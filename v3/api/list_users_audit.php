<?php
require_once __DIR__ . '/db.php';
$database = new DB();
$mysqli = $database->getConnection();
$query = "SELECT u.id, u.username, u.first_name, u.last_name, p.name AS role 
          FROM users u 
          LEFT JOIN permissions p ON u.permission_id = p.id";
$result = $mysqli->query($query);
if (!$result) {
    die("Query failed: " . $mysqli->error);
}
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} | User: {$row['username']} | Name: {$row['first_name']} {$row['last_name']} | Role: " . ($row['role'] ?? 'NONE') . "\n";
}
?>
