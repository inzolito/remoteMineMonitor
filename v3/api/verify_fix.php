<?php
require_once __DIR__ . '/db.php';
$database = new DB();
$mysqli = $database->getConnection();

// 1. Find a server with metric_id=54 (app.fms.replica) enabled
$res = $mysqli->query("SELECT server_id FROM server_metrics_config WHERE metric_id = 54 AND is_enabled = 1 LIMIT 1");
if (!$res || $res->num_rows === 0) {
    die("No server found with metric 54 enabled. Cannot verify.\n");
}
$row = $res->fetch_assoc();
$serverId = $row['server_id'];
echo "Testing with Server ID: $serverId\n";

// 2. Simulate API Call
$_GET['server_id'] = $serverId;
ob_start();
require __DIR__ . '/get_commands.php';
$json = ob_get_clean();

// 3. Parse and Verify
$data = json_decode($json, true);
if (!$data) {
    die("API returned invalid JSON: $json\n");
}

$found = false;
foreach ($data['metrics'] as $m) {
    if ($m['metric_id'] == 54) {
        $found = true;
        echo "Metric found: {$m['metric_name']}\n";
        echo "Frequency: {$m['frequency']}\n";
        
        if ($m['frequency'] == 1) {
            echo "SUCCESS: Frequency is 1 (matches remote_commands DB value).\n";
        } else {
            echo "FAILURE: Frequency is {$m['frequency']} (expected 1).\n";
        }
    }
}

if (!$found) {
    echo "Metric 54 not found in API response for this server.\n";
}
