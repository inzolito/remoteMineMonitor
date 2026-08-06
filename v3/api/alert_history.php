<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
date_default_timezone_set('America/Santiago');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helper.php';

$database = new DB();
$mysqli   = $database->getConnection();
require_auth($mysqli);

// ── Helpers ────────────────────────────────────────────────────────────────

/**
 * Map a metric_key to a human-readable category name and ID.
 */
function categorize(string $key): array {
    $map = [
        'jams'        => ['id' => 'jams',        'label' => 'JAMS'],
        'summarizer'  => ['id' => 'summarizer',   'label' => 'Summarizer'],
        'backup'      => ['id' => 'backup',       'label' => 'Backup'],
        'cpu'         => ['id' => 'cpu',          'label' => 'CPU / Carga'],
        'disk'        => ['id' => 'disk',         'label' => 'Disco'],
        'freshness'   => ['id' => 'freshness',    'label' => 'Conectividad Servidor'],
        'repc'        => ['id' => 'repc',         'label' => 'Equipos Conectados'],
        'db'          => ['id' => 'db',           'label' => 'Base de Datos'],
    ];
    foreach ($map as $token => $cat) {
        if (stripos($key, $token) !== false) return $cat;
    }
    return ['id' => 'other', 'label' => 'Otro'];
}

/**
 * Format seconds into "Xh Ym" or "Ym Zs".
 */
function fmtSeconds(?int $secs): ?string {
    if ($secs === null || $secs < 0) return null;
    if ($secs < 60)  return "{$secs}s";
    if ($secs < 3600) { $m = intdiv($secs, 60); $s = $secs % 60; return "{$m}m {$s}s"; }
    $h = intdiv($secs, 3600); $m = intdiv($secs % 3600, 60);
    return "{$h}h {$m}m";
}

// ── Read filters from GET ──────────────────────────────────────────────────

$page       = max(1, intval($_GET['page']     ?? 1));
$per_page   = min(100, max(10, intval($_GET['per_page'] ?? 50)));
$offset     = ($page - 1) * $per_page;

$cycle_id   = isset($_GET['cycle_id'])   ? intval($_GET['cycle_id'])   : null;
$sub_shift  = isset($_GET['sub_shift'])  ? $_GET['sub_shift']           : null;  // 'Día' | 'Noche'
$user_id    = isset($_GET['user_id'])    ? intval($_GET['user_id'])     : null;
$date_from  = $_GET['date_from']  ?? null;
$date_to    = $_GET['date_to']    ?? null;
$site_id    = isset($_GET['site_id'])    ? intval($_GET['site_id'])     : null;
$category   = $_GET['category']   ?? null;  // e.g. 'jams', 'backup'…
$status_f   = $_GET['status']     ?? null;  // 'solved' | 'acknowledged'

// ── Resolve shift cycle date range if cycle_id given ──────────────────────

$cycle_info = null;
if ($cycle_id) {
    $cr = $mysqli->prepare("SELECT * FROM shift_cycles WHERE id = ? LIMIT 1");
    $cr->bind_param("i", $cycle_id);
    $cr->execute();
    $cycle_info = $cr->get_result()->fetch_assoc();
    if ($cycle_info) {
        // If cycle has no end_datetime it is still ongoing → use NOW()
        if (!$date_from) $date_from = substr($cycle_info['start_datetime'], 0, 10);
        if (!$date_to)   $date_to   = $cycle_info['end_datetime']
            ? substr($cycle_info['end_datetime'], 0, 10)
            : date('Y-m-d');
    }
}

// If user_id filter is active, resolve which group/sub_shift that user belongs to
// and then find cycles where that group was active.
// We then intersect with cycle date range if already set.
$user_active_periods = []; // [['from' => ..., 'to' => ...], ...]
if ($user_id) {
    $ur = $mysqli->prepare("SELECT group_id, sub_shift FROM shift_group_members WHERE user_id = ?");
    $ur->bind_param("i", $user_id);
    $ur->execute();
    $umRow = $ur->get_result()->fetch_assoc();

    if ($umRow) {
        $grpId    = $umRow['group_id'];
        $subShift = $umRow['sub_shift'];

        // Each cycle where this group was active
        $cyRes = $mysqli->query("SELECT start_datetime, end_datetime FROM shift_cycles WHERE active_group_id = {$grpId} ORDER BY start_datetime ASC");
        while ($cy = $cyRes->fetch_assoc()) {
            $from = $cy['start_datetime'];
            $to   = $cy['end_datetime'] ?? date('Y-m-d H:i:s');

            // If sub-shift is Día → 08:00–20:00; Noche → 20:00–08:00 next day
            // We store the full cycle period and filter inside SQL by hour.
            $user_active_periods[] = ['from' => $from, 'to' => $to, 'sub_shift' => $subShift];
        }
    }
}

