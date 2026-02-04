<?php
/**
 * Final Cleanup: Standardize Metrics and Reset Templates
 */

require_once __DIR__ . '/db.php';
$database = new DB();
$mysqli = $database->getConnection();

echo "Starting Metric Standards Cleanup...\n";

// 1. Define the Standard Catalog
$standardMetrics = [
    // System
    ['system.cpu.load', 'CPU Load', 'Carga promedio del procesador'],
    ['system.ram.percent', 'RAM Usage %', 'Porcentaje de memoria RAM en uso'],
    ['system.ram.total', 'RAM Total', 'Memoria RAM total instalada'],
    ['system.ram.used', 'RAM Used', 'Memoria RAM utilizada'],
    ['system.disk.usage', 'Disk Usage %', 'Porcentaje de uso del disco principal'],
    ['system.uptime', 'Uptime', 'Tiempo de actividad del sistema'],
    
    // FMS App
    ['app.fms.version', 'FMS Version', 'Versión de la aplicación FMS'],
    ['app.fms.active_scripts', 'Active Scripts', 'Número de scripts en ejecución'],
    ['app.connectivity.trucks', 'Connected Trucks', 'Cantidad de equipos/camiones conectados'],
    ['app.station.ping', 'Base Station Ping', 'Latencia hacia la estación base'],
    
    // DB
    ['db.schema.date', 'Last Schema Sync', 'Fecha de la última sincronización de esquema'],
    ['db.integrity.tables', 'Tables Count', 'Cantidad de tablas detectadas en la base de datos'],
    ['db.integrity.diff', 'DB Sync Diff', 'Diferencia de registros entre nodos de base de datos'],
    ['db.size', 'Database Size', 'Tamaño total de la base de datos'],
    
    // Backups
    ['backup.daily.file', 'Daily Backup File', 'Nombre del último backup diario'],
    ['backup.daily.status', 'Daily Backup Status', 'Estado del último backup diario'],
    ['backup.hourly.file', 'Hourly Backup File', 'Nombre del último backup por hora'],
    ['backup.hourly.status', 'Hourly Backup Status', 'Estado del último backup por hora'],
    
    // Services
    ['jamsService', 'JAMS Service', 'Estado del servicio JAMS'],
    ['fmsService', 'FMS Service', 'Estado del servicio principal FMS'],
    ['dbService', 'Database Service', 'Estado del motor de base de datos'],
    ['apacheService', 'Web Server', 'Estado del servidor web Apache'],
    ['sshService', 'SSH Access', 'Estado del servicio SSH'],
    ['vpnService', 'VPN Connection', 'Estado del túnel VPN'],
    ['ntpService', 'NTP Sync', 'Estado de la sincronización horaria'],
    ['segOfflineOasService', 'OAS Offline Service', 'Servicio OAS modo offline'],
    ['segOnlineOasService', 'OAS Online Service', 'Servicio OAS modo online']
];

echo "Updating/Inserting Standard Metrics...\n";
foreach ($standardMetrics as $m) {
    $stmt = $mysqli->prepare("INSERT INTO metrics (name, display_name, description) VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description)");
    $stmt->bind_param("sss", $m[0], $m[1], $m[2]);
    $stmt->execute();
}

// 2. Clear Template Assignments and Re-assign
echo "Resetting Template Assignments...\n";
$mysqli->query("DELETE FROM server_type_metrics");

// Helper to get ID
function getMid($mysqli, $name) {
    $res = $mysqli->query("SELECT id FROM metrics WHERE name = '$name'");
    $row = $res->fetch_assoc();
    return $row['id'] ?? null;
}

// Templates setup
$templates = [
    'FMS' => [
        'system.cpu.load', 'system.ram.percent', 'system.disk.usage',
        'app.connectivity.trucks', 'db.schema.date', 'db.integrity.tables',
        'jamsService', 'dbService', 'apacheService', 'sshService',
        'backup.daily.file', 'backup.hourly.file'
    ],
    'OAS' => [
        'system.cpu.load', 'system.ram.percent', 'system.disk.usage',
        'app.connectivity.trucks', 'segOfflineOasService', 'segOnlineOasService'
    ]
];

foreach ($templates as $typeName => $metricsList) {
    $typeRes = $mysqli->query("SELECT id FROM server_types WHERE name = '$typeName'");
    $typeId = $typeRes->fetch_assoc()['id'] ?? null;
    if (!$typeId) continue;
    
    foreach ($metricsList as $mName) {
        $mid = getMid($mysqli, $mName);
        if ($mid) {
            $mysqli->query("INSERT IGNORE INTO server_type_metrics (server_type_id, metric_id, default_frequency) VALUES ($typeId, $mid, 60)");
        }
    }
}

// Mirror FMS to FMS_SECONDARY if exists
$mysqli->query("INSERT IGNORE INTO server_type_metrics (server_type_id, metric_id, default_frequency) 
                SELECT st2.id, stm.metric_id, stm.default_frequency 
                FROM server_type_metrics stm 
                JOIN server_types st1 ON stm.server_type_id = st1.id 
                JOIN server_types st2 ON st2.name = 'FMS_SECONDARY' 
                WHERE st1.name = 'FMS'");

// 3. Final PURGE: Delete from metrics any key NOT in our standard list
echo "Purging Legacy Metrics...\n";
$names = array_column($standardMetrics, 0);
$quoted = "'" . implode("','", $names) . "'";
$mysqli->query("DELETE FROM metrics WHERE name NOT IN ($quoted)");

// 4. Update existing server_metrics_config to map to new standard where possible
echo "Syncing all servers with new templates...\n";
require_once __DIR__ . '/sync_type_metrics.php';
syncServerTemplates();

echo "Cleanup Complete. The catalog is now clean and standardized.\n";
