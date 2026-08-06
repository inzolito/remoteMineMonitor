<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");
date_default_timezone_set('America/Santiago');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    require_once __DIR__ . '/db.php';
    $database = new DB();
    $mysqli = $database->getConnection();

    require_once __DIR__ . '/auth_helper.php';
    $auth = require_auth($mysqli);
    $decoded = $auth['decoded'];

    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'GET') {
        $notifications = [];

        // 1. South American Support Q Tickets
        $query1 = "SELECT c.Id, c.CaseNumber, c.Subject, c.Status, c.CreatedDate, 
                         a.internal_faena_alias as Faena, a.Name as AccountName
                  FROM rmmsalesforce.sf_cases c
                  LEFT JOIN rmmsalesforce.sf_accounts a ON c.AccountId = a.Id
                  LEFT JOIN rmmsalesforce.sf_users u ON c.OwnerId = u.Id
                  WHERE (c.OwnerId = '00G1I00000249DwUAI' OR u.Name = 'South American Support Q')
                    AND LOWER(c.Status) != 'closed'
                    AND c.IsDeleted = 0
                  ORDER BY c.CreatedDate DESC";
                  
        $result1 = $mysqli->query($query1);
        if ($result1) {
            while ($row = $result1->fetch_assoc()) {
                $notifications[] = [
                    'id' => 'sf_' . $row['Id'],
                    'case_id' => $row['Id'],
                    'case_number' => $row['CaseNumber'],
                    'title' => 'Caso en South American Support',
                    'message' => 'El caso #' . $row['CaseNumber'] . ' (' . ($row['Faena'] ?: $row['AccountName'] ?: 'Global') . ') está esperando asignación en la cola principal.',
                    'subject' => $row['Subject'],
                    'status' => $row['Status'],
                    'faena' => $row['Faena'] ?: $row['AccountName'] ?: 'Global',
                    'created_at' => $row['CreatedDate'],
                    'type' => 'salesforce_ticket'
                ];
            }
        }

        // 2. Unassigned tickets from Inactive Shift (using new shift_cycles table)
        $cycle_res = $mysqli->query(
            "SELECT active_group_id FROM shift_cycles WHERE end_datetime IS NULL ORDER BY start_datetime DESC LIMIT 1"
        );
        if ($cycle_res && $cycle_res->num_rows > 0) {
            $cycle_row     = $cycle_res->fetch_assoc();
            $inactive_shift = intval($cycle_row['active_group_id']) === 1 ? 2 : 1;

            $query2 = "SELECT c.Id, c.CaseNumber, c.Subject, c.Status, c.CreatedDate, 
                             a.internal_faena_alias as Faena, a.Name as AccountName
                      FROM rmmsalesforce.sf_cases c
                      LEFT JOIN rmmsalesforce.sf_accounts a ON c.AccountId = a.Id
                      WHERE (
                          c.OwnerId IN (
                              SELECT u2.salesforce_user_id COLLATE utf8mb4_unicode_ci
                              FROM monitoring_system.shift_group_members sgm
                              JOIN monitoring_system.users u2 ON u2.id = sgm.user_id
                              WHERE sgm.group_id = $inactive_shift
                                AND u2.salesforce_user_id IS NOT NULL AND u2.salesforce_user_id != ''
                          )
                      ) AND LOWER(c.Status) != 'closed' AND c.IsDeleted = 0
                      ORDER BY c.CreatedDate DESC";
            
            $result2 = $mysqli->query($query2);
            if ($result2) {
                while ($row = $result2->fetch_assoc()) {
                    $notifications[] = [
                        'id'         => 'shift_unassigned_' . $row['Id'],
                        'case_id'    => $row['Id'],
                        'case_number'=> $row['CaseNumber'],
                        'title'      => 'Ticket sin Asignar al Turno',
                        'message'    => 'El caso #' . $row['CaseNumber'] . ' (' . ($row['Faena'] ?: $row['AccountName'] ?: 'Global') . ') pertenece al turno anterior y sigue abierto. Por favor reasígnalo al turno actual.',
                        'subject'    => $row['Subject'],
                        'status'     => $row['Status'],
                        'faena'      => $row['Faena'] ?: $row['AccountName'] ?: 'Global',
                        'created_at' => $row['CreatedDate'],
                        'type'       => 'shift_ticket'
                    ];
                }
            }
        }

        // Sort combined notifications by CreatedDate descending
        usort($notifications, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        echo json_encode($notifications);
    } else {
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
}
?>