// ── Build WHERE clause ─────────────────────────────────────────────────────

$conditions = ["1=1"];
$params     = [];
$types      = "";

// Date range
if ($date_from) {
    $conditions[] = "a.created_at >= ?";
    $params[] = $date_from . " 00:00:00";
    $types   .= "s";
}
if ($date_to) {
    $conditions[] = "a.created_at <= ?";
    $params[] = $date_to . " 23:59:59";
    $types   .= "s";
}

// Site filter (join through servers → site_servers)
if ($site_id) {
    $conditions[] = "ss.site_id = ?";
    $params[] = $site_id;
    $types   .= "i";
}

// Category filter (metric_key based)
if ($category) {
    $catTokens = [
        'jams'       => '%jams%',
        'summarizer' => '%summarizer%',
        'backup'     => '%backup%',
        'cpu'        => '%cpu%',
        'disk'       => '%disk%',
        'freshness'  => '%freshness%',
        'repc'       => '%repc%',
        'db'         => '%db.%',
    ];
    if (isset($catTokens[$category])) {
        $conditions[] = "a.metric_key LIKE ?";
        $params[] = $catTokens[$category];
        $types   .= "s";
    }
}

// Status filter
if ($status_f && in_array($status_f, ['active', 'acknowledged', 'solved'])) {
    $conditions[] = "a.status = ?";
    $params[] = $status_f;
    $types   .= "s";
}

// User active periods filter (resolved above)
if ($user_id && !empty($user_active_periods)) {
    $orParts = [];
    foreach ($user_active_periods as $p) {
        $subCond = "a.created_at BETWEEN ? AND ?";
        $params[] = $p['from'];
        $params[] = $p['to'];
        $types   .= "ss";

        // Sub-shift hour filter
        if ($p['sub_shift'] === 'Día') {
            $subCond .= " AND HOUR(a.created_at) BETWEEN 8 AND 19";
        } elseif ($p['sub_shift'] === 'Noche') {
            $subCond .= " AND (HOUR(a.created_at) >= 20 OR HOUR(a.created_at) < 8)";
        }
        $orParts[] = "({$subCond})";
    }
    if ($orParts) $conditions[] = "(" . implode(" OR ", $orParts) . ")";
} elseif ($user_id) {
    // User has no cycles assigned: return empty
    echo json_encode(['alerts' => [], 'meta' => ['total' => 0], 'shift_cycles' => [], 'categories' => []]);
    exit();
}

// Sub-shift only filter (without user_id)
if ($sub_shift && !$user_id) {
    if ($sub_shift === 'Día') {
        $conditions[] = "HOUR(a.created_at) BETWEEN 8 AND 19";
    } elseif ($sub_shift === 'Noche') {
        $conditions[] = "(HOUR(a.created_at) >= 20 OR HOUR(a.created_at) < 8)";
    }
}

$whereSQL = implode(" AND ", $conditions);

// ── Count total ────────────────────────────────────────────────────────────

$countSQL = "
    SELECT COUNT(*) as total
    FROM alerts a
    LEFT JOIN servers s  ON a.server_id = s.id
    LEFT JOIN site_servers ss ON s.id = ss.server_id
    WHERE {$whereSQL}
