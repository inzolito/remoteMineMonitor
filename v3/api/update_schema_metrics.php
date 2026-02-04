<?php
require_once __DIR__ . '/db.php';

$database = new DB();
$conn = $database->getConnection();

// 1. Server Metrics (Health: CPU, RAM, Disk, Uptime)
$sql_metrics = "CREATE TABLE IF NOT EXISTS server_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    cpu_usage FLOAT DEFAULT 0,
    ram_usage FLOAT DEFAULT 0,
    ram_total FLOAT DEFAULT 0,
    disk_usage FLOAT DEFAULT 0,
    disk_total FLOAT DEFAULT 0,
    uptime_seconds BIGINT DEFAULT 0,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
)";

if ($conn->query($sql_metrics) === TRUE) {
    echo "Table 'server_metrics' created/checked successfully.\n";
} else {
    echo "Error creating 'server_metrics': " . $conn->error . "\n";
}

// 2. Server Services (Processes/Services status)
$sql_services = "CREATE TABLE IF NOT EXISTS server_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    status VARCHAR(50) DEFAULT 'unknown', -- running, stopped, error
    message TEXT,
    last_checked DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
)";

if ($conn->query($sql_services) === TRUE) {
    echo "Table 'server_services' created/checked successfully.\n";
} else {
    echo "Error creating 'server_services': " . $conn->error . "\n";
}

// 3. Database Metrics (Connections, Queries, etc)
$sql_db_metrics = "CREATE TABLE IF NOT EXISTS server_db_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    active_connections INT DEFAULT 0,
    queries_per_second FLOAT DEFAULT 0,
    db_size_mb FLOAT DEFAULT 0,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
)";

if ($conn->query($sql_db_metrics) === TRUE) {
    echo "Table 'server_db_metrics' created/checked successfully.\n";
} else {
    echo "Error creating 'server_db_metrics': " . $conn->error . "\n";
}

// 4. Importers / App Specific Metrics (Generic key-value for FMS specific stuff)
$sql_app_metrics = "CREATE TABLE IF NOT EXISTS server_app_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    metric_key VARCHAR(100) NOT NULL, -- e.g. 'importer_delay', 'active_users'
    metric_value VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'ok', -- ok, warning, danger
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
)";

if ($conn->query($sql_app_metrics) === TRUE) {
    echo "Table 'server_app_metrics' created/checked successfully.\n";
} else {
    echo "Error creating 'server_app_metrics': " . $conn->error . "\n";
}

$conn->close();
?>
