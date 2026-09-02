<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['page'] = '1';
$_GET['limit'] = '30';
function require_auth($mysqli) { return true; }
require 'api/tickets.php';
?>
