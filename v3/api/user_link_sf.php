<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helper.php';

$database = new DB();
$mysqli = $database->getConnection();

// Authenticate user via JWT
$auth = require_auth($mysqli);
$user_id = $auth['user_id'];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "GET method not allowed."]);
    exit();
}

if ($method === 'POST') {
    if ($action === 'link') {
        // 1. Fetch user's local email
        $stmt = $mysqli->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        
        $email = trim($user['email'] ?? '');
        if (empty($email)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error", 
                "message" => "Tu correo electrónico no está configurado. Por favor, actualiza tu correo en 'Datos Personales' antes de vincular tu cuenta con Salesforce."
            ]);
            exit();
        }

        // 2. Search for active Salesforce user with this email (using Username column and support LIKE fallback)
        $stmtSf = $mysqli->prepare("SELECT Id, Name FROM rmmsalesforce.sf_users WHERE (Username = ? OR Username LIKE CONCAT(?, '%')) AND IsActive = 1 LIMIT 1");
        $stmtSf->bind_param("ss", $email, $email);
        $stmtSf->execute();
        $resSf = $stmtSf->get_result();
        $sfUser = $resSf->fetch_assoc();

        if (!$sfUser) {
            http_response_code(404);
            echo json_encode([
                "status" => "error", 
                "message" => "No se encontró ningún usuario activo en Salesforce con tu correo registrado: " . $email
            ]);
            exit();
        }

        $salesforce_user_id = $sfUser['Id'];
        $salesforce_name = $sfUser['Name'];

        // 3. Link them in the database
        $stmtLink = $mysqli->prepare("UPDATE users SET salesforce_user_id = ? WHERE id = ?");
        $stmtLink->bind_param("si", $salesforce_user_id, $user_id);

        if ($stmtLink->execute()) {
            http_response_code(200);
            echo json_encode([
                "status" => "success", 
                "message" => "¡Vinculado exitosamente con Salesforce como " . $salesforce_name . "!",
                "salesforce_user_id" => $salesforce_user_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "status" => "error", 
                "message" => "Error al guardar vinculación en base de datos: " . $stmtLink->error
            ]);
        }
        exit();
    }

    if ($action === 'unlink') {
        $stmt = $mysqli->prepare("UPDATE users SET salesforce_user_id = NULL WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Unlinked successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Unlinkage failed."]);
        }
        exit();
    }
}

http_response_code(400);
echo json_encode(["status" => "error", "message" => "Invalid endpoint request."]);
?>
