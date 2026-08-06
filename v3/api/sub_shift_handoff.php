<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('America/Santiago');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helper.php';

$database = new DB();
$mysqli   = $database->getConnection();
$auth     = require_auth($mysqli);
$me       = $auth['decoded']->data;   // ->id, ->username

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ─────────────────────────────────────────────────────────────
// GET ?action=pending  — check if there is a pending session for the current user's group
// GET ?action=status   — list recent sessions (last 30 days) for the active group
// ─────────────────────────────────────────────────────────────
if ($method === 'GET') {

    // Resolve current user's group and sub-shift from shift_group_members
    $stmt = $mysqli->prepare(
        "SELECT sgm.group_id, sgm.sub_shift, sc.id as cycle_id, sc.active_group_id,
                cfg.start_hour, cfg.end_hour
         FROM shift_group_members sgm
         JOIN shift_cycles sc ON sc.end_datetime IS NULL
         CROSS JOIN shift_config cfg
         WHERE sgm.user_id = ?
         LIMIT 1"
    );
    $stmt->bind_param('i', $me->id);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();

    if (!$member) {
        echo json_encode(['pending_session' => null, 'is_receiver' => false]);
        exit();
    }

    $my_group    = intval($member['group_id']);
    
    // Mathematical role inversion
    $ref_date = strtotime('2026-05-27 08:00:00');
    $now_time = time();
    $elapsed_weeks = max(0, floor(($now_time - $ref_date) / (7 * 24 * 3600)));
    $cycle_index = ($my_group == 1) ? floor(($elapsed_weeks + 1) / 2) : floor($elapsed_weeks / 2);
    $invert_roles = ($cycle_index % 2 != 0);

    $base_sub = $member['sub_shift'];
    if ($invert_roles) {
        $my_sub = ($base_sub === 'Día') ? 'Noche' : 'Día';
    } else {
        $my_sub = $base_sub;
    }
    
    $cycle_id    = intval($member['cycle_id']);
    $active_group = intval($member['active_group_id']);

    // Derive current sub-shift by clock
    $now_h    = intval((new DateTime())->format('H'));
    $start_h  = intval(explode(':', $member['start_hour'])[0]);
    $end_h    = intval(explode(':', $member['end_hour'])[0]);
    $curr_sub = ($now_h >= $start_h && $now_h < $end_h) ? 'Día' : 'Noche';
    $next_sub = $curr_sub === 'Día' ? 'Noche' : 'Día';

    // Look for a pending session where this user is the RECEIVER
    // (i.e., session's to_sub_shift == my sub-shift and it's my group)
    $stmt2 = $mysqli->prepare(
        "SELECT s.*, 
                u.first_name, u.last_name,
                (SELECT COUNT(*) FROM sub_shift_handoff_tickets t WHERE t.session_id = s.id) as ticket_count
         FROM sub_shift_handoff_sessions s
         JOIN users u ON u.id = s.from_user_id
         WHERE s.cycle_id = ? AND s.group_id = ? AND s.status = 'pending'
           AND s.to_sub_shift = ?
         ORDER BY s.created_at DESC
         LIMIT 1"
    );
    $stmt2->bind_param('iis', $cycle_id, $my_group, $my_sub);
    $stmt2->execute();
    $pending = $stmt2->get_result()->fetch_assoc();

    // If pending, also fetch the tickets list
    $pending_tickets = [];
    if ($pending) {
        $stmt3 = $mysqli->prepare(
            "SELECT * FROM sub_shift_handoff_tickets WHERE session_id = ?"
        );
        $stmt3->bind_param('i', $pending['id']);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        while ($r = $res3->fetch_assoc()) { $pending_tickets[] = $r; }
    }

    echo json_encode([
        'my_group'        => $my_group,
        'my_sub_shift'    => $my_sub,
        'current_sub'     => $curr_sub,   // clock-based
        'next_sub'        => $next_sub,
        'cycle_id'        => $cycle_id,
        'active_group'    => $active_group,
        'is_active_group' => $my_group === $active_group,
        'pending_session' => $pending,
        'pending_tickets' => $pending_tickets,
        'is_receiver'     => !empty($pending),  // true if I should see "Aceptar Traspaso"
    ]);
    exit();
}

// ─────────────────────────────────────────────────────────────
// POST ?action=create  — engineer initiates a sub-shift handoff
// ─────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents("php://input"), true);

    $notes      = $data['notes']    ?? '';
    $tickets    = $data['tickets']  ?? [];   // array of {case_id, case_number, status}
    $from_sub   = $data['from_sub'] ?? 'Día';
    $to_sub     = $data['to_sub']   ?? 'Noche';
    $cycle_id   = intval($data['cycle_id'] ?? 0);
    $group_id   = intval($data['group_id'] ?? 0);

    if (!$cycle_id || !$group_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan parámetros requeridos (cycle_id, group_id)']);
        exit();
    }

    $mysqli->begin_transaction();
    try {
        // Insert session
        $stmt = $mysqli->prepare(
            "INSERT INTO sub_shift_handoff_sessions
             (cycle_id, group_id, from_sub_shift, to_sub_shift, from_user_id, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        );
        $uid = intval($me->id);
        $stmt->bind_param('iissis', $cycle_id, $group_id, $from_sub, $to_sub, $uid, $notes);
        $stmt->execute();
        $session_id = $mysqli->insert_id;
        $stmt->close();

        // Insert tickets
        if (!empty($tickets)) {
            $stmt2 = $mysqli->prepare(
                "INSERT INTO sub_shift_handoff_tickets (session_id, case_id, case_number, status_at_handoff)
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($tickets as $t) {
                $cid    = $t['case_id']     ?? '';
                $cnum   = $t['case_number'] ?? '';
                $cstat  = $t['status']      ?? '';
                $stmt2->bind_param('isss', $session_id, $cid, $cnum, $cstat);
                $stmt2->execute();
            }
            $stmt2->close();
        }

        $mysqli->commit();
        echo json_encode(['status' => 'ok', 'session_id' => $session_id]);
    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ─────────────────────────────────────────────────────────────
// POST ?action=accept  — receiving engineer accepts the handoff
// ─────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'accept') {
    $data       = json_decode(file_get_contents("php://input"), true);
    $session_id = intval($data['session_id'] ?? 0);

    if (!$session_id) {
        http_response_code(400);
        echo json_encode(['error' => 'session_id requerido']);
        exit();
    }

    $now = (new DateTime())->format('Y-m-d H:i:s');
    $uid = intval($me->id);

    $stmt = $mysqli->prepare(
        "UPDATE sub_shift_handoff_sessions
         SET status = 'accepted', to_user_id = ?, accepted_at = ?
         WHERE id = ? AND status = 'pending'"
    );
    $stmt->bind_param('isi', $uid, $now, $session_id);
    $stmt->execute();

    if ($mysqli->affected_rows > 0) {
        echo json_encode(['status' => 'accepted']);
    } else {
        http_response_code(409);
        echo json_encode(['error' => 'Sesión no encontrada o ya fue aceptada.']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method/action not allowed']);
?>
