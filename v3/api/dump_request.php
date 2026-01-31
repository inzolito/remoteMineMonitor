<?php
$logFile = __DIR__ . '/request_dump.log';
$timestamp = date('Y-m-d H:i:s');
$headers = getallheaders();
$rawBody = file_get_contents("php://input");

$dump = "Timestamp: $timestamp\n";
$dump .= "Method: {$_SERVER['REQUEST_METHOD']}\n";
$dump .= "URI: {$_SERVER['REQUEST_URI']}\n";
$dump .= "Headers:\n";
foreach ($headers as $name => $value) {
    $dump .= "  $name: $value\n";
}
$dump .= "Body Size: " . strlen($rawBody) . " bytes\n";
$dump .= "Body Content: $rawBody\n";
$dump .= "----------------------------------------\n";

file_put_contents($logFile, $dump, FILE_APPEND);
echo "Logged";
?>
