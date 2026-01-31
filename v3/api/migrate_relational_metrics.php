<?php
require_once __DIR__ . '/db.php';
$database = new DB();
$mysqli = $database->getConnection();

echo "Starting Database Migration to Relational Metrics...\n";

// 1. Create the 'metrics' catalog table
echo "1. Creating 'metrics' table...\n";
$mysqli->query("CREATE TABLE IF NOT EXISTS metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Add 'metric_id' to remote_commands
echo "2. Adding 'metric_id' to 'remote_commands'...\n";
$res = $mysqli->query("SHOW COLUMNS FROM remote_commands LIKE 'metric_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE remote_commands ADD COLUMN metric_id INT AFTER name");
    $mysqli->query("ALTER TABLE remote_commands ADD COLUMN os_family VARCHAR(20) DEFAULT 'linux' AFTER metric_id");
}

// 3. Add 'metric_id' to server_app_metrics
echo "3. Adding 'metric_id' to 'server_app_metrics'...\n";
$res = $mysqli->query("SHOW COLUMNS FROM server_app_metrics LIKE 'metric_id'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE server_app_metrics ADD COLUMN metric_id INT AFTER server_id");
}

// 4. Create 'server_metrics_config'
echo "4. Creating 'server_metrics_config' table...\n";
$mysqli->query("CREATE TABLE IF NOT EXISTS server_metrics_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    metric_id INT NOT NULL,
    frequency INT DEFAULT 60,
    is_enabled TINYINT(1) DEFAULT 1,
    last_run TIMESTAMP NULL,
    UNIQUE KEY (server_id, metric_id),
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (metric_id) REFERENCES metrics(id) ON DELETE CASCADE
)");

// 5. Populate Metrics Catalog from existing data
echo "5. Populating 'metrics' catalog...\n";
$existingKeys = [];

// From server_app_metrics
$res = $mysqli->query("SELECT DISTINCT metric_key FROM server_app_metrics");
while ($row = $res->fetch_assoc()) {
    $existingKeys[] = $row['metric_key'];
}

// From remote_commands
$res = $mysqli->query("SELECT DISTINCT name FROM remote_commands");
while ($row = $res->fetch_assoc()) {
    $existingKeys[] = $row['name'];
}

$existingKeys = array_unique($existingKeys);
$stmt = $mysqli->prepare("INSERT IGNORE INTO metrics (name, display_name) VALUES (?, ?)");
foreach ($existingKeys as $key) {
    $displayName = ucwords(str_replace(['.', '_'], ' ', $key));
    $stmt->bind_param("ss", $key, $displayName);
    $stmt->execute();
}

// 6. Link remote_commands to metric_id
echo "6. Linking 'remote_commands' to 'metric_id'...\n";
$mysqli->query("UPDATE remote_commands rc JOIN metrics m ON rc.name = m.name SET rc.metric_id = m.id");

// 7. Link server_app_metrics to metric_id
echo "7. Linking 'server_app_metrics' to 'metric_id'...\n";
$mysqli->query("UPDATE server_app_metrics sam JOIN metrics m ON sam.metric_key = m.name SET sam.metric_id = m.id");

// 8. Auto-configure server_metrics_config based on current server_app_metrics
echo "8. Auto-configuring 'server_metrics_config'...\n";
$mysqli->query("INSERT IGNORE INTO server_metrics_config (server_id, metric_id) 
                SELECT DISTINCT server_id, metric_id FROM server_app_metrics WHERE metric_id IS NOT NULL");

echo "Migration Complete.\n";
