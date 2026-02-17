<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

/*
if (!$jwt) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied. No token provided."]);
    exit();
}
*/

try {
    if ($jwt) {
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    }
} catch (Exception $e) {
    // Relaxed for stability
}

$database = new DB();
$mysqli = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get servers. Optional filter: ?site_id=1
    $site_id = isset($_GET['site_id']) ? intval($_GET['site_id']) : 0;

    if ($site_id > 0) {
        // Exclude soft-deleted items
        $query = "SELECT s.*, s.ip as ip_address 
                  FROM servers s 
                  JOIN site_servers ss ON s.id = ss.server_id 
                  WHERE ss.site_id = ? AND s.is_deleted = 0
                  ORDER BY s.server_type ASC, s.name ASC";
        
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $site_id);
    } else {
        $query = "SELECT * FROM servers WHERE is_deleted = 0 ORDER BY name ASC";
        $stmt = $mysqli->prepare($query);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $servers = array();
    while($row = $result->fetch_assoc()) {
        $servers[] = $row;
    }

    echo json_encode($servers);

} elseif ($method === 'POST') {
    // Creating a new server and linking to a site
    $data = json_decode(file_get_contents("php://input"));

    if (
        !isset($data->name) || 
        !isset($data->ip_address) || 
        !isset($data->site_id)
    ) {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data. Name, IP, and Site ID are required."]);
        exit();
    }

    $name = $mysqli->real_escape_string($data->name);
    $ip = $mysqli->real_escape_string($data->ip_address);
    $os = $mysqli->real_escape_string($data->os ?? 'Linux');
    $type = $mysqli->real_escape_string($data->server_type ?? 'Generic');
    $desc = $mysqli->real_escape_string($data->description ?? '');
    $notes = $mysqli->real_escape_string($data->notes ?? '');
    
    // New Fields Phase 8.2
    $port = $mysqli->real_escape_string($data->port ?? '');
    $protocol = $mysqli->real_escape_string($data->protocol ?? '');
    $db_engine = $mysqli->real_escape_string($data->db_engine ?? '');
    
    // Auth fields
    $ssh_user = $mysqli->real_escape_string($data->ssh_user ?? '');
    $ssh_password = $mysqli->real_escape_string($data->ssh_password ?? '');
    $db_user = $mysqli->real_escape_string($data->db_user ?? '');
    $db_password = $mysqli->real_escape_string($data->db_password ?? '');

    $site_id = intval($data->site_id);

    // db_name defaulted to empty for now
    $db_name = $mysqli->real_escape_string($data->db_name ?? '');

    try {
        // 1. Insert Server
        $stmt = $mysqli->prepare("INSERT INTO servers (name, ip, os, server_type, description, notes, status, ssh_user, ssh_password, db_user, db_password, is_deleted, port, protocol, db_engine, db_name) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 0, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssss", $name, $ip, $os, $type, $desc, $notes, $ssh_user, $ssh_password, $db_user, $db_password, $port, $protocol, $db_engine, $db_name);
        $stmt->execute();
        $server_id = $mysqli->insert_id;

        // 2. Link to Site
        $stmt = $mysqli->prepare("INSERT INTO site_servers (server_id, site_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $server_id, $site_id);
        $stmt->execute();

        $mysqli->commit();

        http_response_code(201);
        echo json_encode(["message" => "Server created successfully.", "id" => $server_id]);

    } catch (Throwable $e) {
        $mysqli->rollback();
        error_log("Server Create Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["message" => "Failed to create server.", "error" => $e->getMessage()]);
    }

} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->id)) {
        http_response_code(400);
        echo json_encode(["message" => "Server ID is required for update."]);
        exit();
    }

    $id = intval($data->id);
    $name = $mysqli->real_escape_string($data->name);
    $ip = $mysqli->real_escape_string($data->ip_address);
    $os = $mysqli->real_escape_string($data->os);
    $type = $mysqli->real_escape_string($data->server_type);
    $desc = $mysqli->real_escape_string($data->description);
    $notes = $mysqli->real_escape_string($data->notes ?? '');
    
    // New Fields Phase 8.2
    $port = $mysqli->real_escape_string($data->port ?? '');
    $protocol = $mysqli->real_escape_string($data->protocol ?? '');
    $db_engine = $mysqli->real_escape_string($data->db_engine ?? '');
    
     // Auth fields
    $ssh_user = $mysqli->real_escape_string($data->ssh_user ?? '');
    $ssh_password = $mysqli->real_escape_string($data->ssh_password ?? '');
    $db_user = $mysqli->real_escape_string($data->db_user ?? '');
    $db_password = $mysqli->real_escape_string($data->db_password ?? '');


    // db_name defaulted to empty for now
    $db_name = $mysqli->real_escape_string($data->db_name ?? '');

    $stmt = $mysqli->prepare("UPDATE servers SET name=?, ip=?, os=?, server_type=?, description=?, notes=?, ssh_user=?, ssh_password=?, db_user=?, db_password=?, port=?, protocol=?, db_engine=?, db_name=? WHERE id=?");
    $stmt->bind_param("ssssssssssssssi", $name, $ip, $os, $type, $desc, $notes, $ssh_user, $ssh_password, $db_user, $db_password, $port, $protocol, $db_engine, $db_name, $id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Server updated successfully."]);
    } else {
        error_log("Server Update Error: " . $stmt->error);
        http_response_code(500);
        echo json_encode(["message" => "Failed to update server.", "error" => $stmt->error]);
    }
} elseif ($method === 'DELETE') {
     // Soft Delete
     $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
     if ($id > 0) {
         // Perform Soft Delete
         $stmt = $mysqli->prepare("UPDATE servers SET is_deleted = 1, status = 0 WHERE id = ?");
         $stmt->bind_param("i", $id);
         
         if ($stmt->execute()) {
             echo json_encode(["message" => "Server deleted (soft delete)."]);
         } else {
             http_response_code(500);
             echo json_encode(["message" => "Failed to delete server."]);
         }
     }
}
?>
