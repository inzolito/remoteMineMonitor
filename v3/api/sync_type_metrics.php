<?php
/**
 * Synchronize Server Metrics Config from Templates
 */

require_once __DIR__ . '/db.php';

function syncServerTemplates($specificServerId = null) {
    $database = new DB();
    $mysqli = $database->getConnection();
    
    $query = "SELECT id, name, server_type_id FROM servers WHERE server_type_id IS NOT NULL";
    if ($specificServerId) {
        $query .= " AND id = " . intval($specificServerId);
    }
    
    $serversRes = $mysqli->query($query);
    if (!$serversRes) return false;

    $synced = 0;
    while ($server = $serversRes->fetch_assoc()) {
        $serverId = $server['id'];
        $typeId = $server['server_type_id'];

        $metricsRes = $mysqli->query("SELECT metric_id, default_frequency FROM server_type_metrics WHERE server_type_id = $typeId");
        if (!$metricsRes) continue;

        while ($m = $metricsRes->fetch_assoc()) {
            $metricId = $m['metric_id'];
            $frequency = $m['default_frequency'] ?? 60;
            
            $upsert = $mysqli->prepare("
                INSERT INTO server_metrics_config (server_id, metric_id, frequency, is_enabled) 
                VALUES (?, ?, ?, 1) 
                ON DUPLICATE KEY UPDATE 
                    frequency = IF(frequency IS NULL OR frequency = 0, VALUES(frequency), frequency),
                    is_enabled = 1
            ");
            $upsert->bind_param("iii", $serverId, $metricId, $frequency);
            $upsert->execute();
        }
        $synced++;
    }
    return $synced;
}

// Support CLI execution
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Starting Sync...\n";
    $count = syncServerTemplates();
    echo "Sync complete. Servers updated: $count\n";
}
