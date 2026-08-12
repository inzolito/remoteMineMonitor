<?php
require_once __DIR__ . '/api/db.php';
$db = new DB();
$mysqli = $db->getConnection();

// Add column
$mysqli->query("ALTER TABLE monitoring_system.users ADD COLUMN show_in_tickets TINYINT(1) DEFAULT 0");

$users = [
    'Fernando Coronado',
    'Claudio Ponce',
    'Ricardo Rubio',
    'Mauricio Estay',
    'Hugo Fuenzalida',
    'Roberto Maldonado',
    'Juan Buchner',
    'Yazmin Sanchez',
    'Tomas Jimenez',
    'Maikol Salas',
    'Armando Mestanza',
    'Nicolas Carrasco',
    'Nelson Donoso',
    'Guillermo Lamas',
    'Matias Dominguez'
];

foreach ($users as $user) {
    $first_name = trim(explode(' ', $user)[0]);
    $last_name = trim(explode(' ', $user)[1]);
    
    $stmt = $mysqli->prepare("UPDATE monitoring_system.users SET show_in_tickets = 1 WHERE first_name = ? AND last_name = ?");
    $stmt->bind_param('ss', $first_name, $last_name);
    $stmt->execute();
    echo "Updated $user: " . $stmt->affected_rows . " rows affected\n";
}
?>
