<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes/MetricsEvaluator.php';

$db = (new DB())->getConnection();
$evaluator = new MetricsEvaluator($db);

echo "Starting re-evaluation...\n";

// Get all metrics
$stmt = $db->query("SELECT id, server_id, metric_key, metric_value FROM server_app_metrics");
$count = 0;
while ($row = $stmt->fetch_assoc()) {
    $status = $evaluator->evaluate($row['metric_key'], $row['metric_value'], $row['server_id']);
    
    // Update status
    $update = $db->prepare("UPDATE server_app_metrics SET status = ? WHERE id = ?");
    $update->bind_param("si", $status, $row['id']);
    $update->execute();
    
    if ($status === 'danger') {
        echo "[{$row['id']}] {$row['metric_key']} Val: {$row['metric_value']} -> New Status: $status (ALERT TRIGGERED)\n";
    }
    $count++;
}

echo "Re-evaluated $count metrics.\n";
