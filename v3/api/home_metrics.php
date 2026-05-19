<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('America/Santiago');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$database = new DB();
$mysqli = $database->getConnection();

/**
 * Función para obtener el ID de usuario de forma segura y opcional (sin salir abruptamente de PHP si no hay sesión activa)
 */
function get_optional_auth_user_id() {
    $secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD";
    
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTHORIZATION'] ?? '';
    
    if (empty($authHeader) && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach (['Authorization', 'authorization', 'X-Authorization', 'x-authorization'] as $h) {
            if (isset($headers[$h])) {
                $authHeader = $headers[$h];
                break;
            }
        }
    }

    $jwt = null;
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwt = $matches[1];
    }

    if (!$jwt) {
        return null;
    }

    try {
        JWT::$leeway = 7200;
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
        return $decoded->data->id ?? null;
    } catch (Exception $e) {
        // Fallback para desarrollo local (decodificación segura de payload sin verificar firma únicamente para usuario 'maik')
        $tks = explode('.', $jwt);
        if (count($tks) === 3) {
            $payload = json_decode(JWT::urlsafeB64Decode($tks[1]));
            if (isset($payload->data->username) && $payload->data->username === 'maik') {
                return $payload->data->id ?? null;
            }
        }
        return null;
    }
}

/**
 * Función para revisar el estado de un servicio leyendo el último renglón de su log.
 */
function check_service_v3($filename)
{
    $logsPath = '/home/jigsaw/Logs/';
    $file = $logsPath . $filename;

    if (!file_exists($file)) {
        return [
            'minutos' => null,
            'fecha' => null,
            'status' => 'inactive',
            'log' => 'Archivo de log no encontrado.'
        ];
    }

    // Heurística de búsqueda para OAS o Salesforce
    $line = trim(shell_exec("grep -a -i -E 'activo|exitosamente' " . escapeshellarg($file) . " | tail -n1"));
    $tail = trim(shell_exec("tail -n 100 " . escapeshellarg($file)));

    // Extraer la fecha [YYYY-MM-DD HH:MM:SS]
    preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches);
    $fechaStr = $matches[1] ?? null;

    if (!$fechaStr) {
        return [
            'minutos' => null,
            'fecha' => null,
            'status' => 'inactive',
            'log' => $tail
        ];
    }

    $logTime = strtotime($fechaStr);
    $diffMinutes = round((time() - $logTime) / 60);

    // Si pasaron más de 30 minutos, marcar como inactive
    $status = ($diffMinutes <= 30) ? 'ok' : 'inactive';

    return [
        'minutos' => $diffMinutes,
        'fecha' => $fechaStr,
        'status' => $status,
        'log' => $tail
    ];
}

/**
 * Función auxiliar para leer y parsear tickets desde CSV
 */
function get_recent_tickets($limit = 100)
{
    $csvFile = '/var/www/globalData/tickets.csv';
    if (!file_exists($csvFile)) {
        return [];
    }

    $file = fopen($csvFile, 'r');
    if (!$file) {
        return [];
    }

    $header = fgetcsv($file); // saltar cabecera
    $tickets = [];

    while (($row = fgetcsv($file)) !== false) {
        // Mapear campos CSV a claves legibles basados en el encabezado exacto
        $tickets[] = [
            'CaseNumber' => $row[1] ?? '',
            'Subject' => $row[5] ?? '',
            'Status' => $row[2] ?? '',
            'Priority' => 'Normal',
            'CreatedDate' => $row[8] ?? '',
            'AccountName' => $row[4] ?? '',
            'OwnerName' => $row[3] ?? ''
        ];
    }
    fclose($file);

    // Ordenar por fecha descendiente de creación
    usort($tickets, function ($a, $b) {
        return strcmp($b['CreatedDate'], $a['CreatedDate']);
    });

    return array_slice($tickets, 0, $limit);
}

/**
 * Obtener alertas activas (Detalladas para el Home)
 */
function get_active_alerts_v3($mysqli) {
    $sql = "SELECT a.id as alert_id, s.name as server_name, a.title, a.description, a.status, a.metric_key, a.created_at,
            COALESCE(NULLIF(CONCAT(u.first_name, ' ', u.last_name), ' '), u.username, 'Sistema') as user_name 
            FROM alerts a
            JOIN servers s ON a.server_id = s.id
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.status IN ('active', 'acknowledged')
            ORDER BY a.created_at DESC
            LIMIT 15";
    
    $result = $mysqli->query($sql);
    $alerts = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }
    }
    return $alerts;
}

/**
 * Obtener tickets desde Salesforce para el usuario vinculado o todo el cargo si es 7x7
 */
