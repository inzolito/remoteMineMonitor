<?php
/**
 * bootstrap_virtual_metrics.php
 * 
 * One-time CLI utility to retroactively calculate virtual metrics
 * from data that already exists in server_app_metrics.
 * 
 * Uses the SAME processMetric() pipeline as the real agent POST,
 * so MetricsEvaluator runs, alert_rules are checked, and alerts
 * are inserted into the alerts table naturally.
 * 
 * Usage: php bootstrap_virtual_metrics.php
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes/MetricsEvaluator.php';

$db = (new DB())->getConnection();
$evaluator = new MetricsEvaluator($db);

// Inline version of processMetric (simplified, no history insert, no logging)
function triggerVirtualMetric($db, $evaluator, $server_id, $site_id, $key, $val) {
    $metric_id = null;
    $check = $db->query("SELECT id FROM metrics WHERE name = '" . $db->real_escape_string($key) . "' LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        $db->query("INSERT INTO metrics (name, display_name) VALUES ('" . $db->real_escape_string($key) . "', '" . $db->real_escape_string($key) . "')");
        $metric_id = $db->insert_id;
    } else {
        $metric_id = $check->fetch_assoc()['id'];
    }

    $status = $evaluator->evaluate($key, $val, $server_id, $metric_id);

    $stmt = $db->prepare("INSERT INTO server_app_metrics (server_id, metric_key, metric_value, status, last_updated) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value), status = VALUES(status), last_updated = NOW()");
    $stmt->bind_param("isss", $server_id, $key, $val, $status);
    $stmt->execute();

    echo "  [server_id=$server_id] $key = $val → status: $status\n";
    return $status;
}

// -------------------------------------------------------
// 1. Virtual Metric: db.shifts.max_diff (per site)
// -------------------------------------------------------
echo "\n=== Calculating db.shifts.max_diff per site ===\n";

$sitesRes = $db->query("SELECT DISTINCT site_id FROM site_servers");
while ($siteRow = $sitesRes->fetch_assoc()) {
    $site_id = (int)$siteRow['site_id'];

    $res = $db->query(
        "SELECT sam.server_id, sam.metric_value
         FROM server_app_metrics sam
         JOIN site_servers ss ON sam.server_id = ss.server_id
         WHERE ss.site_id = $site_id AND sam.metric_key = 'db.tables.shifts'"
    );

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }

    if (count($rows) < 2) {
        echo "Site $site_id: less than 2 servers with db.tables.shifts — skip\n";
        continue;
    }

    $parseMap = function($v) {
        $res = [];
        if (preg_match_all('/([a-zA-Z0-9_]+)\|(\d+)/', (string)$v, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) $res[$match[1]] = (int)$match[2];
        }
        return $res;
    };

    $mapA = $parseMap($rows[0]['metric_value']);
    $mapB = $parseMap($rows[1]['metric_value']);
    $serverA = (int)$rows[0]['server_id'];
    $serverB = (int)$rows[1]['server_id'];

    $maxDiff = 0;
    $maxTable = '-';
    foreach ($mapA as $table => $countA) {
        $diff = abs($countA - ($mapB[$table] ?? 0));
        if ($diff > $maxDiff) { $maxDiff = $diff; $maxTable = $table; }
    }

    echo "Site $site_id: maxDiff = $maxDiff (table: $maxTable)\n";

    // Feed through the evaluator for BOTH servers in the pair
    triggerVirtualMetric($db, $evaluator, $serverA, $site_id, 'db.shifts.max_diff', $maxDiff);
    triggerVirtualMetric($db, $evaluator, $serverB, $site_id, 'db.shifts.max_diff', $maxDiff);
}

// -------------------------------------------------------
// 2. Virtual Metric: app.fms.cluster 'None' re-evaluation
// -------------------------------------------------------
echo "\n=== Re-evaluating app.fms.cluster ===\n";

$clusterRes = $db->query(
    "SELECT sam.server_id, ss.site_id, sam.metric_value
     FROM server_app_metrics sam
     JOIN site_servers ss ON sam.server_id = ss.server_id
     WHERE sam.metric_key = 'app.fms.cluster'"
);
while ($row = $clusterRes->fetch_assoc()) {
    $val = $row['metric_value'] ?: 'None';
    triggerVirtualMetric($db, $evaluator, (int)$row['server_id'], (int)$row['site_id'], 'app.fms.cluster', $val);
}

echo "\n=== Done. Check alerts table for new active entries. ===\n";

// Show result
$alertRes = $db->query("SELECT id, server_id, metric_key, title, status, created_at FROM alerts WHERE status = 'active' ORDER BY created_at DESC LIMIT 10");
echo "\nActive alerts now:\n";
while ($a = $alertRes->fetch_assoc()) {
    echo "  [{$a['id']}] server={$a['server_id']} key={$a['metric_key']} → {$a['title']}\n";
}
echo "\n";
