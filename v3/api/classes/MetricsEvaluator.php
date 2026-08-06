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
                return !empty($l) && strpos($l, '/bin/sh -c') === false; // Ignore shell wrappers
            });
            // Every remaining line is an actual Summarizer process
            return count($lines);
        }

        // 6. JAMS Restarts Count (Numeric string or multiline log parsing)
        if ($key === 'app.jams.restarts_log') {
            $strVal = trim((string)$value);
            if (is_numeric($strVal)) {
                return intval($strVal);
            }
            $lines = array_filter(explode("\n", $strVal), function($l) {
                return !empty(trim($l));
            });
            return count($lines);
        }

        // 7. Active Scripts and Replicas Execution Time (Multiline parsing)
        // Returns the MAXIMUM execution time in seconds
        if (($key === 'app.fms.active_scripts' || $key === 'app.fms.replica') && is_string($value)) {
            $lines = explode("\n", trim($value));
            $maxSecs = 0;
            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 3) {
                    $timeStr = $parts[2];
                    
                    $days = 0; $hours = 0; $mins = 0; $secs = 0;
                    
                    if (strpos($timeStr, '-') !== false) {
                        $timeParts = explode('-', $timeStr);
                        $days = intval($timeParts[0]);
                        $timeStr = $timeParts[1] ?? '00:00';
                    }
                    
                    $timeChunks = explode(':', $timeStr);
                    if (count($timeChunks) === 3) {
                        $hours = intval($timeChunks[0]);
                        $mins = intval($timeChunks[1]);
                        $secs = intval($timeChunks[2]);
                    } elseif (count($timeChunks) === 2) {
                        $mins = intval($timeChunks[0]);
                        $secs = intval($timeChunks[1]);
                    }
                    
                    $totalSecs = ($days * 86400) + ($hours * 3600) + ($mins * 60) + $secs;
                    if ($totalSecs > $maxSecs) {
                        $maxSecs = $totalSecs;
                    }
                }
            }
            return $maxSecs;
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

        // --- CUSTOM OVERRIDE: app.summarizer.ejecution ---
        // Rules:
        // 1. Only alert if summarizer is actually running (service_active, cron_active, both_active)
        // 2. "both_active" mode gets +1 extra process margin (danger threshold = 3 instead of 2)
        // 3. Must stay elevated for 2 minutes before escalating to danger
        if ($key === 'app.summarizer.ejecution' && $serverId && $metricId) {
            $currentCount = intval($value); // already normalized to process count

            // Get summarizer run mode
            $statusRes = $this->mysqli->query(
                "SELECT metric_value FROM server_app_metrics WHERE server_id = $serverId AND metric_key = 'app.summarizer.status' LIMIT 1"
            );
            $summarizerMode = ($statusRes && $statusRes->num_rows > 0)
                ? trim($statusRes->fetch_assoc()['metric_value'])
                : '';

            if ($currentCount === 0) {
                $status = 'ok';
            } else {
                // both_active gets +1 margin: danger at >3 processes; single mode: danger at >2
                $dangerThreshold = ($summarizerMode === 'both_active') ? 3 : 2;

                if ($currentCount <= 1) {
                    $status = 'ok';
                    $this->mysqli->query("DELETE FROM server_app_metrics WHERE server_id = $serverId AND metric_key = 'app.summarizer.ejecution.warning_start'");
                } elseif ($currentCount < $dangerThreshold) {
                    $status = 'warning';
                    $this->mysqli->query("DELETE FROM server_app_metrics WHERE server_id = $serverId AND metric_key = 'app.summarizer.ejecution.warning_start'");
                } else {
                    // Enforce 2-minute delay by checking a custom state in server_app_metrics
                    $stateRes = $this->mysqli->query("SELECT metric_value FROM server_app_metrics WHERE server_id = $serverId AND metric_key = 'app.summarizer.ejecution.warning_start'");
                    if ($stateRes && $stateRes->num_rows > 0) {
                        $warningStart = (int)$stateRes->fetch_assoc()['metric_value'];
                    } else {
                        $warningStart = time();
                        $this->mysqli->query("INSERT INTO server_app_metrics (server_id, metric_key, metric_value, status, last_updated) VALUES ($serverId, 'app.summarizer.ejecution.warning_start', '$warningStart', 'ok', NOW()) ON DUPLICATE KEY UPDATE metric_value = '$warningStart', last_updated = NOW()");
                    }

                    if ((time() - $warningStart) < 120) {
                        $status = 'warning'; // Within 2-minute grace period
                    } else {
                        $status = 'danger';  // 2 minutes exceeded → fire alert
                    }
                }
            }
        }
        // --- END CUSTOM OVERRIDE ---

        if ($serverId) {
            $this->manageAlerts($serverId, $key, $status, $matchedRule, $value, $metricId);
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

    public function manageAlerts($serverId, $key, $status, $rule = null, $metricValue = null, $metricId = null) {
        $isDangerStatus = $this->isWorse($status, 'warning');

        if (!isset($this->contextCache[$serverId])) {
            $ctxStmt = $this->mysqli->prepare("SELECT s.server_type, s.ip as ip_address, si.name as site_name, s.name as server_name, s.is_deleted FROM servers s LEFT JOIN site_servers ss ON s.id = ss.server_id LEFT JOIN sites si ON ss.site_id = si.id WHERE s.id = ?");
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

        if ($isDangerStatus) {
            $stmt = $this->mysqli->prepare("SELECT id FROM alerts WHERE server_id = ? AND metric_key = ? AND status IN ('active', 'acknowledged') LIMIT 1");
            $stmt->bind_param("is", $serverId, $key);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if (!$existing) {
                $siteName = $ctx['site_name'] ?? '';
                $serverType = $ctx['server_type'] ?? 'Servidor Desconocido';

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

                $title = "Alerta en el servidor $translatedRole" . ($siteName ? " de $siteName" : "");
                $desc = $rule['description'] ?? "Se ha detectado una anomalía ($status)";
                $desc = str_ireplace(['Alerta Detector:', 'Alerta Detector', 'Crítico:', 'Crítica:'], '', $desc);
                $desc = trim($desc);
                
                $valStr = $metricValue !== null ? (string)$metricValue : null;
                if ($valStr !== null && strlen($valStr) > 255) {
                    $valStr = substr($valStr, 0, 252) . '...';
                }
                $ins = $this->mysqli->prepare("INSERT INTO alerts (server_id, metric_key, title, description, status, created_at, metric_value) VALUES (?, ?, ?, ?, 'active', NOW(), ?)");
                $ins->bind_param("issss", $serverId, $key, $title, $desc, $valStr);
                $ins->execute();
            } else {
                // Clear any pending cooldown because danger is back
                $upd = $this->mysqli->prepare("UPDATE alerts SET ok_since = NULL WHERE id = ?");
                $upd->bind_param("i", $existing['id']);
                $upd->execute();
            }
        } else {
            $stmt = $this->mysqli->prepare("SELECT id, ok_since FROM alerts WHERE server_id = ? AND metric_key = ? AND status IN ('active', 'acknowledged') LIMIT 1");
            $stmt->bind_param("is", $serverId, $key);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if ($existing) {
                // Find cooldown config for this metric
                $cooldownSecs = 5; // Default 5 seconds
                foreach ($this->rules as $r) {
                    $isMatch = false;
                    if ($metricId !== null && $r['metric_id'] !== null && (int)$r['metric_id'] === (int)$metricId) {
                        $isMatch = true;
                    } elseif (!empty($r['metric_pattern'])) {
                        $pattern = '/^' . str_replace(['%', '.'], ['.*', '\.'], $r['metric_pattern']) . '$/';
                        if (preg_match($pattern, $key)) $isMatch = true;
                    }
                    if ($isMatch && isset($r['cooldown_seconds'])) {
                        $cooldownSecs = (int)$r['cooldown_seconds'];
                        break;
                    }
                }

                if ($cooldownSecs <= 0) {
                    // Instant resolve
                    $upd = $this->mysqli->prepare("UPDATE alerts SET status = 'solved', solved_at = NOW(), ok_since = NULL WHERE id = ?");
                    $upd->bind_param("i", $existing['id']);
                    $upd->execute();
                } else {
                    if (empty($existing['ok_since'])) {
                        $upd = $this->mysqli->prepare("UPDATE alerts SET ok_since = NOW() WHERE id = ?");
                        $upd->bind_param("i", $existing['id']);
                        $upd->execute();
                    } else {
                        $okTime = strtotime($existing['ok_since']);
                        if ((time() - $okTime) >= $cooldownSecs) {
                            $upd = $this->mysqli->prepare("UPDATE alerts SET status = 'solved', solved_at = NOW(), ok_since = NULL WHERE id = ?");
                            $upd->bind_param("i", $existing['id']);
                            $upd->execute();
                        }
                    }
                }
            }
        }
    }

    private function isWorse($new, $current) {
        $weights = ['ok' => 0, 'warning' => 1, 'error' => 2, 'danger' => 2, 'critical' => 3];
        $n = isset($weights[$new]) ? $weights[$new] : -1;
        $c = isset($weights[$current]) ? $weights[$current] : -1;
        return $n > $c;
    }
}
