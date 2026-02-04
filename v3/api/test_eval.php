<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes/MetricsEvaluator.php';

$db = new DB();
$mysqli = $db->getConnection();
$evaluator = new MetricsEvaluator($mysqli);

$key = 'system.cpu.load';
$val = 3.11;
$metric_id = 1;

echo "Evaluating $key = $val (ID: $metric_id)...\n";
$status = $evaluator->evaluate($key, $val, null, $metric_id);
echo "Result Status: $status\n";

$res = $mysqli->query("SELECT * FROM alert_rules WHERE metric_id = 1 OR metric_pattern = 'system.cpu.load' ORDER BY priority DESC");
echo "Rules matching:\n";
while($r = $res->fetch_assoc()) {
    echo "- ID: {$r['id']}, Pattern: {$r['metric_pattern']}, OP: {$r['operator']}, Val: {$r['threshold_value']}, Cat: {$r['alert_category']}, Prio: {$r['priority']}\n";
}
?>
