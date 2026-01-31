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
    if (is_array($val) || is_object($val)) $val = json_encode($val);
    $evalVal = $val;
    
    // 1. Virtual Metric: IDLE Query Count (Multiline parsing)
    if (($key === 'db.idle_queries' || $key === 'idleQuery') && is_string($val) && strpos($val, '|') !== false) {
        $lines = explode("\n", $val);
        $lines = array_filter($lines, function($l) {
            $l = trim($l);
            return !empty($l) && strpos($l, '|') !== false && stripos($l, 'pid') === false && strpos($l, '---') === false;
        });
        $evalVal = count($lines);
    }
    
    // 2. Virtual Metric: Max ID Gap Calculation
    if ($key === 'db.max_id_table' && is_string($val) && strpos($val, '|') !== false) {
        $parts = explode('|', $val);
        $currentId = intval($parts[1] ?? 0);
        $evalVal = 2147483647 - $currentId;
    }
    
    // 3. Virtual Metric: Connectivity Count
    if ($key === 'app.connectivity.trucks' && is_string($val) && strpos($val, "\n") !== false) {
        $lines = array_filter(explode("\n", trim($val)), function($l) {
            return trim($l) !== "" && stripos($l, "name") === false && stripos($l, "symbol") === false && stripos($l, "---") === false;
        });
        $evalVal = count($lines);
    }

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

    // Evaluate against DB Rules
    $status = $evaluator->evaluate($key, $evalVal, $server_id);
    
    // Persist to unified table (State)
    $stmt = $mysqli->prepare("INSERT INTO server_app_metrics (server_id, metric_key, metric_value, status, last_updated) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value), status = VALUES(status), last_updated = NOW()");
    $stmt->bind_param("isss", $server_id, $key, $val, $status);
    $stmt->execute();

    // Persist to History (Generic Value Tracking)
    // Auto-discovery: If metric is not in catalogue, add it.
    $check = $mysqli->query("SELECT id FROM metrics WHERE name = '" . $mysqli->real_escape_string($key) . "' LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        $mysqli->query("INSERT INTO metrics (name, display_name) VALUES ('" . $mysqli->real_escape_string($key) . "', '" . $mysqli->real_escape_string($key) . "')");
        $metric_id = $mysqli->insert_id;
    } else {
        $metric_id = $check->fetch_assoc()['id'];
    }

    if ($metric_id) {
        $h_ins = $mysqli->prepare("INSERT INTO server_metric_values (server_id, metric_id, value, status, created_at) VALUES (?, ?, ?, ?, NOW())");
        $h_ins->bind_param("iiss", $server_id, $metric_id, $val, $status);
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

            processMetric($mysqli, $evaluator, $server_id, $site_id, "system.services." . $svc['name'], $svc['status']);
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
    if (isset($_GET['server_id'])) {
        $server_id = intval($_GET['server_id']);
        $res = [];
        $res['app'] = $app;
        echo json_encode($res);
    } elseif (isset($_GET['site_id'])) {
        $site_id = intval($_GET['site_id']);
        $site_servers = $mysqli->query("SELECT s.*, ss.is_primary FROM servers s JOIN site_servers ss ON s.id = ss.server_id WHERE ss.site_id = $site_id AND s.is_deleted = 0");

        while ($server = $site_servers->fetch_assoc()) {
            $server_id = $server['id'];

            // 1. Fetch App Metrics FIRST
            $app = [];
            $appRes = $mysqli->query("SELECT metric_key, metric_value, status FROM server_app_metrics WHERE server_id = $server_id");
            while($ar = $appRes->fetch_assoc()) {
                $app[$ar['metric_key']] = $ar;
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
                'ram_used' => $app['system.ram.used']['metric_value'] ?? 0,
                'ram_total' => $app['system.ram.total']['metric_value'] ?? 0,
                'ram_percent' => $getMetricVal($mysqli, $server_id, $app, 'system.ram.percent'),
                'disk_used' => $app['system.disk.used']['metric_value'] ?? 0,
                'disk_total' => $app['system.disk.total']['metric_value'] ?? 0,
                'disk_percent' => $getMetricVal($mysqli, $server_id, $app, 'system.disk.percent'),
                'load_average' => $app['system.load.average']['metric_value'] ?? '0.0',
                'uptime_seconds' => $app['system.uptime']['metric_value'] ?? 0
            ];

            // Ensure compatibility with frontend gauge expectations (inject back into $app)
            if (!isset($app['system.cpu.load'])) {
                $app['system.cpu.load'] = ['metric_key' => 'system.cpu.load', 'metric_value' => $sys['cpu_usage'], 'status' => 'ok'];
            } else {
                $app['system.cpu.load']['metric_value'] = $sys['cpu_usage'];
            }

            if (!isset($app['system.ram.percent'])) {
                $app['system.ram.percent'] = ['metric_key' => 'system.ram.percent', 'metric_value' => $sys['ram_percent'], 'status' => 'ok'];
            } else {
                $app['system.ram.percent']['metric_value'] = $sys['ram_percent'];
            }

            if (!isset($app['system.disk.percent'])) {
                $app['system.disk.percent'] = ['metric_key' => 'system.disk.percent', 'metric_value' => $sys['disk_percent'], 'status' => 'ok'];
            } else {
                $app['system.disk.percent']['metric_value'] = $sys['disk_percent'];
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
        echo json_encode(["servers" => $servers_data]);
    }
}
