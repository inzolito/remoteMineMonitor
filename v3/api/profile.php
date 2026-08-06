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
    // Get profile details
    $stmt = $mysqli->prepare("SELECT id, username, first_name, last_name, email, cargo, salesforce_user_id, teams_webhook_url FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "data" => [
                "id" => $row['id'],
                "username" => $row['username'],
                "first_name" => $row['first_name'],
                "last_name" => $row['last_name'],
                "email" => $row['email'],
                "cargo" => $row['cargo'],
                "salesforce_user_id" => $row['salesforce_user_id'],
                "teams_webhook_url" => $row['teams_webhook_url']
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found."]);
    }
    exit();
}

if ($method === 'POST') {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput);

    if ($action === 'update_personal') {
        if (!isset($data->first_name) || !isset($data->last_name) || !isset($data->email)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing required fields."]);
            exit();
        }

        $first_name = trim($data->first_name);
        $last_name = trim($data->last_name);
        $email = trim($data->email);
        $cargo = isset($data->cargo) ? trim($data->cargo) : null;

        if (empty($first_name) || empty($last_name) || empty($email)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Fields cannot be empty."]);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid email address format."]);
            exit();
        }

        $stmt = $mysqli->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, cargo = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $cargo, $user_id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Profile updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to update profile: " . $stmt->error]);
        }
        exit();
    }

    if ($action === 'change_password') {
        if (!isset($data->new_password)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing password field."]);
            exit();
        }

        $new_password = $data->new_password;

        if (empty($new_password)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Password field cannot be empty."]);
            exit();
        }

        // Update with new password (stored in plain text to maintain parity with legacy login.php)
        $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $user_id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Password updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to update password."]);
        }
        exit();
    }

    if ($action === 'update_teams') {
        if (!isset($data->teams_webhook_url)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing teams_webhook_url."]);
            exit();
        }

        $teams_webhook_url = trim($data->teams_webhook_url);
        if ($teams_webhook_url === '') {
            $teams_webhook_url = null;
        }

        $stmt = $mysqli->prepare("UPDATE users SET teams_webhook_url = ? WHERE id = ?");
        $stmt->bind_param("si", $teams_webhook_url, $user_id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Teams webhook URL updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to update Teams webhook URL."]);
        }
        exit();
    }

    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid POST action."]);
    exit();
}

http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method not allowed."]);
?>
