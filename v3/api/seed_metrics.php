<?php
require_once __DIR__ . '/db.php';
$database = new DB();
$mysqli = $database->getConnection();

$servers = $mysqli->query("SELECT id, name FROM servers")->fetch_all(MYSQLI_ASSOC);

$now = new DateTime();
$dangerDaily = clone $now;
$dangerDaily->modify('-26 hours');
$okDaily = clone $now;
$okDaily->modify('-10 hours');

$dangerHourly = clone $now;
$dangerHourly->modify('-3 hours');
$okHourly = clone $now;
$okHourly->modify('-1 hour');

foreach ($servers as $server) {
    $sid = $server['id'];
    $name = $server['name'];

    // 1. Server Metrics (System)
    // Alternate between OK and Danger/Warn
    $isDanger = ($sid % 3 === 0);
    $disk_total = 1000;
    $disk_used = $isDanger ? 860 : 500;
    $ram_total = 32;
    $ram_used = $isDanger ? 24 : 10;
    $load = $isDanger ? 6.5 : 1.2;

    $stmt = $mysqli->prepare("INSERT INTO server_metrics (server_id, cpu_usage, ram_usage, ram_total, disk_usage, disk_total, load_average, uptime_seconds, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $cpu = 25.0;
    $uptime = 3600;
    $stmt->bind_param("idddddsi", $sid, $cpu, $ram_used, $ram_total, $disk_used, $disk_total, $load, $uptime);
    $stmt->execute();

    // 2. App Metrics (Backups, Connectivity, etc.)
    $metrics = [
        ['daily', ($sid % 2 === 0 ? "jmineops-".$dangerDaily->format('Y-m-d_H-i-s').".tgz" : "jmineops-".$okDaily->format('Y-m-d_H-i-s').".tgz"), ($sid % 2 === 0 ? 'danger' : 'ok')],
        ['hourly', ($sid % 4 === 0 ? "jmineops-".$dangerHourly->format('Y-m-d_H-00-00').".tgz" : "jmineops-".$okHourly->format('Y-m-d_H-00-00').".tgz"), ($sid % 4 === 0 ? 'danger' : 'ok')],
        ['repc', ($sid % 5 === 0 ? "0" : "15"), ($sid % 5 === 0 ? 'danger' : 'ok')], // Equipos conectados
        ['estacionBase', ($sid % 6 === 0 ? "No response" : "OK: amant201801"), ($sid % 6 === 0 ? 'danger' : 'ok')],
        ['versionJAMS', 'V7.2.1', 'ok'],
        ['nombreServidor', $name, 'ok'],
        ['statusServer', 'ACTIVE', 'ok']
    ];

    foreach ($metrics as $m) {
        $stmt = $mysqli->prepare("INSERT INTO server_app_metrics (server_id, metric_key, metric_value, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $sid, $m[0], $m[1], $m[2]);
        $stmt->execute();
    }

    // 3. Services
    $services = ['JAMS', 'PostgreSQL', 'SSH', 'Apache'];
    foreach ($services as $svc) {
        $status = ($sid % 7 === 0 && $svc === 'JAMS') ? 'stopped' : 'active';
        $stmt = $mysqli->prepare("INSERT INTO server_services (server_id, service_name, status, message, last_checked) VALUES (?, ?, ?, 'Running', NOW())");
        $stmt->bind_param("iss", $sid, $svc, $status);
        $stmt->execute();
    }
}

echo "Seeding completed successfully.\n";
?>
