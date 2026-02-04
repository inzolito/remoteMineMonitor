<?php
$host = 'localhost';
$user = 'jigsaw';
$pass = 'Jigsaw1';
$old_db = 'checksupport';
$new_db = 'monitoring_system';

// Connect to Server
$mysqli = new mysqli($host, $user, $pass);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Create DB
echo "Creating Database $new_db...\n";
$mysqli->query("CREATE DATABASE IF NOT EXISTS $new_db");
$mysqli->select_db($new_db);

// Run Schema
echo "Importing Schema...\n";
$schema = file_get_contents(__DIR__ . '/schema_v3.sql');
if ($mysqli->multi_query($schema)) {
    do {
        // flush results
        if ($res = $mysqli->store_result()) {
            $res->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
} else {
    die("Schema import failed: " . $mysqli->error);
}

// Connect to Old DB
$old = new mysqli($host, $user, $pass, $old_db);
if ($old->connect_error) {
    die("Old DB connection failed: " . $old->connect_error);
}

// Migrate Permissions
echo "Migrating Permissions...\n";
$mysqli->query("TRUNCATE TABLE permissions"); // Clean target
$res = $old->query("SELECT * FROM permisos");
while ($row = $res->fetch_assoc()) {
    $stmt = $mysqli->prepare("INSERT INTO permissions (id, name, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $row['id'], $row['permiso'], $row['descripcion']);
    $stmt->execute();
}

// Migrate Users
echo "Migrating Users...\n";
$mysqli->query("DELETE FROM users"); // Clean target (cant truncate due to FK?)
$res = $old->query("SELECT * FROM usuarios");
while ($row = $res->fetch_assoc()) {
    $stmt = $mysqli->prepare("INSERT INTO users (id, permission_id, first_name, last_name, username, password, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $row['id'], $row['id_permiso'], $row['nombre'], $row['apellido'], $row['usuario'], $row['password'], $row['mail']);
    $stmt->execute();
}

// Migrate Sites (Faenas)
echo "Migrating Sites...\n";
$mysqli->query("DELETE FROM sites");
$res = $old->query("SELECT * FROM faenas");
while ($row = $res->fetch_assoc()) {
    $stmt = $mysqli->prepare("INSERT INTO sites (id, name, alias, status, conglomerate, logo_url, contract_manager, dispatch_contact, dispatch_phone, onsite_engineers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississssss", 
        $row['id'], $row['faena'], $row['alias'], $row['estado'], 
        $row['conglomerado'], $row['logo'], $row['administrador_contrato'], 
        $row['despacho'], $row['telefono_despacho'], $row['ingenieros_onsite']
    );
    $stmt->execute();
}

// Migrate Servers
echo "Migrating Servers...\n";
$mysqli->query("DELETE FROM servers");
$res = $old->query("SELECT * FROM servidores");
while ($row = $res->fetch_assoc()) {
    $stmt = $mysqli->prepare("INSERT INTO servers (id, name, ip_address, os, ssh_user, ssh_password, protocol, port, server_type, db_name, db_engine, db_user, db_password, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssisssssis", 
        $row['id'], $row['nombre'], $row['ip'], $row['sistema_operativo'], 
        $row['usuario'], $row['password'], $row['protocolo'], $row['puerto'], 
        $row['tipo_servidor'], $row['database'], $row['motor_db'], 
        $row['usuario_db'], $row['password_db'], $row['estado'], $row['descripcion']
    );
    $stmt->execute();
}

// Migrate Site Servers (Pivot)
echo "Migrating Site Servers...\n";
$mysqli->query("DELETE FROM site_servers");
$res = $old->query("SELECT * FROM faenas_servidores");
while ($row = $res->fetch_assoc()) {
    // Check if referenced IDs exist to avoid FK errors
    $server_exists = $mysqli->query("SELECT id FROM servers WHERE id = " . $row['id_servidor'])->num_rows > 0;
    $site_exists = $mysqli->query("SELECT id FROM sites WHERE id = " . $row['id_faena'])->num_rows > 0;
    
    if ($server_exists && $site_exists) {
        $stmt = $mysqli->prepare("INSERT INTO site_servers (id, server_id, site_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $row['id'], $row['id_servidor'], $row['id_faena']);
        $stmt->execute();
    }
}

// Migrate Metrics
echo "Migrating Metrics...\n";
$mysqli->query("DELETE FROM metrics");
$res = $old->query("SELECT * FROM metricas");
while ($row = $res->fetch_assoc()) {
    $stmt = $mysqli->prepare("INSERT INTO metrics (id, name, symbol) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $row['id'], $row['metrica'], $row['simbolo']);
    $stmt->execute();
}

echo "Migration Complete!\n";
?>
