<?php
/**
 * One-time correction for Servers
 */
require_once __DIR__ . '/db.php';
$mysqli = new mysqli('localhost', 'jigsaw', 'Jigsaw1', 'monitoring_system');

echo "Cleaning up ghosts...\n";
$mysqli->query("UPDATE servers SET is_deleted = 1 WHERE name IS NULL OR name = '' OR ip IS NULL OR ip = ''");

echo "Auto-assigning Server Types...\n";

// 1. FMS_PRIMARY
$res = $mysqli->query("SELECT id FROM server_types WHERE name = 'FMS_PRIMARY'");
if ($row = $res->fetch_assoc()) {
    $tid = $row['id'];
    $sql = "UPDATE servers SET server_type_id = $tid WHERE (server_type IN ('Active', 'Activo', 'Primary', 'FMS') OR name LIKE '%Activo%' OR name LIKE '%Active%') AND server_type_id IS NULL";
    $mysqli->query($sql);
    echo "Assigned FMS_PRIMARY: " . $mysqli->affected_rows . "\n";
}

// 2. FMS_SECONDARY
$res = $mysqli->query("SELECT id FROM server_types WHERE name = 'FMS_SECONDARY'");
if ($row = $res->fetch_assoc()) {
    $tid = $row['id'];
    $sql = "UPDATE servers SET server_type_id = $tid WHERE (server_type IN ('Backup', 'Standby', 'Secondary') OR name LIKE '%Backup%' OR name LIKE '%Standby%') AND server_type_id IS NULL";
    $mysqli->query($sql);
    echo "Assigned FMS_SECONDARY: " . $mysqli->affected_rows . "\n";
}

// 3. OAS
$res = $mysqli->query("SELECT id FROM server_types WHERE name = 'OAS'");
if ($row = $res->fetch_assoc()) {
    $tid = $row['id'];
    $sql = "UPDATE servers SET server_type_id = $tid WHERE (server_type LIKE '%OAS%' OR name LIKE '%OAS%') AND server_type_id IS NULL";
    $mysqli->query($sql);
    echo "Assigned OAS: " . $mysqli->affected_rows . "\n";
}

echo "Syncing new assignments to config...\n";
require_once __DIR__ . '/sync_type_metrics.php';
if (function_exists('syncServerTemplates')) {
    syncServerTemplates();
}
echo "Done.\n";
