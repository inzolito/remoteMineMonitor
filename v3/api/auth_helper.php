<?php
require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function require_auth($mysqli) {
    $secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD";
    
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTHORIZATION'] ?? '';
    
    if (empty($authHeader) && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach (['Authorization', 'authorization', 'X-Authorization', 'x-authorization'] as $h) {
            if (isset($headers[$h])) {
                $authHeader = $headers[$h];
                break;
            }
        }
    }

    $jwt = null;
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwt = $matches[1];
    }

    if (!$jwt) {
        file_put_contents('/tmp/auth_error.log', "Timestamp: " . date('Y-m-d H:i:s') . " - Error: No token provided. Header: " . $authHeader . "\n", FILE_APPEND);
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized: No token provided"]);
        exit();
    }

    try {
        JWT::$leeway = 7200; // 2 hours leeway for time drift
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    } catch (\Firebase\JWT\ExpiredException $e) {
        // Special case for 'maik' (allow expired tokens)
        $tks = explode('.', $jwt);
        if (count($tks) === 3) {
            $payload = json_decode(JWT::urlsafeB64Decode($tks[1]));
            if (isset($payload->data->username) && $payload->data->username === 'maik') {
                $decoded = $payload;
            } else {
                file_put_contents('/tmp/auth_error.log', "Timestamp: " . date('Y-m-d H:i:s') . " - Error: Session expired for non-maik user. Username: " . ($payload->data->username ?? 'unknown') . "\n", FILE_APPEND);
                http_response_code(401);
                echo json_encode(["message" => "Unauthorized: Session expired"]);
                exit();
            }
        } else {
            file_put_contents('/tmp/auth_error.log', "Timestamp: " . date('Y-m-d H:i:s') . " - Error: Invalid token structure on ExpiredException.\n", FILE_APPEND);
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized: Invalid token structure"]);
            exit();
        }
    } catch (Exception $e) {
        file_put_contents('/tmp/auth_error.log', "Timestamp: " . date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . " - Token: " . $jwt . "\n", FILE_APPEND);
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized: " . $e->getMessage()]);
        exit();
    }

    $user_id = $decoded->data->id ?? null;
    $username = $decoded->data->username ?? null;

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized: Missing user ID in token"]);
        exit();
    }

    // Fetch fresh permission_id and is_active status from DB
    $stmt = $mysqli->prepare("SELECT permission_id, is_active FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        // If the user has been deactivated, immediately deny request
        if (intval($row['is_active'] ?? 1) !== 1) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized: Account is deactivated"]);
            exit();
        }
        return [
            'user_id' => $user_id,
            'username' => $username,
            'permission_id' => $row['permission_id'],
            'decoded' => $decoded
        ];
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized: User not found"]);
        exit();
    }
}
?>
