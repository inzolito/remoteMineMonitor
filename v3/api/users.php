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

$database = new DB();
$mysqli = $database->getConnection();

require_once __DIR__ . '/auth_helper.php';
$auth = require_auth($mysqli);
$decoded = $auth['decoded'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List Users - Alphabetical sort by first_name
    $query = "SELECT u.id, u.first_name, u.last_name, u.username, u.email, p.name as role, u.permission_id, u.is_active, u.cargo, u.turno_7x7, u.turno_tipo, u.show_in_tickets 
              FROM users u 
              JOIN permissions p ON u.permission_id = p.id 
              ORDER BY u.first_name ASC";
    $result = $mysqli->query($query);
    
    $users = array();
    while($row = $result->fetch_assoc()) {
        // Ensure is_active is cast to integer/boolean
        $row['is_active'] = filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN);
        $row['turno_7x7'] = $row['turno_7x7'] !== null ? intval($row['turno_7x7']) : null;
        $row['show_in_tickets'] = filter_var($row['show_in_tickets'], FILTER_VALIDATE_BOOLEAN);
        $row['turno_tipo'] = $row['turno_tipo'] ?? 'Día';
        $users[] = $row;
    }
    echo json_encode($users);

} elseif ($method === 'POST') {
    // Create User
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->username) || !isset($data->password)) {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data. Username and Password are required."]);
        exit();
    }

    $fname = $mysqli->real_escape_string($data->first_name ?? '');
    $lname = $mysqli->real_escape_string($data->last_name ?? '');
    $user = $mysqli->real_escape_string($data->username);
    $pass = $data->password;
    $email = $mysqli->real_escape_string($data->email ?? '');
    $perm_id = intval($data->permission_id ?? 2);
    $is_active = isset($data->is_active) ? intval($data->is_active) : 1;
    $show_in_tickets = isset($data->show_in_tickets) ? intval($data->show_in_tickets) : 1;
    $cargo = $mysqli->real_escape_string($data->cargo ?? '');
    $turno_7x7 = isset($data->turno_7x7) && $data->turno_7x7 !== '' && $data->turno_7x7 !== null ? intval($data->turno_7x7) : null;
    $turno_tipo = $mysqli->real_escape_string($data->turno_tipo ?? 'Día');

    $stmt = $mysqli->prepare("INSERT INTO users (first_name, last_name, username, password, email, permission_id, is_active, cargo, turno_7x7, turno_tipo, show_in_tickets) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiisisi", $fname, $lname, $user, $pass, $email, $perm_id, $is_active, $cargo, $turno_7x7, $turno_tipo, $show_in_tickets);

    if ($stmt->execute()) {
        $new_id = $mysqli->insert_id;
        if ($turno_7x7 !== null) {
            $mysqli->query("INSERT INTO shift_group_members (user_id, group_id, sub_shift) VALUES ($new_id, $turno_7x7, '$turno_tipo')");
        }
        
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

    // Dynamic single status toggle update
    if (isset($data->is_active) && !isset($data->first_name)) {
        $is_active = intval($data->is_active);
        $stmt = $mysqli->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_active, $id);
        if ($stmt->execute()) {
            echo json_encode(["message" => "User status updated."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Status update failed."]);
        }
        exit();
    }

    $fname = $mysqli->real_escape_string($data->first_name);
    $lname = $mysqli->real_escape_string($data->last_name);
    $email = $mysqli->real_escape_string($data->email ?? '');
    $perm_id = intval($data->permission_id);
    $is_active = isset($data->is_active) ? intval($data->is_active) : 1;
    $show_in_tickets = isset($data->show_in_tickets) ? intval($data->show_in_tickets) : 1;
    $cargo = $mysqli->real_escape_string($data->cargo ?? '');
    $turno_7x7 = isset($data->turno_7x7) && $data->turno_7x7 !== '' && $data->turno_7x7 !== null ? intval($data->turno_7x7) : null;
    $turno_tipo = $mysqli->real_escape_string($data->turno_tipo ?? 'Día');
    
    // Check if password update is requested
    if (!empty($data->password)) {
        $pass = $data->password; 
        $stmt = $mysqli->prepare("UPDATE users SET first_name=?, last_name=?, email=?, permission_id=?, password=?, is_active=?, cargo=?, turno_7x7=?, turno_tipo=?, show_in_tickets=? WHERE id=?");
        $stmt->bind_param("sssisisisii", $fname, $lname, $email, $perm_id, $pass, $is_active, $cargo, $turno_7x7, $turno_tipo, $show_in_tickets, $id);
    } else {
        $stmt = $mysqli->prepare("UPDATE users SET first_name=?, last_name=?, email=?, permission_id=?, is_active=?, cargo=?, turno_7x7=?, turno_tipo=?, show_in_tickets=? WHERE id=?");
        $stmt->bind_param("sssiisisi", $fname, $lname, $email, $perm_id, $is_active, $cargo, $turno_7x7, $turno_tipo, $show_in_tickets, $id);
    }

    if ($stmt->execute()) {
        // Keep shift_group_members in sync
        $mysqli->query("DELETE FROM shift_group_members WHERE user_id = $id");
        if ($turno_7x7 !== null) {
            $mysqli->query("INSERT INTO shift_group_members (user_id, group_id, sub_shift) VALUES ($id, $turno_7x7, '$turno_tipo')");
        }

        echo json_encode(["message" => "User updated."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Update failed."]);
    }
}
?>
