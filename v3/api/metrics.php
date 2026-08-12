<?php
// Debug Logger
$headers = getallheaders();
$ua = $headers['User-Agent'] ?? 'No UA';
$ct = $headers['Content-Type'] ?? 'No CT';
file_put_contents(__DIR__ . '/traffic.log', date('Y-m-d H:i:s') . " [{$_SERVER['REQUEST_METHOD']}] {$_SERVER['REMOTE_ADDR']} {$_SERVER['REQUEST_URI']} | UA: $ua | CT: $ct\n", FILE_APPEND);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('America/Santiago');

require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';
$database = new DB();
$mysqli = $database->getConnection();
if ($mysqli) {
    $mysqli->query("SET time_zone = '" . date('P') . "'");
}

require_once __DIR__ . '/classes/MetricsEvaluator.php';
$evaluator = new MetricsEvaluator($mysqli);

/**
 * [CENTRALIZED] Process, Transform, Evaluate and Persist any metric.
 * Strictly moves virtual metric logic here to keep Evaluator PURE.
 */
function processMetric($mysqli, $evaluator, $server_id, $site_id, $key, $val) {
    file_put_contents(__DIR__ . '/metric_keys.log', date('Y-m-d H:i:s') . " SID:$server_id KEY:$key\n", FILE_APPEND);
    if (is_array($val) || is_object($val)) $val = json_encode($val);
    
    // Normalize metric names (mapping legacy or inconsistent keys to V3 standard)
    $mapping = [
        'app.reconciliador.log' => 'app.jams.reconcilie',
        'logReconciliador' => 'app.jams.reconcilie',
        'app.jams.restart' => 'app.jams.restarts_log',
        'app.jams.restarts' => 'app.jams.restarts_log',
        'jamsRestart' => 'app.jams.restarts_log',
        'jams_restart' => 'app.jams.restarts_log',
        'ramPercent' => 'system.ram.percent',
        'porcentajeCpu' => 'system.cpu.load',
        'reiniciosJAMS' => 'app.jams.restarts_log',
        'summarizerService' => 'app.summarizer.service'
    ];
    if (isset($mapping[$key])) $key = $mapping[$key];

    $evalVal = $val;
    
    // 1. Virtual Metric: IDLE Query Count
    // [MOVED TO MetricsEvaluator::normalizeValue]
    
    // 2. Virtual Metric: Max ID Gap
    // [MOVED TO MetricsEvaluator::normalizeValue]
    
    // 3. Virtual Metric: Connectivity Count (Trucks/Equipment)
    // [MOVED TO MetricsEvaluator::normalizeValue]

    // 4. Virtual Metric: Integrity Diff (Requires site context)
    if ($key === 'db.integrity.tables' && $site_id && is_string($val) && strpos($val, '|') !== false) {
        $parseIntegrity = function($v) {
            $res = [];
            foreach(explode("\n", (string)$v) as $l) {
                if (preg_match('/^\s*(\w+)\s*\|\s*(\d+)/', $l, $m)) $res[$m[1]] = (int)$m[2];
            }
            return $res;
        };
        $pArr = $parseIntegrity($val);
        $otherRes = $mysqli->query("SELECT metric_value FROM server_app_metrics WHERE server_id IN (SELECT server_id FROM site_servers WHERE site_id = $site_id AND server_id != $server_id) AND metric_key = 'db.integrity.tables' LIMIT 1");
        if ($otherRow = $otherRes->fetch_assoc()) {
            $sArr = $parseIntegrity($otherRow['metric_value']);
            $maxDiff = 0;
            foreach($pArr as $name => $count) {
                $maxDiff = max($maxDiff, abs($count - ($sArr[$name] ?? 0)));
            }
            $evaluator->evaluate('db.integrity.diff', $maxDiff, $server_id);
        }
    }

    // 5. Virtual Metric: Cluster Name Extraction from procesoJAMSCluster
    if ($key === 'procesoJAMSCluster' && is_string($val)) {
        if (preg_match('/status\s+([a-zA-Z0-9_-]+)/', $val, $matches)) {
            processMetric($mysqli, $evaluator, $server_id, $site_id, 'app.fms.cluster', $matches[1]);
        }
    }

    // La lógica de alertas de Active Scripts se maneja ahora globalmente en MetricsEvaluator::normalizeValue

    // 7. Summarizer Aggregate Alert (Check both service and crontab)
    if ($key === 'app.summarizer.service' || $key === 'app.summarizer.crontab') {
        $otherKey = ($key === 'app.summarizer.service') ? 'app.summarizer.crontab' : 'app.summarizer.service';
        $otherVal = $mysqli->query("SELECT metric_value FROM server_app_metrics WHERE server_id = $server_id AND metric_key = '$otherKey' LIMIT 1")->fetch_assoc()['metric_value'] ?? 'stopped';
        
        $isStopped = function($v) { 
            return empty($v) || 
                   stripos($v, 'stopped') !== false || 
                   stripos($v, 'failed') !== false || 
                   stripos($v, 'error') !== false || 
                   (is_string($v) && strlen(trim($v)) > 0 && trim($v)[0] === '#'); 
        };
        
        $currentInactive = $isStopped($val);
        $otherInactive = $isStopped($otherVal);
        
        $aggrStatus = 'ok';
        if ($currentInactive && $otherInactive) {
            $aggrStatus = 'stopped';
        } else {
            // Determine combined state for the frontend
            $svcVal = ($key === 'app.summarizer.service') ? $val : $otherVal;
            $cronVal = ($key === 'app.summarizer.crontab') ? $val : $otherVal;
            
            $svcActive = !$isStopped($svcVal);
            $cronActive = !$isStopped($cronVal);
            
            if ($svcActive && $cronActive) {
                $aggrStatus = 'both_active';
            } elseif ($svcActive) {
                $aggrStatus = 'service_active';
            } else {
                $aggrStatus = 'cron_active';
            }
        }
        
        // This triggers the 'app.summarizer.status' rule in DB
        // We also persist this aggregated status so frontend can read it easily
        $evaluator->evaluate('app.summarizer.status', $aggrStatus, $server_id);
        
        $stmt_aggr = $mysqli->prepare("INSERT INTO server_app_metrics (server_id, metric_key, metric_value, status, last_updated) VALUES (?, 'app.summarizer.status', ?, 'ok', NOW()) ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value), last_updated = NOW()");
        $stmt_aggr->bind_param("is", $server_id, $aggrStatus);
        $stmt_aggr->execute();
    }

    // 8. Backup Latency
    // [MOVED TO MetricsEvaluator::normalizeValue]

    // 9. Virtual Metric: Shift Tables Max Diff (Primary vs Secondary)
    // Triggered when any server sends db.tables.shifts. Finds sibling server, calculates the max
    // row-count difference across all shift tables, and persists it as db.shifts.max_diff
    // so MetricsEvaluator can evaluate it against alert_rules and create a real alert.
    if ($key === 'db.tables.shifts' && $site_id && is_string($val) && strlen(trim($val)) > 0) {
        $parseShiftMap = function($v) {
            $res = [];
            // Handles both space-separated and newline-separated "TableName|Count" pairs
            if (preg_match_all('/([a-zA-Z0-9_]+)\|(\d+)/', (string)$v, $m, PREG_SET_ORDER)) {
                foreach ($m as $match) {
                    $res[$match[1]] = (int)$match[2];
                }
            }
            return $res;
        };

        $pMap = $parseShiftMap($val);

        if (!empty($pMap)) {
            $otherRes = $mysqli->query(
                "SELECT metric_value FROM server_app_metrics
                 WHERE server_id IN (
                     SELECT server_id FROM site_servers
                     WHERE site_id = $site_id AND server_id != $server_id
                 )
                 AND metric_key = 'db.tables.shifts'
                 ORDER BY last_updated DESC
                 LIMIT 1"
            );
            if ($otherRow = $otherRes->fetch_assoc()) {
                $sMap = $parseShiftMap($otherRow['metric_value']);
                $maxDiff = 0;
                $maxTable = '';
                foreach ($pMap as $table => $count) {
                    $diff = abs($count - ($sMap[$table] ?? 0));
                    if ($diff > $maxDiff) {
                        $maxDiff = $diff;
                        $maxTable = $table;
                    }
                }
                // Persist and evaluate: db.shifts.max_diff holds the numeric diff value
                // The DB rule (> 10 = warning, > 30 = danger) will fire the alert naturally
                processMetric($mysqli, $evaluator, $server_id, $site_id, 'db.shifts.max_diff', $maxDiff);
            }
        }
    }

    // Auto-discovery / Get Metric ID: If metric is not in catalogue, add it.
    // We do this BEFORE evaluate so we can pass the ID to the evaluator for explicit rules
    $metric_id = null;
    $check = $mysqli->query("SELECT id FROM metrics WHERE name = '" . $mysqli->real_escape_string($key) . "' LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        $mysqli->query("INSERT INTO metrics (name, display_name) VALUES ('" . $mysqli->real_escape_string($key) . "', '" . $mysqli->real_escape_string($key) . "')");
        $metric_id = $mysqli->insert_id;
    } else {
        $metric_id = $check->fetch_assoc()['id'];
    }

    // Evaluate against DB Rules (Now passing metric_id for better accuracy)
    $status = $evaluator->evaluate($key, $evalVal, $server_id, $metric_id);
    
    if ($key === 'system.cpu.load') {
        file_put_contents(__DIR__ . '/debug_cpu.log', date('Y-m-d H:i:s') . " [SID:$server_id] Key:$key Val:$val Status:$status\n", FILE_APPEND);
    }

    // Persist to unified table (State)
    $stmt = $mysqli->prepare("INSERT INTO server_app_metrics (server_id, metric_key, metric_value, status, last_updated) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value), status = VALUES(status), last_updated = NOW()");
    $stmt->bind_param("isss", $server_id, $key, $val, $status);
    $stmt->execute();

    // Persist to History (Generic Value Tracking)
    if ($metric_id) {
        $historyStatus = $status ?: 'ok';
        $h_ins = $mysqli->prepare("INSERT INTO server_metric_values (server_id, metric_id, value, status, created_at) VALUES (?, ?, ?, ?, NOW())");
        $h_ins->bind_param("iiss", $server_id, $metric_id, $val, $historyStatus);
        $h_ins->execute();
    }
    
    return $status;
}

