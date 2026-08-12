<?php
require 'api/db.php';
$db = new DB();
$mysqli = $db->getConnection();

$sf_ids = [];
$all_users_q = "SELECT u.salesforce_user_id 
                FROM monitoring_system.shift_group_members sgm
                JOIN monitoring_system.users u ON u.id = sgm.user_id
                WHERE u.salesforce_user_id IS NOT NULL AND u.salesforce_user_id != ''";
$sf_res = $mysqli->query($all_users_q);
if ($sf_res) {
    while ($sf_row = $sf_res->fetch_assoc()) {
        $sf_ids[] = "'" . $mysqli->real_escape_string($sf_row['salesforce_user_id']) . "'";
    }
}
$in_clause = count($sf_ids) > 0 ? implode(',', $sf_ids) : "'NO_MATCH'";

$stats_q = "SELECT Status, COUNT(*) as count 
            FROM rmmsalesforce.sf_cases 
            WHERE OwnerId IN ($in_clause) AND IsDeleted = 0 
            GROUP BY Status";
$stats_res = $mysqli->query($stats_q);
$total = 0;
if ($stats_res) {
    while ($row = $stats_res->fetch_assoc()) {
        $total += (int)$row['count'];
    }
}
echo "Total tickets: " . $total . "\n";
