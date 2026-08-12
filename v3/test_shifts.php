<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once 'api/db.php';
$database = new DB();
$mysqli = $database->getConnection();
require_once 'api/shifts.php';
