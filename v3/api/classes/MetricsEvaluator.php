<?php

class MetricsEvaluator {
    private $rules = [];
    private $mysqli;
    private $contextCache = [];

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        date_default_timezone_set('America/Santiago');
        $this->loadRules();
    }

    private function loadRules() {
        $result = $this->mysqli->query("SELECT * FROM alert_rules ORDER BY priority DESC");
        if ($result) {
            $this->rules = $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    public function normalizeValue($key, $value) {
        if (!is_string($value)) return $value;

        // 1. Truck/Equipment Counting (Connectivity)
        if ($key === 'app.connectivity.trucks' || $key === 'app.repc') {
            $lines = array_filter(explode("\n", trim($value)), function($l) {
                $l = trim($l);
                return $l !== "" && stripos($l, "name") === false && stripos($l, "symbol") === false && stripos($l, "---") === false;
            });
            return count($lines);
        }

        // 2. Backup Age Normalization (D-HH:MM:SS format)
        if (strpos($key, 'backup.') !== false && strpos($key, '.status') !== false) {
            if (preg_match('/(\d+)-(\d{2}):(\d{2}):(\d{2})/', $value, $m)) {
                $days = intval($m[1]);
                $hours = intval($m[2]);
                $mins = intval($m[3]);
                $secs = intval($m[4]);
                $totalSecs = ($days * 86400) + ($hours * 3600) + ($mins * 60) + $secs;
                // Return a clear timestamp format for AGE_GREATER_THAN
                return date('Y-m-d_H-i-s', time() - $totalSecs);
            }
        }

        // 3. IDLE Query Count (Multiline parsing)
        if (($key === 'db.idle_queries' || $key === 'idleQuery') && strpos($value, '|') !== false) {
            $lines = array_filter(explode("\n", trim($value)), function($l) {
                $l = trim($l);
                return !empty($l) && strpos($l, '|') !== false && stripos($l, 'pid') === false && strpos($l, '---') === false;
            });
            return count($lines);
        }

        // 4. Max ID Gap Calculation
        if ($key === 'db.max_id_table' && strpos($value, '|') !== false) {
            $parts = explode('|', $value);
            $currentId = intval($parts[1] ?? 0);
            return 2147483647 - $currentId;
        }

        // 5. Summarizer Execution Count (Multiline parsing)
        if ($key === 'app.summarizer.ejecution') {
            $lines = array_filter(explode("\n", trim($value)), function($l) {
                $l = trim($l);
                return !empty($l);
            });
            // Each 2 lines is one process (parent + child)
            // Odd line = manual summarization, still counts as a process
            return ceil(count($lines) / 2);
        }

        return $value;
    }

    public function evaluate($key, $value, $serverId = null, $metricId = null) {
        $value = $this->normalizeValue($key, $value);
        $status = 'ok';
        $matchedRule = null;

        foreach ($this->rules as $rule) {
            $isMatch = false;

            // 1. Match by explicit metric_id
            if ($metricId !== null && $rule['metric_id'] !== null) {
                if ((int)$rule['metric_id'] === (int)$metricId) {
                    $isMatch = true;
                }
            } 
            
            // 2. Fallback to pattern matching
            if (!$isMatch && !empty($rule['metric_pattern'])) {
                $pattern = '/^' . str_replace(['%', '.'], ['.*', '\.'], $rule['metric_pattern']) . '$/';
                if (preg_match($pattern, $key)) {
                    $isMatch = true;
                }
            }

            if ($isMatch) {
                $triggered = $this->checkRule($value, $rule['operator'], $rule['threshold_value']);

                if ($triggered) {
                    $currentStatus = $rule['alert_category'];
                    // DEBUG LOG match
                    if ($key === 'system.cpu.load') {
                         file_put_contents(__DIR__ . '/../debug_cpu.log', date('Y-m-d H:i:s') . " RULE MATCH: ID={$rule['id']} Threshold={$rule['threshold_value']} Cat=$currentStatus triggered=" . ($triggered?'yes':'no') . "\n", FILE_APPEND);
                    }
                    if ($this->isWorse($currentStatus, $status)) {
                        $status = $currentStatus;
                        $matchedRule = $rule; 
                    }
                }
            }
        }

        if ($serverId) {
            $this->manageAlerts($serverId, $key, $status, $matchedRule);
        }

        return $status;
    }

    private function checkRule($value, $operator, $threshold) {
        if (empty($operator)) return false;
        $operator = strtoupper($operator);

        switch ($operator) {
            case '>': return floatval($value) > floatval($threshold);
            case '>=': return floatval($value) >= floatval($threshold);
            case '<': return floatval($value) < floatval($threshold);
            case '<=': return floatval($value) <= floatval($threshold);
            case '=': return (string)$value === (string)$threshold;
            case '!=': return (string)$value !== (string)$threshold;
            
            case 'BETWEEN':
                $parts = explode(',', $threshold);
                if (count($parts) !== 2) return false;
                $v = floatval($value);
                return $v >= floatval($parts[0]) && $v <= floatval($parts[1]);

            case 'CONTAINS':
                return stripos((string)$value, (string)$threshold) !== false;

            case 'NOT_CONTAINS':
                return stripos((string)$value, (string)$threshold) === false;

            case 'REGEX':
                return (bool)preg_match('/' . $threshold . '/i', (string)$value);

            case 'AGE_GREATER_THAN':
                return $this->checkAge($value, $threshold);

            case 'DURATION_GREATER_THAN':
                return $this->checkDuration($value, $threshold);

            default:
                return false;
        }
    }

    private function checkAge($value, $condition) {
        $timestamp = null;
        if (preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $value, $m)) {
            $timestamp = DateTime::createFromFormat('Y-m-d_H-i-s', $m[1])->getTimestamp();
        } elseif (preg_match('/(\d{4}-\d{2}-\d{2})/', $value, $m)) {
            $timestamp = DateTime::createFromFormat('Y-m-d', $m[1])->getTimestamp();
        }

        if (!$timestamp) return false;
        $age = time() - $timestamp;

        $limit = $this->parseTimePeriod($condition);
        return $age > $limit;
    }

    private function checkDuration($value, $condition) {
        // Input like "00:03:21"
        if (preg_match('/(?:(\d+):)?(\d+):(\d+)/', $value, $m)) {
            $hours = intval($m[1] ?? 0);
            $mins = intval($m[2]);
            $secs = intval($m[3]);
            $total = ($hours * 3600) + ($mins * 60) + $secs;
            
            $limit = $this->parseTimePeriod($condition);
            return $total > $limit;
        }
        return false;
    }

    private function parseTimePeriod($str) {
        if (preg_match('/([\d\.]+)([hmds])/', strtolower($str), $m)) {
             $val = floatval($m[1]);
             $unit = $m[2];
             if ($unit === 'd') return $val * 86400;
             if ($unit === 'h') return $val * 3600;
             if ($unit === 'm') return $val * 60;
             if ($unit === 's') return $val;
        }
        return intval($str); 
    }

    public function manageAlerts($serverId, $key, $status, $rule = null) {
        $isDangerStatus = $this->isWorse($status, 'warning');

        if ($isDangerStatus) {
            if (!isset($this->contextCache[$serverId])) {
                $ctxStmt = $this->mysqli->prepare("SELECT s.server_type, s.ip as ip_address, si.name as site_name, s.name as server_name, s.is_deleted FROM servers s JOIN site_servers ss ON s.id = ss.server_id JOIN sites si ON ss.site_id = si.id WHERE s.id = ?");
                if ($ctxStmt) {
                    $ctxStmt->bind_param("i", $serverId);
                    $ctxStmt->execute();
                    $this->contextCache[$serverId] = $ctxStmt->get_result()->fetch_assoc();
                } else {
                     $this->contextCache[$serverId] = null;
                }
            }
            
            $ctx = $this->contextCache[$serverId];
            
            // Do not alert if server is deleted
            if ($ctx && isset($ctx['is_deleted']) && (int)$ctx['is_deleted'] === 1) {
                return;
            }
            
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
                $desc = $rule['description'] ?? "Se ha detectado una anomalía ($status)";
                $desc = str_ireplace(['Alerta Detector:', 'Alerta Detector', 'Crítico:', 'Crítica:'], '', $desc);
                $desc = trim($desc);
                $fullDesc = "[IP: $serverIp] " . $desc;
                
                $ins = $this->mysqli->prepare("INSERT INTO alerts (server_id, metric_key, title, description, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                $ins->bind_param("isss", $serverId, $key, $title, $fullDesc);
                $ins->execute();
            }
        } else {
            $stmt = $this->mysqli->prepare("UPDATE alerts SET status = 'solved', solved_at = NOW() WHERE server_id = ? AND metric_key = ? AND status IN ('active', 'acknowledged')");
            $stmt->bind_param("is", $serverId, $key);
            $stmt->execute();
        }
    }

    private function isWorse($new, $current) {
        $levels = [
            'ok' => 0, 
            'warning' => 1, 
            'danger' => 2,
            'critical' => 3,
            'fatal' => 4,
            'error' => 2
        ];
        $n = $levels[strtolower($new)] ?? 1;
        $c = $levels[strtolower($current)] ?? 0;
        return $n > $c;
    }
}
