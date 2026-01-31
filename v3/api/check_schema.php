<?php
require_once __DIR__ . '/db.php';
$db = new DB();
$conn = $db->getConnection();
$result = $conn->query("DESCRIBE servers");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
