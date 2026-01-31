<?php
require_once __DIR__ . '/db.php';
$database = new DB();
$mysqli = $database->getConnection();

echo "Optimizing Metrics Schema...\n";

// 1. Create index on metrics.name for faster lookups
echo "1. Creating index on metrics.name...\n";
$mysqli->query("CREATE INDEX IF NOT EXISTS idx_metrics_name ON metrics(name)");

// 2. Add unique constraint on server_app_metrics (server_id, metric_id)
echo "2. Adding unique constraint on server_app_metrics...\n";
$result = $mysqli->query("SHOW INDEX FROM server_app_metrics WHERE Key_name = 'unique_server_metric'");
if ($result->num_rows === 0) {
    $mysqli->query("ALTER TABLE server_app_metrics ADD UNIQUE KEY unique_server_metric (server_id, metric_id)");
}

// 3. Create a metric name cache helper function (stored procedure)
echo "3. Creating helper function for metric_id lookup...\n";
$mysqli->query("DROP FUNCTION IF EXISTS get_metric_id");
$mysqli->query("
CREATE FUNCTION get_metric_id(metric_name VARCHAR(100))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE metric_id INT;
    SELECT id INTO metric_id FROM metrics WHERE name = metric_name LIMIT 1;
    RETURN metric_id;
END
");

echo "Optimization Complete.\n";