";
$countStmt = $mysqli->prepare($countSQL);
if ($types && $params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;

// ── Fetch paginated alerts ─────────────────────────────────────────────────

$dataSQL = "
    SELECT
        a.id,
        a.server_id,
        a.metric_key,
        a.title,
        a.description,
        a.status,
        a.created_at,
        a.acknowledged_at,
        a.solved_at,
        a.user_id,
        a.metric_value,
        s.name   AS server_name,
        s.ip     AS server_ip,
        s.server_type,
        si.name  AS site_name,
        si.id    AS site_id,
        COALESCE(NULLIF(CONCAT(u.first_name,' ',u.last_name),' '), u.username) AS acknowledged_by,
        TIMESTAMPDIFF(SECOND, a.created_at, a.acknowledged_at) AS response_time_seconds,
        TIMESTAMPDIFF(SECOND, a.created_at, a.solved_at)       AS resolution_time_seconds
    FROM alerts a
    LEFT JOIN servers s      ON a.server_id = s.id
    LEFT JOIN site_servers ss ON s.id = ss.server_id
    LEFT JOIN sites si        ON ss.site_id = si.id
    LEFT JOIN users u         ON a.user_id  = u.id
    WHERE {$whereSQL}
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
";

$allTypes  = $types . "ii";
$allParams = array_merge($params, [$per_page, $offset]);

$dataStmt = $mysqli->prepare($dataSQL);
$dataStmt->bind_param($allTypes, ...$allParams);
$dataStmt->execute();
$rows = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Enrich each alert
foreach ($rows as &$row) {
    $cat = categorize($row['metric_key']);
    $row['category_id']    = $cat['id'];
    $row['category_label'] = $cat['label'];
    $row['response_time_fmt']    = fmtSeconds($row['response_time_seconds'] !== null ? (int)$row['response_time_seconds'] : null);
    $row['resolution_time_fmt']  = fmtSeconds($row['resolution_time_seconds'] !== null ? (int)$row['resolution_time_seconds'] : null);
}
unset($row);

// ── Summary stats ──────────────────────────────────────────────────────────

$statsSQL = "
    SELECT
        AVG(TIMESTAMPDIFF(SECOND, a.created_at, a.acknowledged_at)) AS avg_response,
        AVG(TIMESTAMPDIFF(SECOND, a.created_at, a.solved_at))       AS avg_resolution,
        a.metric_key,
        COUNT(*) as cnt
    FROM alerts a
    LEFT JOIN servers s   ON a.server_id = s.id
    LEFT JOIN site_servers ss ON s.id = ss.server_id
    WHERE {$whereSQL}
    GROUP BY a.metric_key
    ORDER BY cnt DESC
";
$statsStmt = $mysqli->prepare($statsSQL);
if ($types && $params) {
    $statsStmt->bind_param($types, ...$params);
}
$statsStmt->execute();
$statsRows = $statsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$avgResponse   = null;
$avgResolution = null;
$topMetric     = null;
$topCount      = 0;

foreach ($statsRows as $i => $sr) {
    if ($i === 0) {
        $avgResponse   = $sr['avg_response']   ? (int)$sr['avg_response']   : null;
        $avgResolution = $sr['avg_resolution'] ? (int)$sr['avg_resolution'] : null;
        $topMetric = $sr['metric_key'];
        $topCount  = (int)$sr['cnt'];
    }
}
$topCat = $topMetric ? categorize($topMetric) : null;

// ── Shift cycles list (for filter dropdown) ────────────────────────────────

$cyclesRes = $mysqli->query("
    SELECT sc.id, sc.start_datetime, sc.end_datetime, sg.alias as group_alias, sg.color_hex
    FROM shift_cycles sc
    JOIN shift_groups sg ON sg.id = sc.active_group_id
    ORDER BY sc.start_datetime DESC
");
$shiftCycles = $cyclesRes->fetch_all(MYSQLI_ASSOC);

// ── Shift members list (for filter) ───────────────────────────────────────

$membersRes = $mysqli->query("
    SELECT sgm.user_id, sgm.group_id, sgm.sub_shift,
           CONCAT(u.first_name, ' ', u.last_name) AS full_name,
           sg.alias as group_alias, sg.color_hex
    FROM shift_group_members sgm
    JOIN users u  ON u.id  = sgm.user_id
    JOIN shift_groups sg ON sg.id = sgm.group_id
    ORDER BY sg.id, sgm.sub_shift, u.first_name
");
$shiftMembers = $membersRes->fetch_all(MYSQLI_ASSOC);

// ── Response ───────────────────────────────────────────────────────────────

echo json_encode([
    'alerts' => $rows,
    'meta'   => [
        'total'       => (int)$total,
        'page'        => $page,
        'per_page'    => $per_page,
        'total_pages' => (int)ceil($total / $per_page),
        'avg_response_seconds'   => $avgResponse,
        'avg_response_fmt'       => fmtSeconds($avgResponse),
        'avg_resolution_seconds' => $avgResolution,
        'avg_resolution_fmt'     => fmtSeconds($avgResolution),
        'top_alert_category'     => $topCat ? $topCat['label'] : null,
        'top_alert_count'        => $topCount,
    ],
    'shift_cycles'  => $shiftCycles,
    'shift_members' => $shiftMembers,
]);
