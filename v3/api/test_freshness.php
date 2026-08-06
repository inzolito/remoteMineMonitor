<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes/MetricsEvaluator.php';

$db = new DB();
$mysqli = $db->getConnection();
$evaluator = new MetricsEvaluator($mysqli);

$key = 'system.freshness';
$updated_at = '2026-01-29 15:56:42';
$formatted_updated_at = str_replace([' ', ':'], ['_', '-'], $updated_at);

echo "Evaluating $key = $formatted_updated_at ...\n";
// server_id 30 is the primary server for capcnn
$status = $evaluator->evaluate($key, $formatted_updated_at, 30, null);
echo "Result Status: $status\n";

$res = $mysqli->query("SELECT * FROM alert_rules WHERE metric_pattern = 'system.freshness' ORDER BY priority DESC");
echo "Rules matching:\n";
while($r = $res->fetch_assoc()) {
    echo "- ID: {$r['id']}, Pattern: {$r['metric_pattern']}, OP: {$r['operator']}, Val: {$r['threshold_value']}, Cat: {$r['alert_category']}, Prio: {$r['priority']}\n";
}
?>
