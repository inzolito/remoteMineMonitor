<?php
require_once __DIR__ . '/db.php';

$database = new DB();
$mysqli = $database->getConnection();

// Create site_contacts table
$sql = "CREATE TABLE IF NOT EXISTS site_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL COMMENT 'contract_admin, dispatch, onsite_engineer',
    phone VARCHAR(100),
    email VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
)";

if ($mysqli->query($sql) === TRUE) {
    echo "Table 'site_contacts' created successfully.\n";
} else {
    echo "Error creating table: " . $mysqli->error . "\n";
}

// Optional: Migrate existing data if any (Best effort parsing)
// logic: Select all sites, check contract_manager, dispatch_contact, etc.
// Not strictly requested but helpful. For now, just creating table.
?>
