<?php
require_once '/var/www/monitoreoLaboratorio/v3/api/db.php';
$db = new DB();
$mysqli = $db->getConnection();

$query = "SELECT c.Id, c.CaseNumber, c.Status, c.CreatedDate, c.ClosedDate
          FROM rmmsalesforce.sf_cases c
          WHERE LOWER(c.Status) = 'closed'
          ORDER BY c.ClosedDate DESC LIMIT 5";
$res = $mysqli->query($query);
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
