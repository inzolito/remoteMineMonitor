<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('America/Santiago');

require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

JWT::$leeway = 7200;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helper.php';

$database = new DB();
$mysqli = $database->getConnection();

// Authenticate user
$auth = require_auth($mysqli);

// ─────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────

/**
 * Returns the current active shift cycle from shift_cycles.
 * Falls back to shift_config if new tables aren't seeded yet.
 */
function get_active_cycle($mysqli) {
    $res = $mysqli->query("SELECT sc.*, sg.alias as group_alias, sg.color_hex, cfg.start_hour, cfg.end_hour, cfg.shift1_alias, cfg.shift2_alias
        FROM shift_cycles sc
        JOIN shift_groups sg ON sg.id = sc.active_group_id
        CROSS JOIN shift_config cfg
        WHERE sc.end_datetime IS NULL
        ORDER BY sc.start_datetime DESC
        LIMIT 1");
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    // fallback
    $res2 = $mysqli->query("SELECT * FROM shift_config LIMIT 1");
    if (!$res2 || $res2->num_rows === 0) return null;
    $cfg = $res2->fetch_assoc();
    return [
        'id'             => null,
        'active_group_id'=> intval($cfg['active_shift']),
        'start_datetime' => $cfg['start_date'] . ' ' . ($cfg['start_hour'] ?? '08:00:00'),
        'end_datetime'   => null,
        'group_alias'    => $cfg['active_shift'] == 1 ? $cfg['shift1_alias'] : $cfg['shift2_alias'],
        'color_hex'      => '#3b82f6',
        'start_hour'     => $cfg['start_hour'] ?? '08:00:00',
        'end_hour'       => $cfg['end_hour'] ?? '20:00:00',
        'shift1_alias'   => $cfg['shift1_alias'],
        'shift2_alias'   => $cfg['shift2_alias'],
    ];
}

/**
 * Determine current sub-shift (Día / Noche) based on config hours.
 */
function get_current_sub_shift($start_hour_str, $end_hour_str) {
    $now_h = intval((new DateTime())->format('H'));
    $start_h = intval(explode(':', $start_hour_str)[0]);
    $end_h   = intval(explode(':', $end_hour_str)[0]);
    if ($start_h < $end_h) {
        return ($now_h >= $start_h && $now_h < $end_h) ? 'Día' : 'Noche';
    }
    return ($now_h >= $start_h || $now_h < $end_h) ? 'Día' : 'Noche';
}

/**
 * Fetch members of a shift group from shift_group_members (new) or users (fallback).
 */
function get_group_members($mysqli, $group_id) {
    // Determine mathematical cycle to know if we should invert the base roles
    // Reference date: Wednesday 2026-05-27 08:00:00 (Start of Cycle 1)
    $ref_date = strtotime('2026-05-27 08:00:00');
    $now = time();
    $elapsed_weeks = max(0, floor(($now - $ref_date) / (7 * 24 * 3600)));
    
    // Group 1 works on even weeks (0, 2, 4...)
    // Group 2 works on odd weeks (1, 3, 5...)
    // We want the cycle index to represent their current (or upcoming) working week.
    $cycle_index = ($group_id == 1) ? floor(($elapsed_weeks + 1) / 2) : floor($elapsed_weeks / 2);
    $invert_roles = ($cycle_index % 2 != 0);

    $stmt = $mysqli->prepare(
        "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.cargo, u.salesforce_user_id,
                sgm.group_id as turno_7x7, sgm.sub_shift as base_sub_shift
         FROM shift_group_members sgm
         JOIN users u ON u.id = sgm.user_id
         WHERE sgm.group_id = ?
         ORDER BY sgm.sub_shift, u.first_name"
    );
    if (!$stmt) return [];
    $stmt->bind_param('i', $group_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $members = [];
    while ($row = $res->fetch_assoc()) {
        $base = $row['base_sub_shift'];
        if ($invert_roles) {
            $row['turno_tipo'] = ($base === 'Día') ? 'Noche' : 'Día';
        } else {
            $row['turno_tipo'] = $base;
        }
        $members[] = $row;
    }
    return $members;
}

/**
 * Fetch open tickets for a given shift group (from Salesforce via shift_group_members).
 * Also includes South American Support Q open tickets.
 * $cycle_start: only include tickets created on or after this datetime (for active shift),
 *               or all open if null (for inactive shift).
 */
function get_tickets_for_group($mysqli, $group_id, $cycle_start = null, $include_older_open = true) {
    // Build date filter
    $date_clause = '';
    if ($cycle_start) {
        $safe = $mysqli->real_escape_string($cycle_start);
        if ($include_older_open) {
            // Tickets created in this cycle OR older tickets still open
            $date_clause = "AND (c.CreatedDate >= '$safe' OR LOWER(c.Status) != 'closed')";
        } else {
            $date_clause = "AND c.CreatedDate >= '$safe'";
        }
    } else {
        // Inactive group: only open tickets
        $date_clause = "AND LOWER(c.Status) != 'closed'";
    }

    // Pre-fetch active group salesforce_user_ids to avoid slow MySQL subquery with collation mismatch
    $sf_ids = [];
    $active_users_q = "SELECT u2.salesforce_user_id 
                       FROM monitoring_system.shift_group_members sgm
                       JOIN monitoring_system.users u2 ON u2.id = sgm.user_id
                       WHERE sgm.group_id = $group_id
                         AND u2.salesforce_user_id IS NOT NULL 
                         AND u2.salesforce_user_id != ''";
    $sf_res = $mysqli->query($active_users_q);
    if ($sf_res) {
        while ($sf_row = $sf_res->fetch_assoc()) {
            $sf_ids[] = "'" . $mysqli->real_escape_string($sf_row['salesforce_user_id']) . "'";
        }
    }
    
    $in_clause = count($sf_ids) > 0 ? implode(',', $sf_ids) : "'NO_MATCH'";

    $query = "SELECT c.Id, c.CaseNumber, c.Subject, c.Status, c.Priority, c.CreatedDate, c.ClosedDate,
                     c.Description, c.Resolution, c.OwnerId,
                     a.internal_faena_alias as Faena, a.Name as AccountName,
                     u.Name as OwnerName,
                     (SELECT COUNT(*) FROM rmmsalesforce.sf_case_comments cc WHERE cc.ParentId = c.Id) as CommentCount,
                     sta.id as assignment_id, sta.cycle_id as assigned_cycle_id
              FROM rmmsalesforce.sf_cases c
              LEFT JOIN rmmsalesforce.sf_accounts a ON c.AccountId = a.Id
              LEFT JOIN rmmsalesforce.sf_users u ON c.OwnerId = u.Id
              LEFT JOIN monitoring_system.shift_ticket_assignments sta ON sta.case_id = c.Id
              WHERE (
                  c.OwnerId IN ($in_clause)
                  OR (
                      (c.OwnerId = '00G1I00000249DwUAI' OR u.Name = 'South American Support Q')
                      AND LOWER(c.Status) != 'closed'
                  )
              ) AND c.IsDeleted = 0
              $date_clause
              ORDER BY c.CreatedDate DESC";

    $stmt = $mysqli->prepare($query);
    if (!$stmt) return [];
    $stmt->execute();
    $res = $stmt->get_result();
    $tickets = [];
    while ($row = $res->fetch_assoc()) {
        $ownerName = $row['OwnerName'];
        if ($row['OwnerId'] === '00G1I00000249DwUAI' || $ownerName === 'South American Support Q') {
            $ownerName = 'South American Support Q';
        }
        $tickets[] = [
            'CaseId'          => $row['Id'],
            'CaseNumber'      => $row['CaseNumber'],
            'Subject'         => $row['Subject'] ?? '(Sin Asunto)',
            'Status'          => $row['Status'],
            'Priority'        => $row['Priority'],
            'CreatedDate'     => $row['CreatedDate'],
            'ClosedDate'      => $row['ClosedDate'],
            'Description'     => $row['Description'] ?? '',
            'Resolution'      => $row['Resolution'] ?? '',
            'Faena'           => $row['Faena'] ?? '',
            'AccountName'     => $row['AccountName'] ?? 'N/A',
            'OwnerName'       => $ownerName ?? 'Sin Asignar',
            'CommentCount'    => (int)($row['CommentCount'] ?? 0),
            'is_inherited'    => !empty($row['assigned_cycle_id']) ? false : 
                                 ($row['CreatedDate'] < ($cycle_start ?? '9999') ? true : false),
        ];
    }

    // Fetch comments for all tickets in one query
    if (count($tickets) > 0) {
        $caseIds = array_map(function($t) { return "'" . $t['CaseId'] . "'"; }, $tickets);
        $idsStr = implode(',', $caseIds);
        $commentsQuery = "SELECT cc.ParentId, cc.CommentBody, cc.CreatedDate, u.Name as Author 
                          FROM rmmsalesforce.sf_case_comments cc 
                          LEFT JOIN rmmsalesforce.sf_users u ON cc.CreatedById = u.Id 
                          WHERE cc.ParentId IN ($idsStr) 
                          ORDER BY cc.CreatedDate ASC";
        $commentsRes = $mysqli->query($commentsQuery);
        $commentsByTicket = [];
        if ($commentsRes) {
            while ($c = $commentsRes->fetch_assoc()) {
                $pid = $c['ParentId'];
                if (!isset($commentsByTicket[$pid])) $commentsByTicket[$pid] = [];
                $commentsByTicket[$pid][] = [
                    'Body' => $c['CommentBody'],
                    'CreatedDate' => $c['CreatedDate'],
                    'Author' => $c['Author']
                ];
            }
        }
        foreach ($tickets as &$t) {
            $t['Comments'] = $commentsByTicket[$t['CaseId']] ?? [];
        }
        unset($t);
    }

    return $tickets;
}

/**
 * Auto-register new tickets into shift_ticket_assignments for the active cycle.
 * Called on every GET so the table stays up to date without a separate bot.
 */
function register_untracked_tickets($mysqli, $tickets, $cycle_id, $group_id, $sub_shift) {
    if (!$cycle_id) return; // can't register without a cycle
    $stmt = $mysqli->prepare(
        "INSERT IGNORE INTO shift_ticket_assignments (case_id, case_number, cycle_id, group_id, sub_shift)
         VALUES (?, ?, ?, ?, ?)"
    );
    if (!$stmt) return;
    foreach ($tickets as $t) {
        // Only auto-register tickets created during this cycle
        if (!$t['is_inherited']) {
            $stmt->bind_param('sssis', $t['CaseId'], $t['CaseNumber'], $cycle_id, $group_id, $sub_shift);
            $stmt->execute();
        }
    }
    $stmt->close();
}

/**
 * Check if a weekly rollover is needed and apply it automatically.
 * Now also creates a new shift_cycle record and closes the old one.
 */
function check_and_perform_rollover($mysqli) {
    $config_res = $mysqli->query("SELECT * FROM shift_config LIMIT 1");
    if (!$config_res || $config_res->num_rows === 0) return;
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
                $cycle_end_str = $cycle_end->format('Y-m-d H:i:s');
                $next_start_str = $cycle_end_str;

                // Close current shift_cycle
                $mysqli->query("UPDATE shift_cycles SET end_datetime = '$cycle_end_str' WHERE end_datetime IS NULL");

                // Note: We NO LONGER swap roles in the database!
                // The shift rotation is mathematically calculated in get_group_members()
                // using the elapsed weeks from 2026-05-27.

                // Create new shift_cycle
                $stmt = $mysqli->prepare("INSERT INTO shift_cycles (active_group_id, start_datetime) VALUES (?, ?)");
                $stmt->bind_param('is', $next_shift, $next_start_str);
                $stmt->execute();
                $stmt->close();

                // Update shift_config (keeps backward compat)
                $stmt2 = $mysqli->prepare("UPDATE shift_config SET active_shift = ?, start_date = ?");
                $stmt2->bind_param('is', $next_shift, $next_start_date);
                $stmt2->execute();
                $stmt2->close();

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

// Run auto-rollover check on every request
check_and_perform_rollover($mysqli);

// ─────────────────────────────────────────────────────────────
// GET — Read shift data
// ─────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $cycle = get_active_cycle($mysqli);
    if (!$cycle) {
        http_response_code(500);
        echo json_encode(['error' => 'No shift cycle found']);
        exit();
    }

    $active_group_id   = intval($cycle['active_group_id']);
    $inactive_group_id = $active_group_id === 1 ? 2 : 1;
    $cycle_start       = $cycle['start_datetime'];
    $start_hour        = $cycle['start_hour'] ?? '08:00:00';
    $end_hour          = $cycle['end_hour']   ?? '20:00:00';

    // Days elapsed in this cycle
    $startDate = new DateTime($cycle_start);
    $today = new DateTime();
    $startDate->setTime(0, 0, 0);
    $today->setTime(0, 0, 0);
    $days_elapsed = $startDate->diff($today)->days + 1;

    // Current sub-shift
    $current_sub_shift = get_current_sub_shift($start_hour, $end_hour);

    // Members
    $members_shift1 = get_group_members($mysqli, 1);
    $members_shift2 = get_group_members($mysqli, 2);

    // Tickets for the active group (created in this cycle + older ones still open)
    $active_tickets = get_tickets_for_group($mysqli, $active_group_id, $cycle_start, true);

    // Tickets for the inactive group (only open — these are "traspasados" pending)
    $inactive_tickets = get_tickets_for_group($mysqli, $inactive_group_id, null, false);

    // Auto-register new tickets in shift_ticket_assignments
    register_untracked_tickets($mysqli, $active_tickets, $cycle['id'], $active_group_id, $current_sub_shift);

    echo json_encode([
        'config' => [
            'active_shift'    => $active_group_id,
            'cycle_id'        => $cycle['id'],
            'shift1_alias'    => $cycle['shift1_alias'],
            'shift2_alias'    => $cycle['shift2_alias'],
            'start_date'      => (new DateTime($cycle_start))->format('Y-m-d'),
            'start_hour'      => $start_hour,
            'end_hour'        => $end_hour,
            'days_elapsed'    => $days_elapsed,
            'current_sub_shift' => $current_sub_shift,
        ],
        'shift1_members'   => $members_shift1,
        'shift2_members'   => $members_shift2,
        'active_tickets'   => $active_tickets,
        'inactive_tickets' => $inactive_tickets,
    ]);

// ─────────────────────────────────────────────────────────────
// POST — Mutations (traspaso, update_members, update_config)
// ─────────────────────────────────────────────────────────────
} elseif ($method === 'POST') {
    $data   = json_decode(file_get_contents("php://input"));
    $action = $_GET['action'] ?? 'update_config';

    // ── UPDATE MEMBERS ──────────────────────────────────────────
    if ($action === 'update_members') {
        if (!isset($data->shift1_members) || !is_array($data->shift1_members)
            || !isset($data->shift2_members) || !is_array($data->shift2_members)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Faltan listas de integrantes.']);
            exit();
        }

        $mysqli->begin_transaction();
        try {
            // Collect all new member IDs to know who was removed
            $new_member_ids = [];
            foreach ($data->shift1_members as $m) $new_member_ids[] = intval($m->id);
            foreach ($data->shift2_members as $m) $new_member_ids[] = intval($m->id);

            // Get current members before clearing, to identify removed users
            $current_members_res = $mysqli->query("SELECT user_id FROM shift_group_members");
            $current_ids = [];
            if ($current_members_res) {
                while ($row = $current_members_res->fetch_assoc()) {
                    $current_ids[] = intval($row['user_id']);
                }
            }

            // Identify users that were removed (in current but not in new list)
            $removed_ids = array_diff($current_ids, $new_member_ids);

            // Reset turno_7x7 for removed users
            if (!empty($removed_ids)) {
                $removed_ids_str = implode(',', $removed_ids);
                $mysqli->query("UPDATE users SET turno_7x7 = NULL, turno_tipo = 'Día' WHERE id IN ($removed_ids_str)");
            }

            // Clear all memberships
            $mysqli->query("DELETE FROM shift_group_members");

            // Re-insert from both lists
            foreach ([1 => $data->shift1_members, 2 => $data->shift2_members] as $gid => $members) {
                foreach ($members as $m) {
                    $uid  = intval($m->id);
                    $tipo = $mysqli->real_escape_string($m->tipo ?? 'Día');
                    $stmt = $mysqli->prepare(
                        "INSERT INTO shift_group_members (user_id, group_id, sub_shift) VALUES (?, ?, ?)"
                    );
                    $stmt->bind_param('iis', $uid, $gid, $tipo);
                    $stmt->execute();
                    $stmt->close();

                    // Keep users table in sync for legacy code
                    $mysqli->query("UPDATE users SET turno_7x7 = $gid, turno_tipo = '$tipo' WHERE id = $uid");
                }
            }

            $mysqli->commit();
            echo json_encode(['status' => 'success', 'message' => 'Integrantes de turnos actualizados correctamente.']);
        } catch (Exception $e) {
            $mysqli->rollback();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar integrantes: ' . $e->getMessage()]);
        }
        exit();

    // ── UPDATE CONFIG (aliases, hours) ──────────────────────────
    } else {
        if (!isset($data->shift1_alias) || !isset($data->shift2_alias)
            || !isset($data->active_shift) || !isset($data->start_date)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Incomplete configuration data.']);
            exit();
        }

        $active_shift = intval($data->active_shift);
        $s1_alias     = $mysqli->real_escape_string($data->shift1_alias);
        $s2_alias     = $mysqli->real_escape_string($data->shift2_alias);
        $start_date   = $mysqli->real_escape_string($data->start_date);
        $start_hour   = $mysqli->real_escape_string($data->start_hour ?? '08:00:00');
        $end_hour     = $mysqli->real_escape_string($data->end_hour   ?? '20:00:00');

        // Update shift_config
        $check = $mysqli->query("SELECT id FROM shift_config LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $stmt = $mysqli->prepare("UPDATE shift_config SET active_shift=?, shift1_alias=?, shift2_alias=?, start_date=?, start_hour=?, end_hour=?");
            $stmt->bind_param('isssss', $active_shift, $s1_alias, $s2_alias, $start_date, $start_hour, $end_hour);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO shift_config (active_shift, shift1_alias, shift2_alias, start_date, start_hour, end_hour) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('isssss', $active_shift, $s1_alias, $s2_alias, $start_date, $start_hour, $end_hour);
        }

        // Also update shift_groups aliases
        $mysqli->query("UPDATE shift_groups SET alias = '$s1_alias' WHERE id = 1");
        $mysqli->query("UPDATE shift_groups SET alias = '$s2_alias' WHERE id = 2");

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Configuración guardada exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la configuración.']);
        }
    }
}
?>
