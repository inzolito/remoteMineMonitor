<?php
// Mock environment for admin.php test
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'commands';

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
$_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = "Bearer $jwt"; // Some setups use this

// Mock Input
// Metric ID 22 (apacheService)
$input = [
    "metric_id" => 22,
    "command" => "echo test_debug_command",
    "priority" => 99,
    "timeout_seconds" => 45,
    "os_family" => "linux"
];

// Helper to mock file_get_contents('php://input')
// Since we can't easily overwrite php://input in CLI affecting the included script without streams overlap or override,
// allow admin.php to read from a global variable IF defined, or we construct a stream wrapper.
// EASIER: I'll modify admin.php slightly to prefer a check, OR I use stream_wrapper_register to mock php://input?
// Too complex.
// ALTERNATIVE: Use `php -r` with input pipe?
// No, I can't pipe into the included file easily.
// I will just modify `test_save_command.php` to create a temp file and tell `admin.php` to read it? No `admin.php` reads `php://input`.

// Let's use the standard "fwrite to php://memory" won't work across context easily.
// OK, I'll use a hack in `admin.php` for testing if I have to.
// But wait, `test_admin.php` used GET so it didn't need input.

// Let's create the file, then run it via `php test_save_command.php < payload.json`?
// `file_get_contents("php://input")` reads STDIN in CLI.
// So I can just pipe the JSON to the script!

// So this script just needs to set up the headers/env.
?>
<?php
// The actual logic is mostly just requiring admin.php.
// But I need to set the headers first.

ob_start();
require __DIR__ . '/admin.php';
$output = ob_get_clean();

echo "--- API OUTPUT ---\n";
echo $output . "\n";
echo "------------------\n";
?>
