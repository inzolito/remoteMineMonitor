<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('America/Santiago');

require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

JWT::$leeway = 7200; // 2 hours leeway for time drift

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helper.php';

$secret_key = "MONITOREO_LAB_V3_SECRET_KEY_CHANGE_ME_IN_PROD";
$database = new DB();
$mysqli = $database->getConnection();

// Authenticate user
$auth = require_auth($mysqli);

// Helper to perform automatic shift rollover if past cycle end
function check_and_perform_rollover($mysqli) {
    $config_res = $mysqli->query("SELECT * FROM shift_config LIMIT 1");
    if (!$config_res || $config_res->num_rows === 0) {
        return;
    }
    $config = $config_res->fetch_assoc();
    $start_hour = $config['start_hour'] ?? '08:00:00';
    $now = new DateTime();

    $mysqli->begin_transaction();
    try {
        while (true) {
            $cycle_end = new DateTime($config['start_date'] . ' ' . $start_hour);
            $cycle_end->modify('+7 days');

            if ($now >= $cycle_end) {
                $current_active = intval($config['active_shift']);
                $next_shift = ($current_active === 1) ? 2 : 1;
                $next_start_date = $cycle_end->format('Y-m-d');

                // Swap roles for the LEAVING shift (which is $current_active)
                $mysqli->query("UPDATE users SET turno_tipo = CASE WHEN turno_tipo = 'Día' THEN 'Noche' ELSE 'Día' END WHERE turno_7x7 = $current_active");

                // Update config
                $stmt = $mysqli->prepare("UPDATE shift_config SET active_shift = ?, start_date = ?");
                $stmt->bind_param("is", $next_shift, $next_start_date);
                $stmt->execute();
                $stmt->close();

                // Update local array for next iteration
                $config['active_shift'] = $next_shift;
                $config['start_date'] = $next_start_date;
            } else {
                break;
            }
        }
        $mysqli->commit();
    } catch (Exception $e) {
        $mysqli->rollback();
    }
}

