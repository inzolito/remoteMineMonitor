<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

echo json_encode(["message" => "API V3 Monitoreo System - Online", "status" => "active"]);
?>
