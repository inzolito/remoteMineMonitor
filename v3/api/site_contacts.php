<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';

$secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD";

// Validate JWT
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$jwt = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $jwt = $matches[1];
}

if (!$jwt) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied. No token provided."]);
    exit();
}

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied. Invalid token."]);
    exit();
}

$database = new DB();
$mysqli = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        if (isset($_GET['site_id'])) {
            $site_id = intval($_GET['site_id']);
            $stmt = $mysqli->prepare("SELECT * FROM site_contacts WHERE site_id = ? ORDER BY role ASC, name ASC");
            $stmt->bind_param("i", $site_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $contacts = [];
            while ($row = $result->fetch_assoc()) {
                $contacts[] = $row;
            }
            echo json_encode($contacts);
        } else {
             http_response_code(400);
             echo json_encode(["message" => "site_id is required"]);
        }
        break;

    case 'POST':
        if (!isset($input['site_id']) || !isset($input['name']) || !isset($input['role'])) {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data. site_id, name, and role are required."]);
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO site_contacts (site_id, name, role, phone, email) VALUES (?, ?, ?, ?, ?)");
        $phone = $input['phone'] ?? '';
        $email = $input['email'] ?? '';
        
        $stmt->bind_param("issss", $input['site_id'], $input['name'], $input['role'], $phone, $email);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Contact created successfully", "id" => $mysqli->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create contact"]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "id is required"]);
            exit();
        }
        $id = intval($_GET['id']);
        
        $stmt = $mysqli->prepare("DELETE FROM site_contacts WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
             echo json_encode(["message" => "Contact deleted successfully"]);
        } else {
             http_response_code(500);
             echo json_encode(["message" => "Failed to delete contact"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>
