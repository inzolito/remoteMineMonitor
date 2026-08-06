<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes/MetricsEvaluator.php';
$db = new DB();
$mysqli = $db->getConnection();
$rtEvaluator = new MetricsEvaluator($mysqli);

$site_id = 19;
$site_servers = $mysqli->query("SELECT s.*, ss.is_primary FROM servers s JOIN site_servers ss ON s.id = ss.server_id WHERE ss.site_id = $site_id AND s.is_deleted = 0 AND s.server_type_id IN (5, 6)");

$servers = [];
while ($server = $site_servers->fetch_assoc()) {
    $server_id = $server['id'];
    $app = [];
    
    // [NEW] Dynamic evaluation for Offline Servers (Freshness)
    if (isset($server['updated_at'])) {
        $formatted_updated_at = str_replace([' ', ':'], ['_', '-'], $server['updated_at']);
        $freshnessStatus = $rtEvaluator->evaluate('system.freshness', $formatted_updated_at, $server_id, null);
        $app['system.freshness'] = [
            'metric_key' => 'system.freshness',
            'metric_value' => $server['updated_at'],
            'status' => $freshnessStatus
        ];
    }
    
    $servers[] = [
        'info' => $server,
        'app' => $app
    ];
}
echo json_encode(['servers' => $servers]);
?>
