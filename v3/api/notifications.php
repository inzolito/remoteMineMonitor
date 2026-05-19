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
        $query = "SELECT c.Id, c.CaseNumber, c.Subject, c.Status, c.CreatedDate, 
                         a.internal_faena_alias as Faena, a.Name as AccountName
                  FROM rmmsalesforce.sf_cases c
                  LEFT JOIN rmmsalesforce.sf_accounts a ON c.AccountId = a.Id
                  LEFT JOIN rmmsalesforce.sf_users u ON c.OwnerId = u.Id
                  WHERE (c.OwnerId = '00G1I00000249DwUAI' OR u.Name = 'South American Support Q')
                    AND LOWER(c.Status) != 'closed'
                    AND c.IsDeleted = 0
                  ORDER BY c.CreatedDate DESC
                  LIMIT 50";
                  
        $result = $mysqli->query($query);
        if (!$result) {
            throw new Exception("Database Query Fail: " . $mysqli->error);
        }

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => 'sf_' . $row['Id'],
                'case_id' => $row['Id'],
                'case_number' => $row['CaseNumber'],
                'title' => 'Caso de Soporte en Cola',
                'message' => 'El caso #' . $row['CaseNumber'] . ' (' . ($row['Faena'] ?: $row['AccountName'] ?: 'Global') . ') está asignado a la cola de soporte.',
                'subject' => $row['Subject'],
                'status' => $row['Status'],
                'faena' => $row['Faena'] ?: $row['AccountName'] ?: 'Global',
                'created_at' => $row['CreatedDate'],
                'type' => 'salesforce_ticket'
            ];
        }
        
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
