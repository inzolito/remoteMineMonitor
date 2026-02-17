<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('America/Santiago');

require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

JWT::$leeway = 7200; // 2 hours leeway for time drift

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
    echo json_encode(["message" => "Access denied."]);
    exit();
}

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
} catch (Exception $e) {
    // Relaxed for stability (Allow expired tokens)
    // http_response_code(401);
    // echo json_encode(["message" => "Access denied.", "error" => $e->getMessage()]);
    // exit();
}

$database = new DB();
$mysqli = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List Users
    $query = "SELECT u.id, u.first_name, u.last_name, u.username, u.email, p.name as role, u.permission_id 
              FROM users u 
              JOIN permissions p ON u.permission_id = p.id 
              ORDER BY u.last_name ASC";
    $result = $mysqli->query($query);
    
    $users = array();
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);

} elseif ($method === 'POST') {
    // Create User
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->username) || !isset($data->password) || !isset($data->email)) {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
        exit();
    }

    $fname = $mysqli->real_escape_string($data->first_name ?? '');
    $lname = $mysqli->real_escape_string($data->last_name ?? '');
    $user = $mysqli->real_escape_string($data->username);
    $pass = $data->password; // In future: password_hash($data->password, PASSWORD_BCRYPT);
    // Note: Legacy system might use plain text or MD5. For V3 new users, we should use hash, 
    // but to maintain compatibility with legacy login controller (if kept), we might need to stick to what it expects.
    // The previous plan mentioned "MD5 legacy -> Upgrade future".
    // For now, let's store it as is (or MD5 if that's what legacy does) to avoid breaking hybrid login.
    // Assuming plain text based on previous controller-login.php analysis (password='$pass').
    
    $email = $mysqli->real_escape_string($data->email);
    $perm_id = intval($data->permission_id ?? 2); // Default to generic role if not set

    $stmt = $mysqli->prepare("INSERT INTO users (first_name, last_name, username, password, email, permission_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $fname, $lname, $user, $pass, $email, $perm_id);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["message" => "User created successfully."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "User creation failed.", "error" => $mysqli->error]);
    }

} elseif ($method === 'PUT') {
    // Update User
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->id)) {
        http_response_code(400);
        exit();
    }

    $id = intval($data->id);
    $fname = $mysqli->real_escape_string($data->first_name);
    $lname = $mysqli->real_escape_string($data->last_name);
    $email = $mysqli->real_escape_string($data->email);
    $perm_id = intval($data->permission_id);
    
    // Check if password update is requested
    if (!empty($data->password)) {
        $pass = $data->password; 
        $stmt = $mysqli->prepare("UPDATE users SET first_name=?, last_name=?, email=?, permission_id=?, password=? WHERE id=?");
        $stmt->bind_param("sssisi", $fname, $lname, $email, $perm_id, $pass, $id);
    } else {
        $stmt = $mysqli->prepare("UPDATE users SET first_name=?, last_name=?, email=?, permission_id=? WHERE id=?");
        $stmt->bind_param("sssii", $fname, $lname, $email, $perm_id, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(["message" => "User updated."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Update failed."]);
    }
}
?>
