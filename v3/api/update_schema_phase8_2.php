<?php
require_once 'db.php';

$database = new DB();
$conn = $database->getConnection();

$cols = [
    "ADD COLUMN port VARCHAR(10) AFTER ip_address",
    "ADD COLUMN protocol VARCHAR(10) AFTER port",
    "ADD COLUMN db_engine VARCHAR(50) AFTER db_password"
];

foreach ($cols as $col_sql) {
    $sql = "ALTER TABLE servers " . $col_sql;
    // Simple check to avoid error if exists (imperfect but works for this context)
    try {
        if ($conn->query($sql) === TRUE) {
            echo "Executed: $sql\n";
        } else {
             // Ignore duplicate column errors
             if (strpos($conn->error, 'Duplicate column') === false) {
                 echo "Error: " . $conn->error . "\n";
             } else {
                 echo "Column already exists.\n";
             }
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

echo "Schema update for Phase 8.2 complete.\n";
?>