// Check and perform auto-rollover on every API request
check_and_perform_rollover($mysqli);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 1. Get shift configuration
    $config_res = $mysqli->query("SELECT * FROM shift_config LIMIT 1");
    $config = null;
    if ($config_res && $config_res->num_rows > 0) {
        $config = $config_res->fetch_assoc();
    } else {
        // Fallback default config if missing
        $config = [
            'active_shift' => 1,
            'shift1_alias' => 'Turno A',
            'shift2_alias' => 'Turno B',
            'start_date' => '2026-05-13',
            'start_hour' => '08:00:00',
            'end_hour' => '20:00:00'
        ];
    }

    // Convert values
    $config['active_shift'] = intval($config['active_shift']);

    // Calculate days elapsed (Wednesday to Tuesday)
    $startDate = new DateTime($config['start_date']);
    $today = new DateTime();
    $startDate->setTime(0, 0, 0);
    $today->setTime(0, 0, 0);

    $diff = $startDate->diff($today)->days;
    // Rotation is 7 days, so we can display days elapsed (e.g. 1 to 7)
    // If it's more than 7, show actual days to indicate handover is overdue
    $days_elapsed = $diff + 1;

    // 2. Fetch members grouped by shift
    $users_res = $mysqli->query("SELECT id, username, first_name, last_name, email, cargo, salesforce_user_id, turno_7x7, turno_tipo FROM users WHERE turno_7x7 IS NOT NULL ORDER BY first_name ASC");
    $members_shift1 = [];
    $members_shift2 = [];
    while ($row = $users_res->fetch_assoc()) {
        $row['turno_7x7'] = intval($row['turno_7x7']);
        $row['turno_tipo'] = $row['turno_tipo'] ?? 'Día';
        if ($row['turno_7x7'] === 1) {
            $members_shift1[] = $row;
        } elseif ($row['turno_7x7'] === 2) {
            $members_shift2[] = $row;
        }
    }

    // Helper function to fetch tickets assigned to a specific shift group
    $get_tickets_for_shift = function($shift_num, $only_open = false) use ($mysqli) {
        $status_filter = $only_open ? "AND LOWER(c.Status) != 'closed'" : "";
        $query = "SELECT c.Id, c.CaseNumber, c.Subject, c.Status, c.Priority, c.CreatedDate, c.ClosedDate, c.Description, c.Resolution,
                         a.internal_faena_alias as Faena, a.Name as AccountName, u.Name as OwnerName, c.OwnerId,
                         (SELECT COUNT(*) FROM rmmsalesforce.sf_case_comments cc WHERE cc.ParentId = c.Id) as CommentCount
                  FROM rmmsalesforce.sf_cases c
                  LEFT JOIN rmmsalesforce.sf_accounts a ON c.AccountId = a.Id
                  LEFT JOIN rmmsalesforce.sf_users u ON c.OwnerId = u.Id
                  WHERE (
                      c.OwnerId IN (
                          SELECT salesforce_user_id COLLATE utf8mb4_unicode_ci 
                          FROM monitoring_system.users 
                          WHERE turno_7x7 = ? AND salesforce_user_id IS NOT NULL AND salesforce_user_id != ''
                      )
                      OR (
                          (c.OwnerId = '00G1I00000249DwUAI' OR u.Name = 'South American Support Q')
                          AND LOWER(c.Status) != 'closed'
                      )
                  ) AND c.IsDeleted = 0
                  $status_filter
                  ORDER BY c.CreatedDate DESC
                  LIMIT 50";
                  
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $shift_num);
        $stmt->execute();
        $res = $stmt->get_result();
        $tickets = [];
        while ($row = $res->fetch_assoc()) {
            $ownerName = $row['OwnerName'];
            if ($row['OwnerId'] === '00G1I00000249DwUAI' || $ownerName === 'South American Support Q') {
                $ownerName = 'South American Support Q';
            }
            $tickets[] = [
                'CaseId' => $row['Id'],
                'CaseNumber' => $row['CaseNumber'],
                'Subject' => $row['Subject'] ?? '(Sin Asunto)',
                'Status' => $row['Status'],
                'Priority' => $row['Priority'],
                'CreatedDate' => $row['CreatedDate'],
                'ClosedDate' => $row['ClosedDate'],
                'Description' => $row['Description'] ?? '',
                'Resolution' => $row['Resolution'] ?? '',
                'Faena' => $row['Faena'] ?? '',
                'AccountName' => $row['AccountName'] ?? 'N/A',
                'OwnerName' => $ownerName ?? 'Mi Cuenta',
                'CommentCount' => (int)($row['CommentCount'] ?? 0)
            ];
        }
        return $tickets;
    };

    $active_tickets = $get_tickets_for_shift($config['active_shift'], false);
    $inactive_shift_num = $config['active_shift'] === 1 ? 2 : 1;
    $inactive_tickets = $get_tickets_for_shift($inactive_shift_num, true);

    echo json_encode([
        'config' => [
            'active_shift' => $config['active_shift'],
            'shift1_alias' => $config['shift1_alias'],
            'shift2_alias' => $config['shift2_alias'],
            'start_date' => $config['start_date'],
            'start_hour' => $config['start_hour'] ?? '08:00:00',
            'end_hour' => $config['end_hour'] ?? '20:00:00',
            'days_elapsed' => $days_elapsed
        ],
        'shift1_members' => $members_shift1,
        'shift2_members' => $members_shift2,
        'active_tickets' => $active_tickets,
        'inactive_tickets' => $inactive_tickets
    ]);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $action = $_GET['action'] ?? 'update_config';

    if ($action === 'traspaso') {
        // Swap shift and set start date to Wednesday of this week (or today if Wednesday)
        $config_res = $mysqli->query("SELECT active_shift FROM shift_config LIMIT 1");
        $current_shift = 1;
        if ($config_res && $config_res->num_rows > 0) {
            $row = $config_res->fetch_assoc();
            $current_shift = intval($row['active_shift']);
        }
        $new_shift = $current_shift === 1 ? 2 : 1;

        $mysqli->begin_transaction();
        try {
            // Swap roles for the LEAVING shift group (which is $current_shift)
            $mysqli->query("UPDATE users SET turno_tipo = CASE WHEN turno_tipo = 'Día' THEN 'Noche' ELSE 'Día' END WHERE turno_7x7 = $current_shift");

            // Default to the most recent Wednesday
            $today = new DateTime();
            $dayOfWeek = (int)$today->format('w'); // 0 (Sun) to 6 (Sat)
            $daysToSubtract = ($dayOfWeek - 3 + 7) % 7;
            $lastWednesday = clone $today;
            $lastWednesday->modify("-$daysToSubtract days");
            $new_start_date = $lastWednesday->format('Y-m-d');

            // Allow custom date override if provided
            if (isset($data->start_date) && !empty($data->start_date)) {
                $new_start_date = $mysqli->real_escape_string($data->start_date);
            }

            $stmt = $mysqli->prepare("UPDATE shift_config SET active_shift = ?, start_date = ?");
            $stmt->bind_param("is", $new_shift, $new_start_date);
            $stmt->execute();
            $stmt->close();
            $mysqli->commit();

            echo json_encode([
                "status" => "success",
                "message" => "Traspaso de turno realizado correctamente.",
                "new_shift" => $new_shift,
                "start_date" => $new_start_date
            ]);
        } catch (Exception $e) {
            $mysqli->rollback();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error al realizar el traspaso: " . $e->getMessage()]);
        }
        exit();

    } elseif ($action === 'update_members') {
        // Receives arrays of user objects for shift 1 and shift 2
        // E.g. shift1_members = [['id' => 6, 'tipo' => 'Día'], ['id' => 12, 'tipo' => 'Noche']]
        // Users not in these lists but with turno_7x7 set will be cleared (turno_7x7 = NULL)
        if (!isset($data->shift1_members) || !is_array($data->shift1_members) || !isset($data->shift2_members) || !is_array($data->shift2_members)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Faltan listas de integrantes para turno 1 y turno 2."]);
            exit();
        }

        $mysqli->begin_transaction();
        try {
            // Reset all turno_7x7 to NULL for support engineers
            $mysqli->query("UPDATE users SET turno_7x7 = NULL");

            // Update shift 1 members
            if (!empty($data->shift1_members)) {
                foreach ($data->shift1_members as $m) {
                    $uid = intval($m->id);
                    $tipo = $mysqli->real_escape_string($m->tipo ?? 'Día');
                    $mysqli->query("UPDATE users SET turno_7x7 = 1, turno_tipo = '$tipo' WHERE id = $uid");
                }
            }

            // Update shift 2 members
            if (!empty($data->shift2_members)) {
                foreach ($data->shift2_members as $m) {
                    $uid = intval($m->id);
                    $tipo = $mysqli->real_escape_string($m->tipo ?? 'Día');
                    $mysqli->query("UPDATE users SET turno_7x7 = 2, turno_tipo = '$tipo' WHERE id = $uid");
                }
            }

            $mysqli->commit();
            echo json_encode(["status" => "success", "message" => "Integrantes de turnos actualizados correctamente."]);
        } catch (Exception $e) {
            $mysqli->rollback();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error al actualizar integrantes: " . $e->getMessage()]);
        }
        exit();

    } else {
        // Normal configuration update (Aliases, Active Shift, custom start date, hours)
        if (!isset($data->shift1_alias) || !isset($data->shift2_alias) || !isset($data->active_shift) || !isset($data->start_date)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Incomplete configuration data."]);
            exit();
        }

        $active_shift = intval($data->active_shift);
        $s1_alias = $mysqli->real_escape_string($data->shift1_alias);
        $s2_alias = $mysqli->real_escape_string($data->shift2_alias);
        $start_date = $mysqli->real_escape_string($data->start_date);
        $start_hour = $mysqli->real_escape_string($data->start_hour ?? '08:00:00');
        $end_hour = $mysqli->real_escape_string($data->end_hour ?? '20:00:00');

        // Check if there is already a config row
        $check = $mysqli->query("SELECT id FROM shift_config LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $stmt = $mysqli->prepare("UPDATE shift_config SET active_shift = ?, shift1_alias = ?, shift2_alias = ?, start_date = ?, start_hour = ?, end_hour = ?");
            $stmt->bind_param("isssss", $active_shift, $s1_alias, $s2_alias, $start_date, $start_hour, $end_hour);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO shift_config (active_shift, shift1_alias, shift2_alias, start_date, start_hour, end_hour) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $active_shift, $s1_alias, $s2_alias, $start_date, $start_hour, $end_hour);
        }

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Configuración de turnos guardada exitosamente."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error al guardar la configuración."]);
        }
    }
}
?>
