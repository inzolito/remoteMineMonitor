<?php
require_once __DIR__ . '/db.php';
$db = new DB();
$conn = $db->getConnection();

$sql = "ALTER TABLE servers ADD COLUMN last_seen DATETIME NULL DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Column 'last_seen' added successfully.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
