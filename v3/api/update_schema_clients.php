<?php
require_once 'db.php';

$database = new DB();
$conn = $database->getConnection();

$updates = [
    "ALTER TABLE sites ADD COLUMN IF NOT EXISTS contract_number VARCHAR(100) AFTER logo_url",
    "ALTER TABLE sites ADD COLUMN IF NOT EXISTS contract_validity VARCHAR(100) AFTER contract_number"
];

foreach ($updates as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Successfully executed: $sql\n";
    } else {
        echo "Error executing: $sql - " . $conn->error . "\n";
    }
}

echo "Database schema updated successfully for Clients module.\n";
?>
