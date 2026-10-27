<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/auth_helper.php';

$mysqli = new mysqli('localhost', 'jigsaw', 'Jigsaw1', 'monitoring_system');
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$auth = require_auth($mysqli);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $site_id       = intval($_GET['site_id'] ?? 0);
    $target_ts_raw = $_GET['target_ts'] ?? '';

    if (!$site_id || !$target_ts_raw) {
        http_response_code(400);
        echo json_encode(['error' => 'site_id and target_ts are required']);
        exit;
    }

    $target_ts = date('Y-m-d H:i:s', strtotime($target_ts_raw));
    if (!$target_ts || $target_ts === '1970-01-01 00:00:00') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid target_ts format. Use YYYY-MM-DD HH:MM:SS']);
        exit;
    }

    $target_ts_escaped = $mysqli->real_escape_string($target_ts);

    $servers_result = $mysqli->query(
        "SELECT s.id, s.name, s.ip as host, ss.is_primary, s.server_type as type
         FROM servers s
         INNER JOIN site_servers ss ON ss.server_id = s.id
         WHERE ss.site_id = $site_id AND s.is_deleted = 0 AND s.status = 1"
    );
    if (!$servers_result) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch servers: ' . $mysqli->error]);
        exit;
    }
    $servers = [];
    while ($row = $servers_result->fetch_assoc()) {
        $servers[] = $row;
    }

    if (empty($servers)) {
        echo json_encode(['snapshot_ts' => $target_ts, 'servers' => []]);
        exit;
    }

    $snapshot = [];
    foreach ($servers as $server) {
        $srv_id = intval($server['id']);

        $hist_query = "
            SELECT
                m.name   AS metric_key,
                mv.value AS metric_value,
                mv.status,
                mv.created_at AS recorded_at
            FROM (
                SELECT metric_id, MAX(id) AS max_id
                FROM server_metric_values
                WHERE server_id = $srv_id
                  AND created_at <= '$target_ts_escaped'
                GROUP BY metric_id
            ) latest
            INNER JOIN server_metric_values mv ON mv.id = latest.max_id
            INNER JOIN metrics m ON m.id = mv.metric_id
        ";
        $hist_result = $mysqli->query($hist_query);

        $metrics = [];
        if ($hist_result) {
            while ($row = $hist_result->fetch_assoc()) {
                $metrics[$row['metric_key']] = [
                    'metric_key'   => $row['metric_key'],
                    'metric_value' => $row['metric_value'],
                    'status'       => $row['status'],
                    'recorded_at'  => $row['recorded_at'],
                ];
            }
        }

        $online_check = $mysqli->query(
            "SELECT 1 FROM server_metric_values
             WHERE server_id = $srv_id
               AND created_at BETWEEN DATE_SUB('$target_ts_escaped', INTERVAL 10 MINUTE) AND '$target_ts_escaped'
             LIMIT 1"
        );
        $was_online = ($online_check && $online_check->num_rows > 0);

        // Map everything to expected format for MonitoreoSite.tsx
        $sys = [
            'cpu_usage'    => floatval($metrics['system.cpu.load']['metric_value'] ?? 0),
            'cpu_status'   => $metrics['system.cpu.load']['status'] ?? 'ok',
            'ram_used'     => $metrics['system.ram.used']['metric_value'] ?? '0',
            'ram_total'    => $metrics['system.ram.total']['metric_value'] ?? '0',
            'ram_percent'  => floatval($metrics['system.ram.percent']['metric_value'] ?? 0),
            'ram_status'   => $metrics['system.ram.percent']['status'] ?? 'ok',
            'disk_used'    => $metrics['system.disk.used']['metric_value'] ?? '0',
            'disk_total'   => $metrics['system.disk.total']['metric_value'] ?? '0',
            'disk_percent' => floatval($metrics['system.disk.percent']['metric_value'] ?? 0),
            'disk_status'  => $metrics['system.disk.percent']['status'] ?? 'ok',
            'load_average' => $metrics['system.load.average']['metric_value'] ?? '0.0',
            'uptime_seconds'=> intval($metrics['system.uptime']['metric_value'] ?? 0)
        ];

        // Freshness virtual metric for frontend online detection
        $metrics['system.freshness'] = [
            'metric_key' => 'system.freshness',
            'metric_value' => $was_online ? $target_ts : 'offline',
            'status' => $was_online ? 'ok' : 'danger'
        ];

        $cpu_hist_res = $mysqli->query(
            "SELECT value AS cpu_usage, created_at
             FROM server_metric_values
             WHERE server_id = $srv_id
               AND metric_id = (SELECT id FROM metrics WHERE name = 'system.cpu.load' LIMIT 1)
               AND created_at <= '$target_ts_escaped'
             ORDER BY created_at DESC
             LIMIT 30"
        );
        $cpu_history = [];
        if ($cpu_hist_res) {
            while ($r = $cpu_hist_res->fetch_assoc()) {
                $cpu_history[] = ['cpu_usage' => floatval($r['cpu_usage']), 'created_at' => $r['created_at']];
            }
            $cpu_history = array_reverse($cpu_history);
        }

        $server['ip_address'] = $server['host']; // Fallback alias

        $snapshot[] = [
            'info'        => $server,
            'system'      => $sys,
            'app'         => $metrics,
            'cpu_history' => $cpu_history,
            'services'    => [] // historical services not needed
        ];
    }

    echo json_encode([
        'snapshot_ts' => $target_ts,
        'site_id'     => $site_id,
        'servers'     => $snapshot,
    ]);
}

$mysqli->close();
