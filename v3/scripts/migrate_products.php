<?php
require_once __DIR__ . '/../api/db.php';

$db = (new DB())->getConnection();

echo "Starting migration...\n";

// 1. Create system_products table
$sql1 = "CREATE TABLE IF NOT EXISTS system_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(50) NOT NULL,
    color VARCHAR(50) NOT NULL,
    default_os VARCHAR(50) DEFAULT 'Linux',
    default_port VARCHAR(20) DEFAULT '22',
    default_protocol VARCHAR(20) DEFAULT 'SSH',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!$db->query($sql1)) {
    die("Error creating system_products: " . $db->error . "\n");
}
echo "Table system_products ready.\n";

// 2. Insert initial products if empty
$check = $db->query("SELECT COUNT(*) as count FROM system_products");
$row = $check->fetch_assoc();
if ($row['count'] == 0) {
    $products = [
        ['Servidor FMS', 'fms', 'Server', 'orange', 'Linux', '22', 'SSH'],
        ['Estacion Base', 'base', 'Globe', 'blue', 'Linux', '22', 'SSH'],
        ['Tunel', 'tunel', 'Box', 'purple', 'Linux', '8080', 'HTTP'],
        ['Pivote', 'pivote', 'ShieldCheck', 'indigo', 'Linux', '22', 'SSH'],
        ['Servidor CAS', 'cas', 'Database', 'emerald', 'Windows', '3389', 'RDP'],
        ['SQL Server', 'sql', 'Database', 'red', 'Windows', '1433', 'TDS'],
        ['Servidor Jview', 'jview', 'Globe', 'cyan', 'Linux', '80', 'HTTP']
    ];

    $stmt = $db->prepare("INSERT INTO system_products (name, code, icon, color, default_os, default_port, default_protocol) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($products as $p) {
        $stmt->bind_param("sssssss", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]);
        $stmt->execute();
    }
    echo "Initial 7 products inserted.\n";
} else {
    echo "system_products already has data (" . $row['count'] . " rows).\n";
}

// 3. Add primary_product_id to servers if not exists
$colCheck = $db->query("SHOW COLUMNS FROM servers LIKE 'primary_product_id'");
if ($colCheck->num_rows == 0) {
    $alter = "ALTER TABLE servers ADD COLUMN primary_product_id INT NULL AFTER server_type_id, ADD CONSTRAINT fk_servers_primary_product FOREIGN KEY (primary_product_id) REFERENCES system_products(id) ON DELETE SET NULL";
    if (!$db->query($alter)) {
        die("Error adding primary_product_id: " . $db->error . "\n");
    }
    echo "Added primary_product_id column with FK to servers.\n";
} else {
    echo "Column primary_product_id already exists in servers.\n";
}

// 4. Create server_secondary_products table
$sql4 = "CREATE TABLE IF NOT EXISTS server_secondary_products (
    server_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (server_id, product_id),
    CONSTRAINT fk_ssp_server FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    CONSTRAINT fk_ssp_product FOREIGN KEY (product_id) REFERENCES system_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!$db->query($sql4)) {
    die("Error creating server_secondary_products: " . $db->error . "\n");
}
echo "Table server_secondary_products ready.\n";

echo "Migration finished successfully!\n";
