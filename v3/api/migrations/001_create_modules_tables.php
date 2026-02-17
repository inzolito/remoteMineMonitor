<?php
require_once __DIR__ . '/../db.php';

$database = new DB();
$mysqli = $database->getConnection();

// 1. Create modules table
$sql_modules = "CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    path VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($mysqli->query($sql_modules)) {
    echo "Table 'modules' created successfully.\n";
} else {
    echo "Error creating 'modules': " . $mysqli->error . "\n";
}

// 2. Create permission_modules table
$sql_perm_modules = "CREATE TABLE IF NOT EXISTS permission_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_id INT NOT NULL,
    module_id INT NOT NULL,
    can_view BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_perm_module (permission_id, module_id)
)";

if ($mysqli->query($sql_perm_modules)) {
    echo "Table 'permission_modules' created successfully.\n";
} else {
    echo "Error creating 'permission_modules': " . $mysqli->error . "\n";
}

// 3. Seed Modules
$modules = [
    ['Inicio', '/', 'LayoutDashboard', 'Dashboard General'],
    ['Clientes', '/clients', 'Briefcase', 'Vistas de Clientes'],
    ['Monitoreo Remoto', '/monitoreo', 'Activity', 'Monitoreo en Tiempo Real'],
    ['Tickets', '/tickets', 'Ticket', 'Gestión de Incidencias'],
    ['Turnos', '/shifts', 'Clock', 'Gestión de Turnos'],
    ['Gestión Usuarios', '/users', 'Users', 'Administración de Usuarios'],
    ['Super Admin', '/super-admin', 'ShieldCheck', 'Configuración Avanzada']
];

foreach ($modules as $mod) {
    $stmt = $mysqli->prepare("INSERT INTO modules (name, path, icon, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), icon=VALUES(icon)");
    $stmt->bind_param("ssss", $mod[0], $mod[1], $mod[2], $mod[3]);
    if ($stmt->execute()) {
        echo "Module '{$mod[0]}' seeded.\n";
    } else {
        echo "Error seeding '{$mod[0]}': " . $stmt->error . "\n";
    }
}

// 4. Seed Permissions (Grant ALL to Admin (id=1) by default)
// Also grant some to User (id=2) and ReadOnly (id=3) if they exist
$result_modules = $mysqli->query("SELECT id, path FROM modules");
$all_modules = [];
while ($row = $result_modules->fetch_assoc()) {
    $all_modules[] = $row;
}

// Assuming Permission ID 1 is Administrator
$admin_perm_id = 1;
foreach ($all_modules as $mod) {
    $stmt = $mysqli->prepare("INSERT IGNORE INTO permission_modules (permission_id, module_id, can_view) VALUES (?, ?, 1)");
    $stmt->bind_param("ii", $admin_perm_id, $mod['id']);
    $stmt->execute();
}

echo "Admin permissions seeded.\n";

// Grant basic access to User (id=2) - Everything except Super Admin and Users?
// Let's grant standard modules: Inicio, Clientes, Monitoreo, Tickets, Turnos
$user_perm_id = 2;
foreach ($all_modules as $mod) {
    if (in_array($mod['path'], ['/', '/clients', '/monitoreo', '/tickets', '/shifts'])) {
        $stmt = $mysqli->prepare("INSERT IGNORE INTO permission_modules (permission_id, module_id, can_view) VALUES (?, ?, 1)");
        $stmt->bind_param("ii", $user_perm_id, $mod['id']);
        $stmt->execute();
    }
}
echo "User permissions seeded.\n";

$mysqli->close();
?>