function parseSize($str) {
    if (!$str) return 0;
    $str = strtoupper(trim($str));
    preg_match('/([TGMK])/', $str, $matches);
    $unit = $matches[1] ?? '';
    $val = floatval($str);
    if ($unit === 'T') return $val * 1024;
    if ($unit === 'G') return $val; 
    if ($unit === 'M') return $val / 1024;
    if ($unit === 'K') return $val / 1024 / 1024;
    return $val;
}

$method = $_SERVER['REQUEST_METHOD'];

// 1. Get raw input
$rawBody = file_get_contents("php://input");
$bodySize = strlen($rawBody);
if ($bodySize > 0) {
    file_put_contents(__DIR__ . '/raw_data.log', date('Y-m-d H:i:s') . " RAW ($bodySize bytes): " . $rawBody . "\n", FILE_APPEND);
}

// 2. Parse JSON early
$data = json_decode($rawBody, true);

if ($method === 'POST') {
    if (json_last_error() !== JSON_ERROR_NONE && $bodySize > 0) {
        file_put_contents(__DIR__ . '/traffic.log', date('Y-m-d H:i:s') . " [JSON_ERROR] " . json_last_error_msg() . " | Body: " . substr($rawBody, 0, 100) . "\n", FILE_APPEND);
        http_response_code(400); echo json_encode(["error" => "Invalid JSON", "raw_size" => $bodySize]); exit;
    }

    $server_id = $data['server_id'] ?? null;
    $ip = $data['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

    if (!$server_id && $ip) {
        $stmt = $mysqli->prepare("SELECT id FROM servers WHERE ip = ? LIMIT 1");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $server_id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
    }

    if (!$server_id) {
        http_response_code(404); echo json_encode(["message" => "Server $ip not found", "received_id" => $data['server_id'] ?? 'none']); exit();
    }

    // [FIX] Update Status IMMEDIATELY
    $mysqli->query("UPDATE servers SET status=1, updated_at=NOW() WHERE id=$server_id");

    $siteRes = $mysqli->query("SELECT site_id FROM site_servers WHERE server_id = $server_id LIMIT 1");
    $site_id = $siteRes->fetch_assoc()['site_id'] ?? null;

    // 1. System Metrics
    if (isset($data['system'])) {
        $sys = $data['system'];
        $ram_used = parseSize($sys['ram_used'] ?? $sys['ram_usage'] ?? $sys['ram.used'] ?? $sys['ram.usage'] ?? 0);
        $ram_total = parseSize($sys['ram_total'] ?? $sys['ram.total'] ?? 0);
        $disk_used = parseSize($sys['disk_used'] ?? $sys['disk_usage'] ?? $sys['disk.used'] ?? $sys['disk.usage'] ?? 0);
        $disk_total = parseSize($sys['disk_total'] ?? $sys['disk.total'] ?? 0);
        $cpu = floatval($sys['cpu'] ?? $sys['cpu_usage'] ?? $sys['cpu.usage'] ?? $sys['cpu.load'] ?? 0);
        $uptime = intval($sys['uptime'] ?? $sys['system.uptime'] ?? 0);
        $load = explode(',', $sys['load'] ?? $sys['load_average'] ?? $sys['cpu.load'] ?? '0.0')[0];

        $diskPct = ($disk_total > 0) ? ($disk_used / $disk_total * 100) : 0;
        $ramPct = ($ram_total > 0) ? ($ram_used / $ram_total * 100) : 0;
        
        $syncMetrics = [
            'system.cpu.load' => round($cpu, 2),
            'system.ram.percent' => round($ramPct, 2),
            'system.ram.used' => round($ram_used, 2),
            'system.ram.total' => round($ram_total, 2),
            'system.disk.percent' => round($diskPct, 2),
            'system.disk.used' => round($disk_used, 2),
            'system.disk.total' => round($disk_total, 2),
            'system.load.average' => $load,
            'system.uptime' => $uptime
        ];

        foreach ($syncMetrics as $key => $val) {
            processMetric($mysqli, $evaluator, $server_id, $site_id, $key, $val);
        }
    }

    // 2. Services
    if (isset($data['services'])) {
        foreach ($data['services'] as $svc) {
            $stmt = $mysqli->prepare("INSERT INTO server_services (server_id, service_name, status, message, last_checked) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status), message = VALUES(message), last_checked = NOW()");
            $stmt->bind_param("isss", $server_id, $svc['name'], $svc['status'], $svc['message']);
            $stmt->execute();

            // NOTE: processMetric call for "system.services.X" removed. 
            // Services are now handled exclusively via server_services table to avoid redundancy.
        }
    }

    // 3. Database
    if (isset($data['db'])) {
        $db = $data['db'];
        $dbMetrics = [
            'db.connections' => $db['connections'],
            'db.qps' => $db['qps'],
            'db.size_mb' => $db['size_mb']
        ];
        foreach ($dbMetrics as $key => $val) {
            processMetric($mysqli, $evaluator, $server_id, $site_id, $key, $val);
        }
    }
    
    // 4. Generic App Metrics
    if (isset($data['app'])) {
        foreach ($data['app'] as $key => $val) {
            processMetric($mysqli, $evaluator, $server_id, $site_id, $key, $val);
        }
    }

    echo json_encode(["message" => "Metrics updated"]);

} elseif ($method === 'GET') {
    require_once __DIR__ . '/auth_helper.php';
    require_auth($mysqli);

    if (isset($_GET['server_id'])) {
        $server_id = intval($_GET['server_id']);
        $res = [];
        $res['app'] = $app;
        echo json_encode($res);
    } elseif (isset($_GET['site_id'])) {
        $site_id = intval($_GET['site_id']);
        $site_servers = $mysqli->query("SELECT s.*, (CASE WHEN s.server_type_id = 5 THEN 1 ELSE ss.is_primary END) AS is_primary FROM servers s JOIN site_servers ss ON s.id = ss.server_id WHERE ss.site_id = $site_id AND s.is_deleted = 0 AND s.server_type_id IN (5, 6) ORDER BY is_primary DESC");
        
        // [READ-TIME EVALUATOR]
        // Instantiate here to re-evaluate rules against current data on every read.
        $rtEvaluator = new MetricsEvaluator($mysqli);

        // [PRE-FETCH METRIC IDs]
        // Resolve IDs once to ensure ID-based rules match against the evaluator
        // Done BEFORE the loop to avoid any interference with the main query result set
        $metricIds = [];
        $keysToResolve = ["'system.cpu.load'", "'system.ram.percent'", "'system.disk.percent'", "'app.repc'", "'backup.daily.status'", "'backup.hourly.status'", "'app.jams.service'", "'app.jams.restarts_log'", "'app.fms.active_scripts'", "'app.summarizer.log'", "'app.summarizer.ejecution'"];
        $mIdsRes = $mysqli->query("SELECT id, name FROM metrics WHERE name IN (" . implode(',', $keysToResolve) . ")");
        if ($mIdsRes) {
            while($mr = $mIdsRes->fetch_assoc()) {
                $metricIds[$mr['name']] = $mr['id'];
            }
        }

        while ($server = $site_servers->fetch_assoc()) {
            $server_id = $server['id'];

            // ... (rest of the loop content is unchanged until evaluation) ...

            // 1. Fetch App Metrics FIRST (Current State)
            $app = [];
            $appRes = $mysqli->query("SELECT metric_key, metric_value, status FROM server_app_metrics WHERE server_id = $server_id");
            while($ar = $appRes->fetch_assoc()) {
                $app[$ar['metric_key']] = $ar;
            }

            // [ROBUST FALLBACK]: Prioritize history (Rooteo source) over app_metrics cache for critical identifiers
            $injectMetric = function($mysqli, $server_id, &$app, $key, $force = false) use ($rtEvaluator) {
                // If force is true, we always query history and override cache if history has data
                if ($force || !isset($app[$key]) || empty($app[$key]['metric_value'])) {
                    $res = $mysqli->query("SELECT value, status FROM server_metric_values WHERE server_id = $server_id AND metric_id = (SELECT id FROM metrics WHERE name = '$key' LIMIT 1) ORDER BY created_at DESC LIMIT 1");
                    if ($row = $res->fetch_assoc()) {
                        // Re-evaluate status using current alert rules instead of trusting the
                        // stored status column, which may be stale when agents write directly
                        // to server_metric_values without going through the evaluator (e.g. rmmcod_agent.py).
                        $liveStatus = $rtEvaluator->evaluate($key, $row['value']);
                        $app[$key] = ['metric_key' => $key, 'metric_value' => $row['value'], 'status' => $liveStatus];
                        return true;
                    }
                }
                return false;
            };

            // Force fresh names and cluster from history (Always override if history exists)
            $injectMetric($mysqli, $server_id, $app, 'system.name', true);
            $injectMetric($mysqli, $server_id, $app, 'system.hostname', true);
            $injectMetric($mysqli, $server_id, $app, 'hostname', true);
            $injectMetric($mysqli, $server_id, $app, 'app.fms.cluster', true);
            $injectMetric($mysqli, $server_id, $app, 'app.jams.status', true);
            $injectMetric($mysqli, $server_id, $app, 'app.jams.service', true);
            $injectMetric($mysqli, $server_id, $app, 'app.jams.restarts_log', true);
            $injectMetric($mysqli, $server_id, $app, 'app.fms.active_scripts', true);
            $injectMetric($mysqli, $server_id, $app, 'app.fms.replica', true);
            $injectMetric($mysqli, $server_id, $app, 'app.summarizer.service', true);
            $injectMetric($mysqli, $server_id, $app, 'app.summarizer.crontab', true);
            $injectMetric($mysqli, $server_id, $app, 'app.summarizer.log', true);
            $injectMetric($mysqli, $server_id, $app, 'app.summarizer.ejecution', true);
            $injectMetric($mysqli, $server_id, $app, 'system.ntp.status', true);
            $injectMetric($mysqli, $server_id, $app, 'backup.daily.log', true);
            $injectMetric($mysqli, $server_id, $app, 'backup.hourly.log', true);
            $injectMetric($mysqli, $server_id, $app, 'db.schema.date', true);

            // [ROBUST FALLBACK]: Explicitly whitelist ALL dashboard metrics to prevent stale cache
            $dashboardMetrics = [
                'app.jams.version',
                'app.repc',
                'app.station.ping',
                'app.station.name',
                'backup.daily.status',
                'backup.hourly.status',
                'db.size',
                'db.max_id_table',
                'db.idle_queries',
                'db.integrity.tables',
                'db.top_ten_tables',
                'db.tables.shifts',
                'system.files.largest'
            ];
            foreach ($dashboardMetrics as $dm) {
                $injectMetric($mysqli, $server_id, $app, $dm, true);
            }

            // 2. Fetch Services
            $services = [];
            $serRes = $mysqli->query("SELECT * FROM server_services WHERE server_id = $server_id");
            while($sr = $serRes->fetch_assoc()) $services[] = $sr;

            // 3. Fetch History (CPU history for chart)
            $history = [];
            $histRes = $mysqli->query("SELECT value as cpu_usage, created_at FROM server_metric_values WHERE server_id = $server_id AND metric_id = (SELECT id FROM metrics WHERE name = 'system.cpu.load' LIMIT 1) ORDER BY created_at DESC LIMIT 20");
            while($hr = $histRes->fetch_assoc()) $history[] = $hr;

            // 4. Construct System Object (Now safely using $app with fallbacks)
            // [ROBUST FALLBACK]: Prioritize latest value from history (Rooteo source) over app_metrics cache
            $getMetricVal = function($mysqli, $server_id, $app, $key, $histObj = null) {
                // If it's CPU history and we have the array, first item is the newest
                if ($key === 'system.cpu.load' && !empty($histObj)) return $histObj[0]['cpu_usage'];
                
                // For others (RAM, Disk), query history directly to ensure real-time accuracy
                $res = $mysqli->query("SELECT value FROM server_metric_values WHERE server_id = $server_id AND metric_id = (SELECT id FROM metrics WHERE name = '$key' LIMIT 1) ORDER BY created_at DESC LIMIT 1");
                if ($row = $res->fetch_assoc()) return $row['value'];
                
                // Final fallback to app_metrics cache
                return $app[$key]['metric_value'] ?? 0;
            };

            $sys = [
                'cpu_usage' => $getMetricVal($mysqli, $server_id, $app, 'system.cpu.load', $history),
                'cpu_status' => $app['system.cpu.load']['status'] ?? 'ok',
                'ram_used' => $getMetricVal($mysqli, $server_id, $app, 'system.ram.used'),
                'ram_total' => $getMetricVal($mysqli, $server_id, $app, 'system.ram.total'),
                'ram_percent' => $getMetricVal($mysqli, $server_id, $app, 'system.ram.percent'),
                'ram_status' => $app['system.ram.percent']['status'] ?? 'ok',
                'disk_used' => $getMetricVal($mysqli, $server_id, $app, 'system.disk.used'),
                'disk_total' => $getMetricVal($mysqli, $server_id, $app, 'system.disk.total'),
                'disk_percent' => $getMetricVal($mysqli, $server_id, $app, 'system.disk.percent'),
                'disk_status' => $app['system.disk.percent']['status'] ?? 'ok',
                'load_average' => $app['system.load.average']['metric_value'] ?? '0.0',
                'uptime_seconds' => $app['system.uptime']['metric_value'] ?? 0
            ];

            // [READ-TIME EVALUATION]
            // Re-run the evaluator against the fetched values to ensure status matches active DB rules exactly.
            // This overrides any stale status stored in the DB from previous write-time evaluations.
            
            // NOW PASSING METRIC ID to ensure rules linked by ID are found.
            // CONDITION: User requested ONLY RAM and Disk for secondary server.
            // If primary, pass all IDs. If secondary, pass NULL for CPU to skip ID-based CPU rules (pattern rules might still match if any).
            $cpuIdArg = ($server['is_primary'] == 1) ? ($metricIds['system.cpu.load'] ?? null) : null;
            
            $sys['cpu_status'] = $rtEvaluator->evaluate('system.cpu.load', $sys['cpu_usage'], $server_id, $cpuIdArg);
            $sys['ram_status'] = $rtEvaluator->evaluate('system.ram.percent', $sys['ram_percent'], $server_id, $metricIds['system.ram.percent'] ?? null);
            $sys['disk_status'] = $rtEvaluator->evaluate('system.disk.percent', $sys['disk_percent'], $server_id, $metricIds['system.disk.percent'] ?? null);

            // [NEW] Dynamic evaluation for app.repc
            $repcVal = $app['app.repc']['metric_value'] ?? null;
            if ($repcVal !== null) {
                $repcStatus = $rtEvaluator->evaluate('app.repc', $repcVal, $server_id, $metricIds['app.repc'] ?? null);
                $app['app.repc']['status'] = $repcStatus;
            }

            // [NEW] Dynamic evaluation for Backups
            $dailyVal = $app['backup.daily.status']['metric_value'] ?? null;
            if ($dailyVal !== null) {
                $dailyStatus = $rtEvaluator->evaluate('backup.daily.status', $dailyVal, $server_id, $metricIds['backup.daily.status'] ?? null);
                $app['backup.daily.status']['status'] = $dailyStatus;
            }

            $hourlyVal = $app['backup.hourly.status']['metric_value'] ?? null;
            if ($hourlyVal !== null) {
                $hourlyStatus = $rtEvaluator->evaluate('backup.hourly.status', $hourlyVal, $server_id, $metricIds['backup.hourly.status'] ?? null);
                $app['backup.hourly.status']['status'] = $hourlyStatus;
            }

            // [NEW] Dynamic evaluation for JAMS Service
            $jamsSvcVal = $app['app.jams.service']['metric_value'] ?? null;
            if ($jamsSvcVal !== null) {
                $jamsSvcStatus = $rtEvaluator->evaluate('app.jams.service', $jamsSvcVal, $server_id, $metricIds['app.jams.service'] ?? null);
                $app['app.jams.service']['status'] = $jamsSvcStatus;
            }

            // [NEW] Dynamic evaluation for JAMS Restarts Log
            $jamsRestartsVal = $app['app.jams.restarts_log']['metric_value'] ?? $app['app.jams.restart']['metric_value'] ?? null;
            if ($jamsRestartsVal !== null) {
                $jamsRestartsStatus = $rtEvaluator->evaluate('app.jams.restarts_log', $jamsRestartsVal, $server_id, $metricIds['app.jams.restarts_log'] ?? null);
                if (isset($app['app.jams.restarts_log'])) {
                    $app['app.jams.restarts_log']['status'] = $jamsRestartsStatus;
                }
                if (isset($app['app.jams.restart'])) {
                    $app['app.jams.restart']['status'] = $jamsRestartsStatus;
                }
            }

            // [NEW] Dynamic evaluation for JAMS Status (Failover Detection)
            $jamsStatusVal = $app['app.jams.status']['metric_value'] ?? null;
            if ($jamsStatusVal !== null) {
                // Default db rule evaluation
                $jamsRoleStatus = $rtEvaluator->evaluate('app.jams.status', $jamsStatusVal, $server_id, $metricIds['app.jams.status'] ?? null);
                
                // Cross-validation with is_primary
                $isPrimary = $server['is_primary'] == 1;
                $isActive = stripos($jamsStatusVal, 'activ') !== false;

                if ($isPrimary && !$isActive) {
                    $jamsRoleStatus = 'danger'; // Primary should be active!
                } elseif (!$isPrimary && $isActive) {
                    $jamsRoleStatus = 'warning'; // Secondary should NOT be active!
                }

                $app['app.jams.status']['status'] = $jamsRoleStatus;
            }

            // [NEW] Dynamic evaluation for Active Scripts
            $scriptsVal = $app['app.fms.active_scripts']['metric_value'] ?? null;
            if ($scriptsVal !== null) {
                $scriptsStatus = $rtEvaluator->evaluate('app.fms.active_scripts', $scriptsVal, $server_id, $metricIds['app.fms.active_scripts'] ?? null);
                $app['app.fms.active_scripts']['status'] = $scriptsStatus;
            }

            // [NEW] Dynamic evaluation for Replicas
            $replicaVal = $app['app.fms.replica']['metric_value'] ?? null;
            if ($replicaVal !== null) {
                $replicaStatus = $rtEvaluator->evaluate('app.fms.replica', $replicaVal, $server_id, $metricIds['app.fms.replica'] ?? null);
                $app['app.fms.replica']['status'] = $replicaStatus;
            }

            // [NEW] Dynamic evaluation for Idle Queries
            $idleVal = $app['db.idle_queries']['metric_value'] ?? null;
            if ($idleVal !== null) {
                $idleStatus = $rtEvaluator->evaluate('db.idle_queries', $idleVal, $server_id, $metricIds['db.idle_queries'] ?? null);
                $app['db.idle_queries']['status'] = $idleStatus;
            }

            // [NEW] Dynamic evaluation for Summarizer Log
            $logVal = $app['app.summarizer.log']['metric_value'] ?? null;
            if ($logVal !== null) {
                $logStatus = $rtEvaluator->evaluate('app.summarizer.log', $logVal, $server_id, $metricIds['app.summarizer.log'] ?? null);
                $app['app.summarizer.log']['status'] = $logStatus;
            }

            // [NEW] Compute Summarizer Status dynamically for Python agents before execution eval
            $svcVal = $app['app.summarizer.service']['metric_value'] ?? '';
            $cronVal = $app['app.summarizer.crontab']['metric_value'] ?? '';
            
            $isStopped = function($v) { 
                return empty($v) || 
                       stripos($v, 'stopped') !== false || 
                       stripos($v, 'failed') !== false || 
                       stripos($v, 'error') !== false || 
                       (is_string($v) && strlen(trim($v)) > 0 && trim($v)[0] === '#'); 
            };
            
            $svcActive = !$isStopped($svcVal);
            $cronActive = !$isStopped($cronVal);
            
            if (!$svcActive && !$cronActive) {
                $aggrStatus = 'stopped';
            } elseif ($svcActive && $cronActive) {
                $aggrStatus = 'both_active';
            } elseif ($svcActive) {
                $aggrStatus = 'service_active';
            } else {
                $aggrStatus = 'cron_active';
            }
            
            // Persist the status so MetricsEvaluator can read it
            $stmt_aggr = $mysqli->prepare("INSERT INTO server_app_metrics (server_id, metric_key, metric_value, status, last_updated) VALUES (?, 'app.summarizer.status', ?, 'ok', NOW()) ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value), last_updated = NOW()");
            if ($stmt_aggr) {
                $stmt_aggr->bind_param("is", $server_id, $aggrStatus);
                $stmt_aggr->execute();
            }
            $app['app.summarizer.status'] = ['metric_key' => 'app.summarizer.status', 'metric_value' => $aggrStatus, 'status' => 'ok'];

            // [NEW] Dynamic evaluation for Summarizer Ejecution (process count)
            $ejecVal = $app['app.summarizer.ejecution']['metric_value'] ?? null;
            if ($ejecVal !== null) {
                $ejecStatus = $rtEvaluator->evaluate('app.summarizer.ejecution', $ejecVal, $server_id, $metricIds['app.summarizer.ejecution'] ?? null);
                $app['app.summarizer.ejecution']['status'] = $ejecStatus;
                
                // Expose the internal timer start so the UI can display a countdown
                if ($ejecStatus === 'warning' || $ejecStatus === 'danger') {
                    $wsRes = $mysqli->query("SELECT metric_value FROM server_app_metrics WHERE server_id = $server_id AND metric_key = 'app.summarizer.ejecution.warning_start'");
                    if ($wsRes && $wsRes->num_rows > 0) {
                        $app['app.summarizer.ejecution']['warning_start'] = (int)$wsRes->fetch_assoc()['metric_value'];
                    }
                }
            }

            // [NEW] Dynamic evaluation for Offline Servers (Freshness)
            // As per user request: We look STRICTLY at system.cpu.load in history, because it's the metric guaranteed 
            // to update every few seconds by the bot, and it is never artificially inserted by virtual metrics.
            $realUpdatedRes = $mysqli->query("SELECT created_at FROM server_metric_values WHERE server_id = $server_id AND metric_id = (SELECT id FROM metrics WHERE name = 'system.cpu.load' LIMIT 1) ORDER BY created_at DESC LIMIT 1");
            $realUpdatedAt = ($realUpdatedRes && $row = $realUpdatedRes->fetch_assoc()) ? $row['created_at'] : null;
            if (!$realUpdatedAt) {
                $realUpdatedAt = $server['updated_at'] ?? null;
            }

            if ($realUpdatedAt) {
                $formatted_updated_at = str_replace([' ', ':'], ['_', '-'], $realUpdatedAt);
                $freshnessStatus = $rtEvaluator->evaluate('system.freshness', $formatted_updated_at, $server_id, null);
                $app['system.freshness'] = [
                    'metric_key' => 'system.freshness',
                    'metric_value' => $realUpdatedAt,
                    'status' => $freshnessStatus
                ];
            }

            // Ensure compatibility with frontend gauge expectations (inject back into $app)
            if (!isset($app['system.cpu.load'])) {
                $app['system.cpu.load'] = ['metric_key' => 'system.cpu.load', 'metric_value' => $sys['cpu_usage'], 'status' => $sys['cpu_status']];
            } else {
                $app['system.cpu.load']['metric_value'] = $sys['cpu_usage'];
                $app['system.cpu.load']['status'] = $sys['cpu_status'];
            }

            if (!isset($app['system.ram.percent'])) {
                $app['system.ram.percent'] = ['metric_key' => 'system.ram.percent', 'metric_value' => $sys['ram_percent'], 'status' => $sys['ram_status']];
            } else {
                $app['system.ram.percent']['metric_value'] = $sys['ram_percent'];
                $app['system.ram.percent']['status'] = $sys['ram_status'];
            }

            if (!isset($app['system.disk.percent'])) {
                $app['system.disk.percent'] = ['metric_key' => 'system.disk.percent', 'metric_value' => $sys['disk_percent'], 'status' => $sys['disk_status']];
            } else {
                $app['system.disk.percent']['metric_value'] = $sys['disk_percent'];
                $app['system.disk.percent']['status'] = $sys['disk_status'];
            }
            
            // Clean units from percent values if they exist (e.g. "1.4%" -> "1.4")
            $sys['cpu_usage'] = floatval($sys['cpu_usage']);
            $sys['ram_percent'] = floatval($sys['ram_percent']);
            $sys['disk_percent'] = floatval($sys['disk_percent']);

            $servers_data[] = [
                'info' => array_merge($server, ['ip_address' => $server['ip']]),
                'system' => $sys,
                'app' => $app,
                'services' => $services,
                'cpu_history' => array_reverse($history)
            ];
        }

        // [POST-LOOP] Virtual Metric: db.shifts.max_diff
        // Calculated here (after loop) because we need BOTH servers simultaneously.
        // processMetric() is used so the evaluator fires, alert_rules are checked,
        // and alerts are inserted into the alerts table naturally — no manual inserts.
        if (count($servers_data) >= 2) {
            $parseShiftMapRT = function($v) {
                $res = [];
                if (preg_match_all('/([a-zA-Z0-9_]+)\|(\d+)/', (string)$v, $m, PREG_SET_ORDER)) {
                    foreach ($m as $match) $res[$match[1]] = (int)$match[2];
                }
                return $res;
            };

            // Find primary and secondary by is_primary flag
            $primaryData   = null;
            $secondaryData = null;
            foreach ($servers_data as $sd) {
                if ($sd['info']['is_primary'] == 1) $primaryData   = $sd;
                else                                $secondaryData = $sd;
            }

            if ($primaryData && $secondaryData) {
                $mapP = $parseShiftMapRT($primaryData['app']['db.tables.shifts']['metric_value'] ?? '');
                $mapS = $parseShiftMapRT($secondaryData['app']['db.tables.shifts']['metric_value'] ?? '');

                if (!empty($mapP) && !empty($mapS)) {
                    $maxDiff  = 0;
                    foreach ($mapP as $table => $countP) {
                        $diff = abs($countP - ($mapS[$table] ?? 0));
                        if ($diff > $maxDiff) $maxDiff = $diff;
                    }

                    // Evaluate and persist ONLY for the PRIMARY server (alert will be created once)
                    $pId = (int)$primaryData['info']['id'];
                    $sId = (int)$secondaryData['info']['id'];
                    $statusP = processMetric($mysqli, $rtEvaluator, $pId, $site_id, 'db.shifts.max_diff', $maxDiff);

                    // Inject the evaluated status back into the response so the frontend
                    // reads the real status from the backend (not a hardcoded local calculation)
                    foreach ($servers_data as &$sd) {
                        $sd['app']['db.shifts.max_diff'] = [
                            'metric_key'   => 'db.shifts.max_diff',
                            'metric_value' => (string)$maxDiff,
                            // Both get the same status in UI, but only primary generated the alert
                            'status'       => $statusP
                        ];
                    }
                    unset($sd);
                }
                
                // [POST-LOOP] Virtual Metric: app.fms.cluster (None fallback)
                // If ANY server doesn't report a cluster or it's 'None',
                // we force evaluate it here so the alert is created naturally in the DB.
                $serversToCheck = [
                    ['id' => (int)$primaryData['info']['id'], 'val' => $primaryData['app']['app.fms.cluster']['metric_value'] ?? 'None'],
                    ['id' => (int)$secondaryData['info']['id'], 'val' => $secondaryData['app']['app.fms.cluster']['metric_value'] ?? 'None']
                ];

                foreach ($serversToCheck as $stc) {
                    if ($stc['val'] === 'None') {
                        $cStatus = processMetric($mysqli, $rtEvaluator, $stc['id'], $site_id, 'app.fms.cluster', 'None');
                        foreach ($servers_data as &$sd) {
                            if ($sd['info']['id'] == $stc['id']) {
                                $sd['app']['app.fms.cluster'] = [
                                    'metric_key'   => 'app.fms.cluster',
                                    'metric_value' => 'None',
                                    'status'       => $cStatus
                                ];
                            }
                        }
                        unset($sd);
                    }
                }
            }
        }

        echo json_encode(["servers" => $servers_data]);
    }
}
