<?php
require_once __DIR__ . '/db.php';
$database = new DB();
$mysqli = $database->getConnection();

echo "Adding Command Fallback Support...\n";

// 1. Add priority column
echo "1. Adding 'priority' column to remote_commands...\n";
$res = $mysqli->query("SHOW COLUMNS FROM remote_commands LIKE 'priority'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE remote_commands ADD COLUMN priority INT DEFAULT 1 AFTER metric_id");
}

// 2. Add timeout_seconds column
echo "2. Adding 'timeout_seconds' column to remote_commands...\n";
$res = $mysqli->query("SHOW COLUMNS FROM remote_commands LIKE 'timeout_seconds'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE remote_commands ADD COLUMN timeout_seconds INT DEFAULT 30 AFTER priority");
}

// 3. Add retry_count column for monitoring
echo "3. Adding 'retry_count' column to remote_commands...\n";
$res = $mysqli->query("SHOW COLUMNS FROM remote_commands LIKE 'retry_count'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE remote_commands ADD COLUMN retry_count INT DEFAULT 0 AFTER timeout_seconds");
}

// 4. Update existing commands to have priority = 1
echo "4. Setting default priority for existing commands...\n";
$mysqli->query("UPDATE remote_commands SET priority = 1 WHERE priority IS NULL OR priority = 0");

// 5. Create index for efficient queries
echo "5. Creating index on (metric_id, priority)...\n";
$mysqli->query("CREATE INDEX IF NOT EXISTS idx_metric_priority ON remote_commands(metric_id, priority)");

echo "Command Fallback Support Added Successfully.\n";
