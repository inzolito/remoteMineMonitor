<?php
if (!function_exists('getallheaders')) { function getallheaders() { return []; } }
function requireAuth() { return ['id' => 1]; }
$_GET['site_id'] = 19;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/metrics.php';
ob_start();
require 'metrics.php';
$json = ob_get_clean();
file_put_contents('api_out.json', $json);
