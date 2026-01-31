<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes/MetricsEvaluator.php';

try {
    $db = (new DB())->getConnection();
    $evaluator = new MetricsEvaluator($db);

    // [NEW] Sync Legacy Data for all servers to ensure we have latest values
    $servers = $db->query("SELECT s.id, s.ip_address FROM servers s WHERE s.is_deleted=0");
    while ($server = $servers->fetch_assoc()) {
        $server_id = $server['id'];
        $server_ip = $server['ip_address'];
        
        if ($server_ip) {
             // 1. Find Legacy ID
            $leg_stmt = $db->prepare("SELECT s.id FROM checksupport.servidores s WHERE s.ip = ? LIMIT 1");
            $leg_stmt->bind_param("s", $server_ip);
            $leg_stmt->execute();
            $leg_row = $leg_stmt->get_result()->fetch_assoc();
            
            if ($leg_row) {
                $legacy_id = $leg_row['id'];
                
                // 2. Fetch Values
                $met_query = "
                    SELECT m.simbolo, 
                           (SELECT valor FROM checksupport.metricas_servidores_valor mv 
                            WHERE mv.id_metrica_servidor = ms.id 
                            ORDER BY mv.updated_at DESC LIMIT 1) as valor
                    FROM checksupport.metricas_servidores ms
                    JOIN checksupport.metricas m ON ms.id_metrica = m.id
                    WHERE ms.id_servidor = ?
                ";
                $met_stmt = $db->prepare($met_query);
                $met_stmt->bind_param("i", $legacy_id);
                $met_stmt->execute();
                $met_res = $met_stmt->get_result();
                
                while ($m = $met_res->fetch_assoc()) {
                    if ($m['valor'] !== null) {
                        $key = $m['simbolo']; // e.g., loadAverage
                        $val = $m['valor'];
                        
                        // Map specific legacy keys to our standard V3 keys
                        if ($key === 'loadAverage') {
                            $key = 'system.load.average';
                            $val = floatval(explode(',', $val)[0]);
                        }
                        // Add more mappings if needed
                        
                        // UPSERT into server_app_metrics
                        // First delete old to ensure clean state (and fix duplicates)
                         $db->query("DELETE FROM server_app_metrics WHERE server_id = $server_id AND metric_key = '$key'");
                         
                         // Evaluate status immediately
                         $status = $evaluator->evaluate($key, $val);
                         
                         $ins = $db->prepare("INSERT INTO server_app_metrics (server_id, metric_key, metric_value, status, last_updated) VALUES (?, ?, ?, ?, NOW())");
                         $ins->bind_param("isss", $server_id, $key, $val, $status);
                         $ins->execute();
                    }
                }
            }
        }
    }

    // Get all metrics (now updated with legacy)
    $stmt = $db->query("SELECT id, metric_key, metric_value FROM server_app_metrics");
    $count = 0;
    $updated = 0;

    while ($row = $stmt->fetch_assoc()) {
        $status = $evaluator->evaluate($row['metric_key'], $row['metric_value']);
        
        // Update status
        $update = $db->prepare("UPDATE server_app_metrics SET status = ? WHERE id = ?");
        $update->bind_param("si", $status, $row['id']);
        $update->execute();
        $updated++;
    }

    echo json_encode(['status' => 'success', 'message' => "Re-evaluated $updated metrics against current rules."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
