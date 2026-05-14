<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

JWT::$leeway = 7200; // 2 hours leeway for time drift

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD";
$database = new DB();
$mysqli = $database->getConnection();

require_once __DIR__ . '/auth_helper.php';
$auth = require_auth($mysqli);
$user_id = $auth['user_id'];
$user_permission_id = $auth['permission_id'];

// Action: List ALL (for Admin Matrix) or List ALLOWED (for Sidebar)
$action = $_GET['action'] ?? 'allowed'; // 'all' or 'allowed'

if ($action === 'all') {
    // Only Admin (Permission 1) or 'maik' should see ALL modules list for configuration
    // For now, let's allow it if we have a valid token. The UI enforces who sees the config tab.
    
    $query = "SELECT * FROM modules ORDER BY id ASC";
    $result = $mysqli->query($query);
    $modules = [];
    while ($row = $result->fetch_assoc()) {
        $modules[] = $row;
    }
    echo json_encode($modules);

} else {
    // List ALLOWED modules for the current user
    if ($user_permission_id > 0) {
        $query = "SELECT m.* 
                  FROM modules m
                  JOIN permission_modules pm ON m.id = pm.module_id
                  WHERE pm.permission_id = ? AND pm.can_view = 1 AND m.is_active = 1
                  ORDER BY m.id ASC";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $user_permission_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $modules = [];
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row;
        }
        echo json_encode($modules);
    } else {
        echo json_encode([]); // No auth, no modules
    }
}
?>
