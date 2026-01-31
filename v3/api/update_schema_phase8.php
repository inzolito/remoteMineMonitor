<?php
require_once 'db.php';

$database = new DB();
$conn = $database->getConnection();

// Add is_deleted to servers
$sql1 = "ALTER TABLE servers ADD COLUMN IF NOT EXISTS is_deleted INT DEFAULT 0 AFTER status";
if ($conn->query($sql1) === TRUE) {
    echo "Added is_deleted to servers.\n";
} else {
    echo "Error adding is_deleted: " . $conn->error . "\n";
}

// Modify sites columns to TEXT to support JSON
// We will use these columns to store JSON arrays:
// dispatch_contact: [{"name": "Name", "phone": "123"}]
// onsite_engineers: [{"name": "Name", "phone": "123"}]
// Note: We might leave dispatch_phone unused if we bundle phone into the contact object, or use it for general site phone.
// User said "cada uno tiene su telefono", so bundling is better.

$cols_to_text = ['dispatch_contact', 'onsite_engineers'];
foreach ($cols_to_text as $col) {
    $sql = "ALTER TABLE sites MODIFY COLUMN $col TEXT";
    if ($conn->query($sql) === TRUE) {
        echo "Modified $col to TEXT.\n";
    } else {
        echo "Error modifying $col: " . $conn->error . "\n";
    }
}

echo "Schema update for Phase 8.1 complete.\n";
?>
