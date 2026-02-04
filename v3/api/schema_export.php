<?php
$host = 'localhost';
$user = 'jigsaw';
$pass = 'Jigsaw1';
$db = 'checksupport';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$tables = [];
$result = $mysqli->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    $res = $mysqli->query("SHOW CREATE TABLE $table");
    $row = $res->fetch_row();
    echo $row[1] . ";\n\n";
}

$mysqli->close();
?>
