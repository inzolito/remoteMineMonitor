<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes/MetricsEvaluator.php';

try {
    $db = (new DB())->getConnection();
    $evaluator = new MetricsEvaluator($db);

    // NOTE: Legacy sync from checksupport has been removed to ensure V3 independence.
    // If legacy sync is needed, use the migration bridge scripts.

    // Get all current metrics from V3 storage
    $stmt = $db->query("SELECT m.id as metric_id, sam.id, sam.server_id, sam.metric_key, sam.metric_value FROM server_app_metrics sam LEFT JOIN metrics m ON sam.metric_key = m.name");
    $count = 0;
    $updated = 0;

    while ($row = $stmt->fetch_assoc()) {
        $status = $evaluator->evaluate($row['metric_key'], $row['metric_value'], $row['server_id'], $row['metric_id']);
        
        // Update status
        $update = $db->prepare("UPDATE server_app_metrics SET status = ? WHERE id = ?");
        $update->bind_param("si", $status, $row['id']);
        $update->execute();
        $updated++;
    }

    echo json_encode(['status' => 'success', 'message' => "Re-evaluated $updated metrics against current rules."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
