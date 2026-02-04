<?php
require_once 'db.php';

$database = new DB();
$conn = $database->getConnection();

function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

$columns = [
    'contract_number' => "VARCHAR(100) AFTER logo_url",
    'contract_validity' => "VARCHAR(100) AFTER contract_number",
    'contract_manager' => "VARCHAR(500) AFTER contract_validity",
    'dispatch_contact' => "VARCHAR(500) AFTER contract_manager",
    'dispatch_phone' => "VARCHAR(250) AFTER dispatch_contact",
    'onsite_engineers' => "VARCHAR(500) AFTER dispatch_phone" // Keeping this just in case, though likely exists
];

foreach ($columns as $col => $def) {
    if (!columnExists($conn, 'sites', $col)) {
        $sql = "ALTER TABLE sites ADD COLUMN $col $def";
        if ($conn->query($sql) === TRUE) {
            echo "Added column: $col\n";
        } else {
            echo "Error adding $col: " . $conn->error . "\n";
        }
    } else {
        echo "Column already exists: $col\n";
    }
}

echo "Schema fix complete.\n";
?>
