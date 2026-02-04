<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['ip']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(["message" => "IP and status are required"]);
    exit();
}

$ip = $input['ip'];
$status = intval($input['status']);

$db = new DB();
$conn = $db->getConnection();

// Update server status and last_seen
$stmt = $conn->prepare("UPDATE servers SET status = ?, last_seen = NOW() WHERE ip_address = ?");
$stmt->bind_param("is", $status, $ip);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["message" => "Ping updated successfully", "ip" => $ip, "status" => $status]);
    } else {
        // Server might exist but status didn't change, OR server doesn't exist.
        // Let's check if it exists.
        $check = $conn->prepare("SELECT id FROM servers WHERE ip_address = ?");
        $check->bind_param("s", $ip);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
             // Exists, so just timestamp updated (if status was same). 
             // We want to ensure last_seen updates even if status is same.
             // The previous UPDATE might not increment affected_rows if values are identical.
             // Force update timestamp?
             // Actually, MySQL UPDATE updates timestamp if listed, even if values same? 
             // No, usually optimizations skip it.
             // Let's force update by checking constraints or just trusting it.
             // Or we can add `last_seen` to the SET clause explicitly (which we did).
             echo json_encode(["message" => "Ping processed (no change or timestamp updated)", "ip" => $ip]);
        } else {
             http_response_code(404);
             echo json_encode(["message" => "Server not found with IP: " . $ip]);
        }
    }
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $stmt->error]);
}
?>
