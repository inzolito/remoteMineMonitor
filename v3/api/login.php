<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('America/Santiago');

require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;

$secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD"; // Make sure to change this in production env
$issuer_claim = "monitoreo_server"; // this can be the servername
$audience_claim = "monitoreo_users";
$issuedat_claim = time(); // issued at
$notbefore_claim = $issuedat_claim; // not before in seconds
$expire_claim = $issuedat_claim + 3600 * 24 * 7; // Default 7 days
// Special case for maik: 1 year session
if (isset($data->username) && $data->username === 'maik') {
    $expire_claim = $issuedat_claim + 3600 * 24 * 365;
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    http_response_code(200);
    exit();
}

// Get Input Data
$rawInput = file_get_contents("php://input");
$log = "Timestamp: " . date('Y-m-d H:i:s') . "\n";
$log .= "Raw Input: " . $rawInput . "\n";
file_put_contents('/tmp/debug_login_raw.log', $log, FILE_APPEND);

$data = json_decode($rawInput);

if (!isset($data->username) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Data incomplete."]);
    exit();
}

// Connect DB (Using simplified manual connection for this endpoint for now)
// Connect DB
require_once __DIR__ . '/db.php';
$database = new DB();
$mysqli = $database->getConnection();

$username = $mysqli->real_escape_string($data->username);
$password = $data->password; // Legacy passwords are plain text in old controller logic: password='$pass'

// Query User
// We join with permissions to get role info
$query = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, p.name as role 
          FROM users u 
          JOIN permissions p ON u.permission_id = p.id 
          WHERE u.username = '$username' AND u.password = '$password'
          LIMIT 1";

$result = $mysqli->query($query);

// DEBUG LOGGING TO FILE
$log = "Timestamp: " . date('Y-m-d H:i:s') . "\n";
$log .= "Login Debug - Username: $username\n";
$log .= "Login Debug - Query: $query\n";
$log .= "Login Debug - Rows found: " . $result->num_rows . "\n";
file_put_contents('/tmp/debug_monitoreo.log', $log, FILE_APPEND);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    file_put_contents(__DIR__ . '/login_debug_keys.log', "Keys: " . implode(', ', array_keys($row)) . "\n", FILE_APPEND);

    $token = array(
        "iss" => $issuer_claim,
        "aud" => $audience_claim,
        "iat" => $issuedat_claim,
        "nbf" => $notbefore_claim,
        "exp" => $expire_claim,
        "data" => array(
            "id" => $row['id'],
            "username" => $row['username'],
            "firstname" => $row['first_name'],
            "lastname" => $row['last_name'],
            "email" => $row['email'],
            "role" => $row['role']
        )
    );

    $jwt = JWT::encode($token, $secret_key, 'HS256');

    http_response_code(200);
    echo json_encode(
        array(
            "message" => "Successful login.",
            "token" => $jwt,
            "user" => $row,
            "expireAt" => $expire_claim
        )
    );

} else {
    http_response_code(401);
    echo json_encode(["message" => "Login failed. Incorrect credentials."]);
}
?>
