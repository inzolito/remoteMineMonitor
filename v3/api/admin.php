<?php
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/classes/MetricsEvaluator.php';

$secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD";
// Direct connection to bypass potential class autoloading weirdness or state issues
$mysqli = new mysqli('localhost', 'jigsaw', 'Jigsaw1', 'monitoring_system');
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "DB Connection Failed: " . $mysqli->connect_error]);
    exit();
}

// --- AUTHENTICATION ---
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$jwt = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $jwt = $matches[1];
}

if (!$jwt) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized"]);
    exit();
}

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
} catch (\Firebase\JWT\ExpiredException $e) {
    // Expired but valid signature? Check 'maik'.
    $tks = explode('.', $jwt);
    if (count($tks) === 3) {
        $payload = json_decode(base64_decode(strTr($tks[1], '-_', '+/')));
        if (isset($payload->data->username) && $payload->data->username === 'maik') {
            $decoded = $payload;
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Session expired"]);
            exit();
        }
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Invalid token structure"]);
        exit();
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid token: " . $e->getMessage()]);
    exit();
}

if ($decoded->data->username !== 'maik') {
    http_response_code(403);
    echo json_encode(["message" => "Forbidden"]);
    exit();
}
// ----------------------

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $data = [];
    if ($action === 'metrics') {
        // Raw procedural call to avoid object weirdness if any
        $res = mysqli_query($mysqli, "SELECT * FROM metrics ORDER BY name ASC");
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
    } elseif ($action === 'server_types') {
        $res = mysqli_query($mysqli, "SELECT * FROM server_types ORDER BY name ASC");
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
    } elseif ($action === 'commands') {
        $metric_id = isset($_GET['metric_id']) ? intval($_GET['metric_id']) : 0;
        $sql = "SELECT * FROM remote_commands";
        if ($metric_id > 0) $sql .= " WHERE metric_id = $metric_id";
        $sql .= " ORDER BY priority ASC";
        $res = mysqli_query($mysqli, $sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
    } elseif ($action === 'template_metrics') {
        $type_id = isset($_GET['type_id']) ? ($_GET['type_id'] === 'orphans' ? 'orphans' : intval($_GET['type_id'])) : 0;
        
        if ($type_id === 'orphans') {
            // Metrics NOT assigned to ANY server profile
            $sql = "SELECT m.id as metric_id, m.name as metric_name, m.display_name, NULL as default_frequency,
                           (SELECT command FROM remote_commands rc WHERE rc.metric_id = m.id LIMIT 1) as command_preview 
                    FROM metrics m 
                    WHERE m.id NOT IN (SELECT DISTINCT metric_id FROM server_type_metrics)
                    ORDER BY m.name ASC";
        } else {
            // Standard assignment List
            $sql = "SELECT stm.*, m.name as metric_name, m.display_name,
                           (SELECT command FROM remote_commands rc WHERE rc.metric_id = m.id LIMIT 1) as command_preview
                    FROM server_type_metrics stm
                    JOIN metrics m ON stm.metric_id = m.id
                    WHERE stm.server_type_id = $type_id
                    ORDER BY m.name ASC";
        }
        $res = mysqli_query($mysqli, $sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }

    } elseif ($action === 'metric_details') {
        $mid = intval($_GET['metric_id'] ?? 0);
        $pattern = $_GET['pattern'] ?? '';
        
        if ($mid === 0 && !empty($pattern)) {
            // Try to find a metric that matches the pattern (removing % for search if needed)
            $searchPattern = str_replace('%', '', $pattern);
            $pRes = mysqli_query($mysqli, "SELECT id FROM metrics WHERE name LIKE '%$searchPattern%' LIMIT 1");
            if ($row = mysqli_fetch_assoc($pRes)) {
                $mid = intval($row['id']);
            }
        }

        // 1. Metric Info
        $mRes = mysqli_query($mysqli, "SELECT * FROM metrics WHERE id = $mid");
        $metric = mysqli_fetch_assoc($mRes) ?: ['id' => 0, 'name' => $pattern, 'display_name' => $pattern, 'description' => 'Métrica definida por patrón (Legacy)'];
        
        // 2. Command
        $cRes = mysqli_query($mysqli, "SELECT * FROM remote_commands WHERE metric_id = $mid ORDER BY priority ASC LIMIT 1");
        $command = mysqli_fetch_assoc($cRes);
        
        // 3. Profiles (Templates) using it
        $profiles = [];
        if ($mid > 0) {
            $pRes = mysqli_query($mysqli, "SELECT st.name, stm.default_frequency FROM server_type_metrics stm JOIN server_types st ON stm.server_type_id = st.id WHERE stm.metric_id = $mid");
            while ($r = mysqli_fetch_assoc($pRes)) $profiles[] = $r;
        }
        
        // 4. Servers effectively using it (via Profile)
        $servers = [];
        if ($mid > 0) {
            $sRes = mysqli_query($mysqli, "SELECT s.name, s.ip FROM servers s JOIN server_type_metrics stm ON s.server_type_id = stm.server_type_id WHERE stm.metric_id = $mid AND s.is_deleted = 0 ORDER BY s.name ASC");
            while ($r = mysqli_fetch_assoc($sRes)) $servers[] = $r;
        }
        
        $data = [
            'metric' => $metric,
            'command' => $command,
            'profiles' => $profiles,
            'servers' => $servers
        ];
    } elseif ($action === 'servers') {
        $sql = "SELECT s.id, s.name, s.ip as ip_address, s.server_type_id, s.status, 
                       st.name as type_name, 
                       si.name as site_name, si.conglomerate 
                FROM servers s 
                LEFT JOIN server_types st ON s.server_type_id = st.id 
                LEFT JOIN site_servers ss ON s.id = ss.server_id 
                LEFT JOIN sites si ON ss.site_id = si.id
                WHERE s.is_deleted = 0 
                ORDER BY 
                    CASE 
                        WHEN si.conglomerate = 'AMSA' THEN 1 
                        WHEN si.conglomerate LIKE 'Codelco%' THEN 2 
                        ELSE 3 
                    END,
                    si.name ASC, 
                    s.name ASC";
        $res = mysqli_query($mysqli, $sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
    } elseif ($action === 'alert_rules') {
        $sql = "SELECT ar.*, m.name as metric_technical_name, m.display_name as metric_display_name 
                FROM alert_rules ar 
                LEFT JOIN metrics m ON ar.metric_id = m.id 
                ORDER BY ar.priority DESC, m.name ASC";
        $res = mysqli_query($mysqli, $sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
    } elseif ($action === 'connections') {
        $res = mysqli_query($mysqli, "SELECT c.*, s.name as server_name FROM connections c JOIN servers s ON c.server_id = s.id WHERE s.is_deleted = 0");
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
    } elseif ($action === 'connection_details') {
        $sid = intval($_GET['server_id'] ?? 0);
        $cRes = mysqli_query($mysqli, "SELECT * FROM connections WHERE server_id = $sid LIMIT 1");
        $connection = mysqli_fetch_assoc($cRes);
        
        $paths = [];
        if ($connection) {
            $cid = $connection['id'];
            $pRes = mysqli_query($mysqli, "SELECT cp.*, s.name as jump_server_name, s.ip as jump_server_ip 
                                         FROM connection_paths cp 
                                         LEFT JOIN servers s ON cp.jump_server_id = s.id 
                                         WHERE cp.connection_id = $cid 
                                         ORDER BY cp.jump_order ASC");

            while ($row = mysqli_fetch_assoc($pRes)) $paths[] = $row;
        }
        
        $data = [
            'connection' => $connection,
            'paths' => $paths
        ];
    }
    echo json_encode($data);

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if ($action === 'metrics') {
        $name = $mysqli->real_escape_string($input['name']);
        $disp = $mysqli->real_escape_string($input['display_name'] ?? '');
        $desc = $mysqli->real_escape_string($input['description'] ?? '');
        
        $res = false;
        if (isset($input['id'])) {
            $id = intval($input['id']);
            $res = $mysqli->query("UPDATE metrics SET name='$name', display_name='$disp', description='$desc' WHERE id=$id");
        } else {
            $res = $mysqli->query("INSERT INTO metrics (name, display_name, description) VALUES ('$name', '$disp', '$desc')");
        }
        
        if ($res) {
            echo json_encode(["message" => "Saved"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "DB Error: " . $mysqli->error]);
        }
        
    } elseif ($action === 'commands') {
        $mid = intval($input['metric_id']);
        $cmd = $mysqli->real_escape_string($input['command']);
        $prio = intval($input['priority'] ?? 1);
        $to = intval($input['timeout_seconds'] ?? 30);
        $os = $mysqli->real_escape_string($input['os_family'] ?? 'linux');
        
        $res = false;
        if (isset($input['id'])) {
            $id = intval($input['id']);
            $res = $mysqli->query("UPDATE remote_commands SET command='$cmd', priority=$prio, timeout_seconds=$to, os_family='$os' WHERE id=$id");
        } else {
            // Generate Defaults for missing NOT NULL columns
            $uniqSeed = substr(md5(uniqid()), 0, 6);
            $genName = "Cmd_{$mid}_{$prio}_{$uniqSeed}"; 
            $monType = 'insert_always';
            $cmdType = (stripos($cmd, 'SELECT') === 0 || stripos($cmd, 'WITH') === 0) ? 'sql' : 'bash';
            
            $res = $mysqli->query("INSERT INTO remote_commands (name, metric_id, command, priority, timeout_seconds, os_family, monitoring_type, command_type, is_active) VALUES ('$genName', $mid, '$cmd', $prio, $to, '$os', '$monType', '$cmdType', 1)");
        }
        
        if ($res) {
            echo json_encode(["message" => "Saved"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "DB Error: " . $mysqli->error]);
        }

    } elseif ($action === 'template_metrics') {
        $tid = intval($input['server_type_id']);
        $mid = intval($input['metric_id']);
        $freq = intval($input['default_frequency'] ?? 60);
        
        // Use INSERT IGNORE, but if it fails due to other reasons like bad ID?
        $res = $mysqli->query("INSERT IGNORE INTO server_type_metrics (server_type_id, metric_id, default_frequency) VALUES ($tid, $mid, $freq)");
        
        if ($res) {
             echo json_encode(["message" => "Assigned"]);
        } else {
             http_response_code(500);
             echo json_encode(["message" => "DB Error: " . $mysqli->error]);
        }
    } elseif ($action === 'alert_rules') {
        $mid = isset($input['metric_id']) ? intval($input['metric_id']) : 'NULL';
        $pattern = $mysqli->real_escape_string($input['metric_pattern'] ?? '');
        $type = $mysqli->real_escape_string($input['rule_type'] ?? 'threshold');
        $op = $mysqli->real_escape_string($input['operator'] ?? '>');
        $val = $mysqli->real_escape_string($input['threshold_value'] ?? '');
        $cat = $mysqli->real_escape_string($input['alert_category'] ?? 'warning');
        $desc = $mysqli->real_escape_string($input['description'] ?? '');
        $prio = intval($input['priority'] ?? 1);

        if (isset($input['id'])) {
            $id = intval($input['id']);
            $sql = "UPDATE alert_rules SET metric_id = $mid, metric_pattern = '$pattern', rule_type = '$type', operator = '$op', threshold_value = '$val', alert_category = '$cat', description = '$desc', priority = $prio WHERE id = $id";
        } else {
            $sql = "INSERT INTO alert_rules (metric_id, metric_pattern, rule_type, operator, threshold_value, alert_category, description, priority) VALUES ($mid, '$pattern', '$type', '$op', '$val', '$cat', '$desc', $prio)";
        }

        if ($mysqli->query($sql)) {
            echo json_encode(["message" => "Rule saved"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "DB Error: " . $mysqli->error]);
        }
    } elseif ($action === 'connections') {
        $sid = intval($input['server_id']);
        $name = $mysqli->real_escape_string($input['connection_name'] ?? '');
        $cst = intval($input['connection_status'] ?? 0);
        
        $check = $mysqli->query("SELECT id FROM connections WHERE server_id = $sid");
        if ($row = $check->fetch_assoc()) {
            $cid = $row['id'];
            $res = $mysqli->query("UPDATE connections SET connection_name = '$name', connection_status = $cst WHERE id = $cid");
        } else {
            $res = $mysqli->query("INSERT INTO connections (server_id, connection_name, connection_status) VALUES ($sid, '$name', $cst)");
            $cid = $mysqli->insert_id;
        }
        
        if ($res) {
            echo json_encode(["message" => "Connection saved", "id" => $cid]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "DB Error: " . $mysqli->error]);
        }
    } elseif ($action === 'connection_paths') {
        $cid = intval($input['connection_id']);
        $hops = $input['hops']; // Array of jump_server_id
        
        // Transactional overwrite
        $mysqli->begin_transaction();
        try {
            $mysqli->query("DELETE FROM connection_paths WHERE connection_id = $cid");
            $order = 1;
            foreach ($hops as $jsid) {
                $jsid = intval($jsid);
                $mysqli->query("INSERT INTO connection_paths (connection_id, jump_server_id, jump_order) VALUES ($cid, $jsid, $order)");
                $order++;
            }
            $mysqli->commit();
            echo json_encode(["message" => "Path updated"]);
        } catch (Exception $e) {
            $mysqli->rollback();
            http_response_code(500);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    }

} elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);
    if ($action === 'servers') {
        $sid = intval($input['id']);
        $tid = intval($input['server_type_id']);
        $mysqli->query("UPDATE servers SET server_type_id = $tid WHERE id = $sid");
        
        require_once __DIR__ . '/sync_type_metrics.php';
        if (function_exists('syncServerTemplates')) {
            syncServerTemplates($sid);
        }
        echo json_encode(["message" => "Updated"]);
    }

} elseif ($method === 'DELETE') {
    $id = intval($_GET['id']);
    if ($action === 'metrics') {
        // Deep delete: remove related commands and rules linked by ID
        mysqli_query($mysqli, "DELETE FROM remote_commands WHERE metric_id = $id");
        mysqli_query($mysqli, "DELETE FROM alert_rules WHERE metric_id = $id");
        mysqli_query($mysqli, "DELETE FROM server_metric_values WHERE metric_id = $id");
        mysqli_query($mysqli, "DELETE FROM server_type_metrics WHERE metric_id = $id");
        $mysqli->query("DELETE FROM metrics WHERE id = $id");
    } elseif ($action === 'commands') {
        $mysqli->query("DELETE FROM remote_commands WHERE id = $id");
    } elseif ($action === 'template_metrics') {
        $tid = intval($_GET['type_id']);
        $mid = intval($_GET['metric_id']);
        $mysqli->query("DELETE FROM server_type_metrics WHERE server_type_id = $tid AND metric_id = $mid");
    } elseif ($action === 'alert_rules') {
        $mysqli->query("DELETE FROM alert_rules WHERE id = $id");
    }
    echo json_encode(["message" => "Deleted"]);
}
?>
