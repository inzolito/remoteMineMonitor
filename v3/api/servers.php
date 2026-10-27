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

$database = new DB();
$mysqli = $database->getConnection();

require_once __DIR__ . '/auth_helper.php';
$auth = require_auth($mysqli);
$decoded = $auth['decoded'];

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
    $server_ids = [];
    while($row = $result->fetch_assoc()) {
        $row['secondary_product_ids'] = [];
        $servers[] = $row;
        $server_ids[] = intval($row['id']);
    }

    if (!empty($server_ids)) {
        $ids_str = implode(',', $server_ids);
        $sec_res = $mysqli->query("SELECT server_id, product_id FROM server_secondary_products WHERE server_id IN ($ids_str)");
        if ($sec_res) {
            $sec_map = [];
            while ($sr = $sec_res->fetch_assoc()) {
                $sec_map[$sr['server_id']][] = intval($sr['product_id']);
            }
            foreach ($servers as &$s) {
                if (isset($sec_map[$s['id']])) {
                    $s['secondary_product_ids'] = $sec_map[$s['id']];
                }
            }
        }
    }

    echo json_encode($servers);

} elseif ($method === 'POST') {
    // Creating a new server and linking to a site
    $data = json_decode(file_get_contents("php://input"));

    if (
        !isset($data->name) || 
        !isset($data->ip_address) || 
        !isset($data->site_id) ||
        !isset($data->primary_product_id) ||
        empty($data->primary_product_id)
    ) {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data. Name, IP, Site ID, and Primary Product are required."]);
        exit();
    }

    $primary_product_id = intval($data->primary_product_id);
    $name = $mysqli->real_escape_string($data->name);
    $ip = $mysqli->real_escape_string($data->ip_address);
    $os = $mysqli->real_escape_string($data->os ?? 'Linux');
    $type = $mysqli->real_escape_string($data->server_type ?? '');
    
    // Auto-fill server_type text from system_products for backwards compatibility
    if (empty($type) || $type === 'Generic') {
        $pQuery = $mysqli->query("SELECT name FROM system_products WHERE id = $primary_product_id");
        if ($pRow = $pQuery->fetch_assoc()) {
            $type = $mysqli->real_escape_string($pRow['name']);
        } else {
            $type = 'Generic';
        }
    }

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
        $stmt = $mysqli->prepare("INSERT INTO servers (name, ip, os, server_type, description, notes, status, ssh_user, ssh_password, db_user, db_password, is_deleted, port, protocol, db_engine, db_name, primary_product_id) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssi", $name, $ip, $os, $type, $desc, $notes, $ssh_user, $ssh_password, $db_user, $db_password, $port, $protocol, $db_engine, $db_name, $primary_product_id);
        $stmt->execute();
        $server_id = $mysqli->insert_id;

        // 2. Link to Site
        $stmt = $mysqli->prepare("INSERT INTO site_servers (server_id, site_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $server_id, $site_id);
        $stmt->execute();

        // 3. Link Secondary Products if provided
        if (isset($data->secondary_product_ids) && is_array($data->secondary_product_ids)) {
            $secStmt = $mysqli->prepare("INSERT IGNORE INTO server_secondary_products (server_id, product_id) VALUES (?, ?)");
            foreach ($data->secondary_product_ids as $secId) {
                $secId = intval($secId);
                if ($secId > 0 && $secId !== $primary_product_id) {
                    $secStmt->bind_param("ii", $server_id, $secId);
                    $secStmt->execute();
                }
            }
        }

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

    $primary_product_id = isset($data->primary_product_id) && intval($data->primary_product_id) > 0 
        ? intval($data->primary_product_id) 
        : null;

    if ($primary_product_id !== null) {
        $stmt = $mysqli->prepare("UPDATE servers SET name=?, ip=?, os=?, server_type=?, description=?, notes=?, ssh_user=?, ssh_password=?, db_user=?, db_password=?, port=?, protocol=?, db_engine=?, db_name=?, primary_product_id=? WHERE id=?");
        $stmt->bind_param("ssssssssssssssii", $name, $ip, $os, $type, $desc, $notes, $ssh_user, $ssh_password, $db_user, $db_password, $port, $protocol, $db_engine, $db_name, $primary_product_id, $id);
    } else {
        $stmt = $mysqli->prepare("UPDATE servers SET name=?, ip=?, os=?, server_type=?, description=?, notes=?, ssh_user=?, ssh_password=?, db_user=?, db_password=?, port=?, protocol=?, db_engine=?, db_name=? WHERE id=?");
        $stmt->bind_param("ssssssssssssssi", $name, $ip, $os, $type, $desc, $notes, $ssh_user, $ssh_password, $db_user, $db_password, $port, $protocol, $db_engine, $db_name, $id);
    }

    if ($stmt->execute()) {
        if (isset($data->secondary_product_ids) && is_array($data->secondary_product_ids)) {
            $mysqli->query("DELETE FROM server_secondary_products WHERE server_id = $id");
            $secStmt = $mysqli->prepare("INSERT IGNORE INTO server_secondary_products (server_id, product_id) VALUES (?, ?)");
            foreach ($data->secondary_product_ids as $secId) {
                $secId = intval($secId);
                if ($secId > 0 && $secId !== $primary_product_id) {
                    $secStmt->bind_param("ii", $id, $secId);
                    $secStmt->execute();
                }
            }
        }
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
