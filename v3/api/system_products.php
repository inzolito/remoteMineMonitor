<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

JWT::$leeway = 7200;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new DB();
$mysqli = $database->getConnection();

require_once __DIR__ . '/auth_helper.php';
$auth = require_auth($mysqli);
$decoded = $auth['decoded'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $query = "SELECT * FROM system_products WHERE is_active = 1 ORDER BY id ASC";
    $result = $mysqli->query($query);
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = intval($row['id']);
        $row['is_active'] = intval($row['is_active']);
        $products[] = $row;
    }
    echo json_encode($products);
    exit();
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    if (!isset($data->name) || trim($data->name) === '') {
        http_response_code(400);
        echo json_encode(["message" => "El nombre del producto es obligatorio."]);
        exit();
    }

    $name = trim($data->name);
    // Generate code slug if not provided
    $code = isset($data->code) && trim($data->code) !== '' ? trim($data->code) : strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
    $code = trim($code, '_');
    if ($code === '') {
        $code = 'prod_' . time();
    }
    
    // Check if code already exists, if so append random/timestamp
    $checkStmt = $mysqli->prepare("SELECT id FROM system_products WHERE code = ?");
    $checkStmt->bind_param("s", $code);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $code = $code . '_' . time();
    }

    $icon = isset($data->icon) && trim($data->icon) !== '' ? trim($data->icon) : 'Server';
    $color = isset($data->color) && trim($data->color) !== '' ? trim($data->color) : 'blue';
    $default_os = isset($data->default_os) && trim($data->default_os) !== '' ? trim($data->default_os) : 'Linux';
    $default_port = isset($data->default_port) && trim($data->default_port) !== '' ? trim($data->default_port) : '22';
    $default_protocol = isset($data->default_protocol) && trim($data->default_protocol) !== '' ? trim($data->default_protocol) : 'SSH';

    $stmt = $mysqli->prepare("INSERT INTO system_products (name, code, icon, color, default_os, default_port, default_protocol, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssssss", $name, $code, $icon, $color, $default_os, $default_port, $default_protocol);

    if ($stmt->execute()) {
        $newId = $mysqli->insert_id;
        http_response_code(201);
        echo json_encode([
            "message" => "Producto creado exitosamente.",
            "product" => [
                "id" => $newId,
                "name" => $name,
                "code" => $code,
                "icon" => $icon,
                "color" => $color,
                "default_os" => $default_os,
                "default_port" => $default_port,
                "default_protocol" => $default_protocol,
                "is_active" => 1
            ]
        ]);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Error al guardar el producto: " . $stmt->error]);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}
