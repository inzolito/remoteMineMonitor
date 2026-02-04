<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes/MetricsEvaluator.php';

$database = new DB();
$mysqli = $database->getConnection();
$evaluator = new MetricsEvaluator($mysqli);

echo "Starting Migration V2...\n";

// 1. Seed Alert Rules
echo "Seeding Alert Rules...\n";
$mysqli->query("TRUNCATE TABLE alert_rules");

$rules = [
    // Metric Pattern, Type, Warning, Danger
    ['system.disk.percent', 'threshold', '> 70', '> 85'],
    ['system.ram.percent', 'threshold', '> 50', '> 70'],
    ['system.load.average', 'threshold', '> 3', '> 5'],
    ['app.backup.daily', 'freshness', '20h', '25h'], // 25h danger
    ['app.backup.hourly', 'freshness', '1h', '2h'],
    ['app.connectivity.equipos', 'threshold', null, '<= 0'], // Danger if 0 (or <= 0)
    ['app.connectivity.station', 'regex', null, 'no response'], // Danger if 'no response'
    ['system.services.%', 'regex', 'pending', 'stopped|failed|error|offline'] // generic service check
];

$stmt = $mysqli->prepare("INSERT INTO alert_rules (metric_pattern, rule_type, warning_threshold, danger_threshold) VALUES (?, ?, ?, ?)");
foreach ($rules as $rule) {
    echo "Adding rule for {$rule[0]}\n";
    $stmt->bind_param("ssss", $rule[0], $rule[1], $rule[2], $rule[3]);
    $stmt->execute();
}

// Reload rules in evaluator
$evaluator = new MetricsEvaluator($mysqli);

// 2. Migrate System Metrics (flatten to key-value)
echo "Migrating Legacy System Metrics...\n";
// Get latest metric per server
$sql = "SELECT sm.* FROM server_metrics sm 
        INNER JOIN (SELECT server_id, MAX(created_at) as max_date FROM server_metrics GROUP BY server_id) latest 
        ON sm.server_id = latest.server_id AND sm.created_at = latest.max_date";
$result = $mysqli->query($sql);
$metrics = $result->fetch_all(MYSQLI_ASSOC);

$insertStmt = $mysqli->prepare("INSERT INTO server_app_metrics (server_id, metric_key, metric_value, status, last_updated) VALUES (?, ?, ?, ?, NOW())");

foreach ($metrics as $row) {
    $sid = $row['server_id'];
    
    // Calculate percents
    $diskPct = ($row['disk_total'] > 0) ? ($row['disk_usage'] / $row['disk_total'] * 100) : 0;
    $ramPct = ($row['ram_total'] > 0) ? ($row['ram_usage'] / $row['ram_total'] * 100) : 0;
    
    $toMigrate = [
        'system.disk.percent' => round($diskPct, 2),
        'system.ram.percent' => round($ramPct, 2),
        'system.load.average' => $row['load_average']
    ];

    foreach ($toMigrate as $key => $val) {
        $status = $evaluator->evaluate($key, $val);
        $insertStmt->bind_param("isss", $sid, $key, $val, $status);
        $insertStmt->execute();
    }
}

// 3. Update Existing App Metrics (Backups/Connectivity) to normalize keys
echo "Normalizing App Metrics keys...\n";
// The current keys are 'daily', 'hourly', 'repc', 'estacionBase'.
// We should map them to our standard 'app.backup.daily', etc. so the rules apply.

$keyMap = [
    'daily' => 'app.backup.daily',
    'hourly' => 'app.backup.hourly',
    'repc' => 'app.connectivity.equipos',
    'estacionBase' => 'app.connectivity.station'
];

foreach ($keyMap as $oldKey => $newKey) {
    // Select existing
    $res = $mysqli->query("SELECT id, server_id, metric_value FROM server_app_metrics WHERE metric_key = '$oldKey'");
    while ($row = $res->fetch_assoc()) {
        $status = $evaluator->evaluate($newKey, $row['metric_value']);
        // Update key and status
        $uStmt = $mysqli->prepare("UPDATE server_app_metrics SET metric_key = ?, status = ? WHERE id = ?");
        $uStmt->bind_param("ssi", $newKey, $status, $row['id']);
        $uStmt->execute();
    }
}

echo "Migration Complete.\n";
?>
