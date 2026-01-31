<?php

class MetricsEvaluator {
    private $rules = [];
    private $mysqli;
    private $contextCache = [];

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->loadRules();
    }

    private function loadRules() {
        $result = $this->mysqli->query("SELECT * FROM alert_rules ORDER BY priority DESC");
        if ($result) {
            $this->rules = $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    /**
     * Evaluate a metric against database rules.
     * Pure engine: No hardcoded keys or logic.
     */
    public function evaluate($key, $value, $serverId = null) {
        $status = 'ok';
        $matchedRule = null;
        $anyRuleMatched = null;

        // Iterate through rules to find matches for this key
        foreach ($this->rules as $rule) {
            // Check if pattern matches key (SQL LIKE style converted to Regex)
            $pattern = '/^' . str_replace(['%', '.'], ['.*', '\.'], $rule['metric_pattern']) . '$/';
            
            if (preg_match($pattern, $key)) {
                if (!$anyRuleMatched) $anyRuleMatched = $rule; 

                $currentStatus = 'ok';
                if ($rule['rule_type'] === 'threshold') {
                    $currentStatus = $this->evaluateThreshold($value, $rule['warning_threshold'], $rule['danger_threshold']);
                } elseif ($rule['rule_type'] === 'regex') {
                    $currentStatus = $this->evaluateRegex($value, $rule['warning_threshold'], $rule['danger_threshold']);
                } elseif ($rule['rule_type'] === 'freshness') {
                    $currentStatus = $this->evaluateFreshness($value, $rule['warning_threshold'], $rule['danger_threshold']);
                }

                // Escalate status
                if ($this->isWorse($currentStatus, $status)) {
                    $status = $currentStatus;
                    $matchedRule = $rule; 
                }
                
                if ($status === 'danger') break; 
            }
        }

        // Trigger alert management
        if ($serverId && ($matchedRule || ($anyRuleMatched && ($status === 'ok' || $status === 'warning')))) {
            $this->manageAlerts($serverId, $key, $status, $matchedRule ?: $anyRuleMatched);
        }

        return $status;
    }

    private function manageAlerts($serverId, $key, $status, $rule) {
        if ($status === 'danger') {
            if (!isset($this->contextCache[$serverId])) {
                $ctxStmt = $this->mysqli->prepare("SELECT s.server_type, s.ip as ip_address, si.name as site_name, s.name as server_name FROM servers s JOIN site_servers ss ON s.id = ss.server_id JOIN sites si ON ss.site_id = si.id WHERE s.id = ?");
                if ($ctxStmt) {
                    $ctxStmt->bind_param("i", $serverId);
                    $ctxStmt->execute();
                    $this->contextCache[$serverId] = $ctxStmt->get_result()->fetch_assoc();
                } else {
                     $this->contextCache[$serverId] = null;
                }
            }
            
            $ctx = $this->contextCache[$serverId];
            
            // [FIX] REMOVED HARDCODED PRIMARY SERVER CHECK FOR BACKUPS
            // Logic strictly follows DB rules.

            $stmt = $this->mysqli->prepare("SELECT id FROM alerts WHERE server_id = ? AND metric_key = ? AND status IN ('active', 'acknowledged') LIMIT 1");
            $stmt->bind_param("is", $serverId, $key);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                $siteName = $ctx['site_name'] ?? 'Unknown Site';
                $serverType = $ctx['server_type'] ?? 'Server';
                $serverIp = $ctx['ip_address'] ?? '0.0.0.0';

                $roleMap = [
                    'Active' => 'FMS Activo',
                    'Activo' => 'FMS Activo',
                    'Backup' => 'FMS Backup',
                    'Primary' => 'Primario',
                    'Secondary' => 'Secundario',
                    'Tunel' => 'Túnel de Comunicación',
                    'Pivote' => 'Servidor Pivote',
                    'Mpdata' => 'Servidor de Datos MP',
                    'Estacion Base' => 'Estación Base',
                    'Servidor SQL Server' => 'SQL Server'
                ];
                $translatedRole = $roleMap[$serverType] ?? $serverType;

                $title = "Alerta en el servidor $translatedRole de $siteName";
                $desc = $rule['description'] ?? "Se ha detectado una anomalía";
                $desc = str_ireplace(['Alerta Detector:', 'Alerta Detector', 'Crítico:', 'Crítica:'], '', $desc);
                $desc = trim($desc);
                $fullDesc = "[IP: $serverIp] " . $desc;
                
                $ins = $this->mysqli->prepare("INSERT INTO alerts (server_id, metric_key, title, description, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                $ins->bind_param("isss", $serverId, $key, $title, $fullDesc);
                $ins->execute();
            }
        } elseif ($status === 'ok' || $status === 'warning') {
            $stmt = $this->mysqli->prepare("UPDATE alerts SET status = 'solved', solved_at = NOW() WHERE server_id = ? AND metric_key = ? AND status IN ('active', 'acknowledged')");
            $stmt->bind_param("is", $serverId, $key);
            $stmt->execute();
        }
    }

    private function isWorse($new, $current) {
        $levels = ['ok' => 0, 'warning' => 1, 'danger' => 2];
        return $levels[$new] > $levels[$current];
    }

    private function evaluateThreshold($value, $warn, $danger) {
        $check = function($val, $cond) {
            if (empty($cond)) return false;
            if (preg_match('/^([<>]=?)\s*([\d\.]+)$/', $cond, $m)) {
                $op = $m[1];
                $thresh = floatval($m[2]);
                $v = floatval($val);
                switch($op) {
                    case '>': return $v > $thresh;
                    case '>=': return $v >= $thresh;
                    case '<': return $v < $thresh;
                    case '<=': return $v <= $thresh;
                }
            }
            return floatval($val) > floatval($cond);
        };

        if ($check($value, $danger)) return 'danger';
        if ($check($value, $warn)) return 'warning';
        return 'ok';
    }

    private function evaluateRegex($value, $warn, $danger) {
        if (!empty($danger) && preg_match('/' . $danger . '/i', $value)) return 'danger';
        if (!empty($warn) && preg_match('/' . $warn . '/i', $value)) return 'warning';
        return 'ok';
    }

    private function evaluateFreshness($value, $warn, $danger) {
        $timestamp = null;
        $age = null;

        if (preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $value, $m)) {
            $timestamp = DateTime::createFromFormat('Y-m-d_H-i-s', $m[1], new DateTimeZone('America/Santiago'))->getTimestamp();
        } elseif (preg_match('/(\d{4}-\d{2}-\d{2})/', $value, $m)) {
            $timestamp = DateTime::createFromFormat('Y-m-d', $m[1], new DateTimeZone('America/Santiago'))->getTimestamp();
        }

        if ($timestamp) {
            $age = time() - $timestamp;
        }

        if ($age === null) return 'ok'; 

        $ageParams = function($str) {
            if (preg_match('/(\d+)([hmd])/', $str, $m)) {
                 if ($m[2] === 'd') return $m[1] * 86400;
                 if ($m[2] === 'h') return $m[1] * 3600;
                 if ($m[2] === 'm') return $m[1] * 60;
            }
            return intval($str); 
        };

        if (!empty($danger) && $age > $ageParams($danger)) return 'danger';
        if (!empty($warn) && $age > $ageParams($warn)) return 'warning';

        return 'ok';
    }
}
?>
