<?php
require_once 'db.php';

$database = new DB();
$conn = $database->getConnection();

// Check if column exists first
$check = $conn->query("SHOW COLUMNS FROM servers LIKE 'is_deleted'");
if ($check->num_rows == 0) {
    $sql = "ALTER TABLE servers ADD COLUMN is_deleted INT DEFAULT 0 AFTER status";
    if ($conn->query($sql) === TRUE) {
        echo "Added is_deleted to servers.\n";
    } else {
        echo "Error adding is_deleted: " . $conn->error . "\n";
    }
} else {
    echo "Column is_deleted already exists.\n";
}

echo "Schema fixed.\n";
?>
