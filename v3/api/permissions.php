<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

JWT::$leeway = 7200; // 2 hours leeway for time drift

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD";
$database = new DB();
$mysqli = $database->getConnection();

// --- AUTHENTICATION ---
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$jwt = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $jwt = $matches[1];
}

try {
    if ($jwt) {
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    } else {
        throw new Exception("No token");
    }
} catch (Exception $e) {
    // Access Denied
    http_response_code(401);
    echo json_encode(["message" => "Access denied: " . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List all Permissions (Roles)
    $query = "SELECT * FROM permissions ORDER BY id ASC";
    $result = $mysqli->query($query);
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        // Fetch enabled modules for each permission
        $pid = $row['id'];
        $mod_query = "SELECT module_id FROM permission_modules WHERE permission_id = $pid AND can_view = 1";
        $mod_res = $mysqli->query($mod_query);
        $enabled_modules = [];
        while ($m = $mod_res->fetch_assoc()) {
            $enabled_modules[] = $m['module_id'];
        }
        $row['enabled_modules'] = $enabled_modules;
        $permissions[] = $row;
    }
    echo json_encode($permissions);

} elseif ($method === 'POST') {
    // Update Permission Matrix
    // Body: { permission_id: 2, module_id: 5, can_view: true/false }
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->permission_id) || !isset($data->module_id)) {
        http_response_code(400);
        exit();
    }

    $pid = intval($data->permission_id);
    $mid = intval($data->module_id);
    $can_view = $data->can_view ? 1 : 0;

    $stmt = $mysqli->prepare("INSERT INTO permission_modules (permission_id, module_id, can_view) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE can_view = VALUES(can_view)");
    $stmt->bind_param("iii", $pid, $mid, $can_view);
    
    if ($stmt->execute()) {
        echo json_encode(["message" => "Permission updated."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Update failed.", "error" => $stmt->error]);
    }
}
?>
