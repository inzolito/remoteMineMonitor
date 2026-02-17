<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD";

// JWT Authentication
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $authHeader;
}

if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$jwt = $matches[1];

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
} catch (\Firebase\JWT\ExpiredException $e) {
    // Special handling for 'maik' user to allow expired tokens (matching admin.php logic)
    $tks = explode('.', $jwt);
    if (count($tks) === 3) {
        $payload = json_decode(base64_decode(strTr($tks[1], '-_', '+/')));
        if (isset($payload->data->username) && $payload->data->username === 'maik') {
            $decoded = $payload;
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Token expired']);
            exit;
        }
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

$username = $decoded->data->username ?? '';

// Only SuperAdmin can access raw metrics
if ($username !== 'maik') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - SuperAdmin only']);
    exit;
}

// Database connection
$mysqli = new mysqli('localhost', 'jigsaw', 'Jigsaw1', 'monitoring_system');
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// GET: Fetch raw metrics for a server
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $serverId = intval($_GET['server_id'] ?? 0);
    
    if (!$serverId) {
        http_response_code(400);
        echo json_encode(['error' => 'server_id is required']);
        exit;
    }
    
    $metrics = [];
    
    // Fetch a TRACE LOG of the last 150 entries from server_metric_values
    // This allows debugging the real-time stream of incoming data
    $query = "SELECT m.name as metric_key, mv.value as metric_value, mv.status, mv.created_at as updated_at 
              FROM server_metric_values mv
              JOIN metrics m ON mv.metric_id = m.id
              WHERE mv.server_id = $serverId 
              ORDER BY mv.created_at DESC 
              LIMIT 150";
    
    $result = $mysqli->query($query);
    if (!$result) {
        error_log("Trace Query Failed: " . $mysqli->error);
    }
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $metrics[] = [
                'metric_key' => $row['metric_key'],
                'metric_value' => $row['metric_value'],
                'status' => $row['status'],
                'updated_at' => $row['updated_at'],
                'type' => strpos($row['metric_key'], 'system.') === 0 ? 'system' : 'app'
            ];
        }
    }
    
    echo json_encode($metrics);
}

$mysqli->close();
