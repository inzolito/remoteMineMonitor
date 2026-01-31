<?php
// Mock environment for admin.php test
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'metrics';

// Generate valid Token
require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;

$secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD";
$token = [
    "iss" => "test",
    "aud" => "test",
    "iat" => time(),
    "nbf" => time(),
    "exp" => time() + 3600,
    "data" => [
        "username" => "maik",
        "role" => "Admin"
    ]
];
$jwt = JWT::encode($token, $secret_key, 'HS256');
$_SERVER['HTTP_AUTHORIZATION'] = "Bearer $jwt";

// Capture output
ob_start();
require __DIR__ . '/admin.php';
$output = ob_get_clean();

echo "--- API OUTPUT ---\n";
echo $output . "\n";
echo "------------------\n";
?>