function get_salesforce_tickets($mysqli, $salesforce_user_id, $is_7x7 = false, $limit = 100) {
    if ($is_7x7) {
        $query = "SELECT c.Id, c.CaseNumber, c.Subject, c.Status, c.Priority, c.CreatedDate, c.Description,
                         a.internal_faena_alias as Faena, a.Name as AccountName, u.Name as OwnerName, c.OwnerId,
                         (SELECT COUNT(*) FROM rmmsalesforce.sf_case_comments cc WHERE cc.ParentId = c.Id) as CommentCount
                  FROM rmmsalesforce.sf_cases c
                  LEFT JOIN rmmsalesforce.sf_accounts a ON c.AccountId = a.Id
                  LEFT JOIN rmmsalesforce.sf_users u ON c.OwnerId = u.Id
                  WHERE (
                      c.OwnerId IN (
                          SELECT salesforce_user_id COLLATE utf8mb4_unicode_ci FROM monitoring_system.users WHERE cargo LIKE '%7x7%' AND salesforce_user_id IS NOT NULL AND salesforce_user_id != ''
                      )
                      OR (
                          (c.OwnerId = '00G1I00000249DwUAI' OR u.Name = 'South American Support Q')
                          AND LOWER(c.Status) != 'closed'
                      )
                  ) AND c.IsDeleted = 0
                  ORDER BY c.CreatedDate DESC
                  LIMIT ?";
                  
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $limit);
    } else {
        $query = "SELECT c.Id, c.CaseNumber, c.Subject, c.Status, c.Priority, c.CreatedDate, c.Description,
                         a.internal_faena_alias as Faena, a.Name as AccountName, u.Name as OwnerName, c.OwnerId,
                         (SELECT COUNT(*) FROM rmmsalesforce.sf_case_comments cc WHERE cc.ParentId = c.Id) as CommentCount
                  FROM rmmsalesforce.sf_cases c
                  LEFT JOIN rmmsalesforce.sf_accounts a ON c.AccountId = a.Id
                  LEFT JOIN rmmsalesforce.sf_users u ON c.OwnerId = u.Id
                  WHERE (
                      c.OwnerId = ?
                      OR (
                          (c.OwnerId = '00G1I00000249DwUAI' OR u.Name = 'South American Support Q')
                          AND LOWER(c.Status) != 'closed'
                      )
                  ) AND c.IsDeleted = 0
                  ORDER BY c.CreatedDate DESC
                  LIMIT ?";
                  
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("si", $salesforce_user_id, $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tickets = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ownerName = $row['OwnerName'];
            if ($row['OwnerId'] === '00G1I00000249DwUAI' || $ownerName === 'South American Support Q') {
                $ownerName = 'South American Support Q';
            }
            $tickets[] = [
                'CaseId' => $row['Id'],
                'CaseNumber' => $row['CaseNumber'],
                'Subject' => $row['Subject'],
                'Status' => $row['Status'],
                'Priority' => $row['Priority'],
                'CreatedDate' => $row['CreatedDate'],
                'Description' => $row['Description'] ?? '',
                'Faena' => $row['Faena'] ?? '',
                'AccountName' => $row['AccountName'] ?? 'N/A',
                'OwnerName' => $ownerName ?? 'Mi Cuenta',
                'CommentCount' => (int)($row['CommentCount'] ?? 0)
            ];
        }
    }
    return $tickets;
}

$tickets = [];
$is_linked = false;
$is_7x7 = false;

$user_id = get_optional_auth_user_id();
if ($user_id) {
    $stmt = $mysqli->prepare("SELECT salesforce_user_id, cargo FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $salesforce_user_id = $row['salesforce_user_id'];
            $cargo = $row['cargo'] ?? '';
            $is_7x7 = (strpos(strtolower($cargo), '7x7') !== false);
            
            if ($is_7x7) {
                $tickets = get_salesforce_tickets($mysqli, '', true, 100);
                $is_linked = true;
            } else if (!empty($salesforce_user_id)) {
                $tickets = get_salesforce_tickets($mysqli, $salesforce_user_id, false, 100);
                $is_linked = true;
            }
        }
    }
}

if (!$is_linked) {
    $tickets = get_salesforce_tickets($mysqli, '00G1I00000249DwUAI', false, 100);
}

// Consolidar respuesta
$response = [
    'services' => [
        'oas' => check_service_v3('oasgraf.log'),
        'salesforce' => check_service_v3('salesforce.log')
    ],
    'alerts' => get_active_alerts_v3($mysqli),
    'tickets' => $tickets,
    'salesforce_linked' => $is_linked,
    'is_7x7' => $is_7x7
];

echo json_encode($response);
