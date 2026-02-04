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
$database = new DB();
$mysqli = $database->getConnection();

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

    $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaStr);
    if (!$fecha) return [
        'minutos' => null, 
        'fecha' => null, 
        'status' => 'inactive',
        'log' => $tail
    ];

    $ahora = new DateTime();
    $diff = $ahora->getTimestamp() - $fecha->getTimestamp();
    $minutosPasados = floor($diff / 60);
    $status = ($minutosPasados <= 10) ? 'ok' : 'inactive';

    return [
        'minutos' => $minutosPasados,
        'fecha' => $fechaStr,
        'status' => $status,
        'log' => $tail
    ];
}

/**
 * Leer últimos tickets del CSV
 */
function get_recent_tickets($limit = 15) {
    $file = "/var/www/globalData/tickets.csv";
    if (!file_exists($file)) return [];

    $rows = [];
    if (($handle = fopen($file, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 1000, ",");
        if ($headers) {
            // Clean BOM from first header
            $headers[0] = str_replace(["\xEF\xBB\xBF", "\xFE\xFF", "\xFF\xFE"], '', $headers[0]);
        }
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $record = [];
            foreach($headers as $index => $header) {
                $record[$header] = $data[$index] ?? '';
            }
            
            $status = $record['Status'] ?? '';
            $account = trim($record['AccountName'] ?? 'N/A');

            // Filtro para NO mostrar tickets Closed ni Resolved
            // Y filtrar tickets sin cliente real (N/A o Hexagon Mining)
            if ($status !== 'Closed' && 
                $status !== 'Resolved' &&
                strcasecmp($account, 'N/A') !== 0 &&
                strcasecmp($account, 'Hexagon Mining') !== 0) {
                $rows[] = $record;
            }
        }
        fclose($handle);
    }

    // Ordenar por fecha de creación (CreatedDate) descendente (más reciente primero)
    usort($rows, function($a, $b) {
        $dateA = strtotime($a['CreatedDate'] ?? 0);
        $dateB = strtotime($b['CreatedDate'] ?? 0);
        return $dateB - $dateA;
    });

    // Retornar los primeros registros
    return array_slice($rows, 0, $limit);
}

/**
 * Obtener cantidad de scripts de monitoreo activos
 */
function get_running_scripts() {
    // Busca procesos python que contengan 'monitoreoRemoto' (el dir de los agentes)
    $cmd = "ps aux | grep 'monitoreoRemoto' | grep -v 'grep' | wc -l";
    return intval(trim(shell_exec($cmd)));
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

// Consolidar respuesta
$response = [
    'services' => [
        'oas' => check_service_v3('oasgraf.log'),
        'salesforce' => check_service_v3('salesforce.log')
    ],
    'alerts' => get_active_alerts_v3($mysqli),
    'tickets' => get_recent_tickets(100)
];

echo json_encode($response);
