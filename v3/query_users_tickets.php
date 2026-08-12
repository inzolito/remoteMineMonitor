<?php
require_once __DIR__ . '/api/db.php';
$db = new DB();
$mysqli = $db->getConnection();

$q = "SELECT u.Name as OwnerName, COUNT(c.Id) as TicketCount
      FROM rmmsalesforce.sf_cases c
      LEFT JOIN rmmsalesforce.sf_users u ON c.OwnerId = u.Id
      WHERE c.IsDeleted = 0
      GROUP BY u.Name
      ORDER BY TicketCount DESC";

$res = $mysqli->query($q);
if (!$res) {
    echo "Error: " . $mysqli->error;
    exit;
}

echo "OwnerName | TicketCount\n";
echo "-----------------------\n";
while ($row = $res->fetch_assoc()) {
    echo str_pad($row['OwnerName'] ?? 'Sin Asignar', 30) . " | " . $row['TicketCount'] . "\n";
}
?>
