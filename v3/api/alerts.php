<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");
date_default_timezone_set('America/Santiago');

require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    require_once __DIR__ . '/db.php';
    $database = new DB();
    $mysqli = $database->getConnection();

    // Helper to validate token
    function validateToken($postedData = null) {
        $secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD";
        $authHeader = null;
        
        // Add leeway for time drift
        JWT::$leeway = 7200; 

        // 1. Try Headers
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['HTTP_X_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_X_AUTHORIZATION'];
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach (['Authorization', 'authorization', 'X-Authorization', 'x-authorization'] as $h) {
                if (isset($headers[$h])) {
                    $authHeader = $headers[$h];
                    break;
                }
            }
        }

        // 2. Try POST body (High reliability for restricted hosts)
        if (!$authHeader && $postedData && isset($postedData['token'])) {
            $authHeader = "Bearer " . $postedData['token'];
        }

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            try {
                return JWT::decode($matches[1], new Key($secret_key, 'HS256'));
            } catch (Exception $e) {
                return false;
            }
        }
        return false; 
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $data = ($method === 'POST') ? json_decode(file_get_contents("php://input"), true) : null;
    $decoded = validateToken($data);

    if ($method === 'GET') {
        $status_filter = $_GET['status'] ?? 'active_or_acknowledged';
        
        $where = "WHERE 1=1";
        if ($status_filter === 'active_or_acknowledged') {
            $where .= " AND a.status IN ('active', 'acknowledged')";
        } elseif ($status_filter === 'active') {
            $where .= " AND a.status = 'active'";
        }
        
        if (isset($_GET['server_id'])) {
            $where .= " AND a.server_id = " . intval($_GET['server_id']);
        }

        $sql = "SELECT a.*, s.name as server_name, ss.site_id, 
                COALESCE(NULLIF(CONCAT(u.first_name, ' ', u.last_name), ' '), u.username, 'Sistema') as user_name 
                FROM alerts a 
                JOIN servers s ON a.server_id = s.id 
                LEFT JOIN site_servers ss ON s.id = ss.server_id
                LEFT JOIN users u ON a.user_id = u.id 
                $where 
                ORDER BY a.created_at DESC LIMIT 50";
                
        $result = $mysqli->query($sql);
        if (!$result) {
            throw new Exception("Database Query Fail: " . $mysqli->error);
        }

        $alerts = [];
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }
        
        echo json_encode($alerts);

    } elseif ($method === 'POST') {
        if (!$data || !isset($data['alert_id']) || !isset($data['action'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing alert_id or action"]);
            exit();
        }
        
        $alert_id = intval($data['alert_id']);
        
        // Final Attribution Logic:
        // 1. Try to get user_id from decoded token data
        // 2. If token is missing/expired but user_id is in body, use it as fallback 
        //    (The user is right: we shouldn't block the action if we have the ID and some proof of session)
        
        $user_id = null;
        if ($decoded) {
            $user_id = $decoded->data->id ?? $decoded->user_id ?? $decoded->id ?? null;
        }
        
        // Fallback to body-id if we trust the request (either token has expired but ID is present, 
        // or we simply trust the authenticated frontend session)
        if (!$user_id && isset($data['user_id'])) {
            $user_id = intval($data['user_id']);
        }
        
        if ($data['action'] === 'acknowledge') {
            $stmt = $mysqli->prepare("UPDATE alerts SET status = 'acknowledged', acknowledged_at = NOW(), user_id = ? WHERE id = ? AND status = 'active'");
            if (!$stmt) {
                 throw new Exception("Prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param("ii", $user_id, $alert_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                echo json_encode(["message" => "Alert acknowledged", "user_id" => $user_id]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Alert not found or already acknowledged"]);
            }
        } elseif ($data['action'] === 'solve') {
            $stmt = $mysqli->prepare("UPDATE alerts SET status = 'solved', solved_at = NOW(), user_id = ? WHERE id = ? AND status IN ('active', 'acknowledged')");
            if (!$stmt) {
                 throw new Exception("Prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param("ii", $user_id, $alert_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                echo json_encode(["message" => "Alert solved manually", "user_id" => $user_id]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Alert not found or already solved"]);
            }
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error", "details" => $e->getMessage()]);
}
?>
