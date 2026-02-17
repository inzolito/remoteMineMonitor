<?php
/**
 * Get Commands API
 * Returns all commands for a server's enabled metrics, ordered by priority
 * Used by monitoring agents to fetch commands with fallback support
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$database = new DB();
$mysqli = $database->getConnection();

// Get server_id from request
$server_id = isset($_GET['server_id']) ? intval($_GET['server_id']) : null;

if (!$server_id) {
    echo json_encode(['error' => 'server_id is required']);
    exit;
}


// Get server OS family
$serverRes = $mysqli->query("SELECT os_type FROM servers WHERE id = $server_id");
if (!$serverRes || $serverRes->num_rows === 0) {
    echo json_encode(['error' => 'Server not found']);
    exit;
}
$server = $serverRes->fetch_assoc();
$os_family = strtolower($server['os_type'] ?? 'linux');

// Fetch all enabled metrics for this server with their commands
$query = "
    SELECT 
        m.id as metric_id,
        m.name as metric_name,
        m.display_name,
        rc.id as command_id,
        rc.command,
        rc.priority,
        rc.timeout_seconds,
        rc.os_family,
        rc.frequency_cycle,
        smc.frequency
    FROM server_metrics_config smc
    JOIN metrics m ON smc.metric_id = m.id
    LEFT JOIN remote_commands rc ON rc.metric_id = m.id
    WHERE smc.server_id = $server_id
      AND smc.is_enabled = 1
      AND (rc.os_family = '$os_family' OR rc.os_family IS NULL OR rc.os_family = 'any')
    ORDER BY m.id, rc.priority ASC
";

$result = $mysqli->query($query);
if (!$result) {
    echo json_encode(['error' => 'Database query failed: ' . $mysqli->error]);
    exit;
}

// Group commands by metric
$metrics = [];
while ($row = $result->fetch_assoc()) {
    $metric_id = $row['metric_id'];
    
    if (!isset($metrics[$metric_id])) {
        // Prioritize server-specific frequency, then command default frequency, then 60
        $freq = $row['frequency'] ?? $row['frequency_cycle'] ?? 60;
        
        $metrics[$metric_id] = [
            'metric_id' => $metric_id,
            'metric_name' => $row['metric_name'],
            'display_name' => $row['display_name'],
            'frequency' => $freq,
            'commands' => []
        ];
    }
    
    if ($row['command_id']) {
        $metrics[$metric_id]['commands'][] = [
            'id' => $row['command_id'],
            'command' => $row['command'],
            'priority' => $row['priority'],
            'timeout' => $row['timeout_seconds']
        ];
    }
}

// Convert to array
$output = [
    'server_id' => $server_id,
    'os_family' => $os_family,
    'metrics' => array_values($metrics)
];

echo json_encode($output, JSON_PRETTY_PRINT);
