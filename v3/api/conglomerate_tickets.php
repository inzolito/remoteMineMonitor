<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('America/Santiago');

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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$conglomerate = $_GET['conglomerate'] ?? '';
if (empty($conglomerate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Conglomerate parameter is required']);
    exit();
}

// Compute the 24-hour cycle dates in Chile local time (America/Santiago)
$local_tz = new DateTimeZone('America/Santiago');
$utc_tz = new DateTimeZone('UTC');

$now = new DateTime('now', $local_tz);
$hour = (int)$now->format('H');

if ($hour >= 8) {
    // Current hour is 08:00 or later today. Target is yesterday 08:00:00 to today 08:00:00.
    $yesterday = new DateTime('yesterday', $local_tz);
    $start_date = $yesterday->format('Y-m-d') . ' 08:00:00';
    $end_date = $now->format('Y-m-d') . ' 08:00:00';
} else {
    // Current hour is before 08:00 today. Target is day-before-yesterday 08:00:00 to yesterday 08:00:00.
    $two_days_ago = new DateTime('-2 days', $local_tz);
    $yesterday = new DateTime('yesterday', $local_tz);
    $start_date = $two_days_ago->format('Y-m-d') . ' 08:00:00';
    $end_date = $yesterday->format('Y-m-d') . ' 08:00:00';
}

// Convert local times to UTC for database querying
$start_dt = new DateTime($start_date, $local_tz);
$start_dt->setTimezone($utc_tz);
$start_date_utc = $start_dt->format('Y-m-d H:i:s');

$end_dt = new DateTime($end_date, $local_tz);
$end_dt->setTimezone($utc_tz);
$end_date_utc = $end_dt->format('Y-m-d H:i:s');

// Conglomerate WHERE clause filtering mapping
$conglom_clause = '';
if ($conglomerate === 'CODELCO') {
    $conglom_clause = "AND s.conglomerate = 'CODELCO'";
} elseif ($conglomerate === 'AMSA') {
    $conglom_clause = "AND s.conglomerate = 'AMSA'";
} elseif ($conglomerate === 'Capstone Copper') {
    $conglom_clause = "AND s.conglomerate = 'Capstone Copper'";
} elseif ($conglomerate === 'Otros') {
    $conglom_clause = "AND (s.conglomerate IS NULL OR s.conglomerate NOT IN ('CODELCO', 'AMSA', 'Capstone Copper'))";
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid conglomerate']);
    exit();
}

// Fetch all 7x7 shift members' salesforce_user_id (groups 1 and 2)
$sf_ids = [];
$members_q = "SELECT DISTINCT u2.salesforce_user_id 
              FROM monitoring_system.shift_group_members sgm
              JOIN monitoring_system.users u2 ON u2.id = sgm.user_id
              WHERE sgm.group_id IN (1, 2)
                AND u2.salesforce_user_id IS NOT NULL 
                AND u2.salesforce_user_id != ''";
$sf_res = $mysqli->query($members_q);
if ($sf_res) {
    while ($sf_row = $sf_res->fetch_assoc()) {
        $sf_ids[] = "'" . $mysqli->real_escape_string($sf_row['salesforce_user_id']) . "'";
    }
}
$in_clause = count($sf_ids) > 0 ? implode(',', $sf_ids) : "'NO_MATCH'";

// Query tickets in the range belonging to this conglomerate
$query = "SELECT c.Id, c.CaseNumber, c.Subject, c.Status, c.Priority, c.CreatedDate, c.ClosedDate,
                 c.Description, c.Resolution, c.OwnerId,
                 s.name as Faena, s.conglomerate as Conglomerate,
                 u.Name as OwnerName
          FROM rmmsalesforce.sf_cases c
          LEFT JOIN rmmsalesforce.sf_accounts a ON c.AccountId = a.Id
          LEFT JOIN monitoring_system.sites s ON a.internal_faena_alias = s.alias COLLATE utf8mb4_unicode_ci
          LEFT JOIN rmmsalesforce.sf_users u ON c.OwnerId = u.Id
          WHERE c.CreatedDate >= ? AND c.CreatedDate <= ?
            AND c.IsDeleted = 0
            AND c.OwnerId IN ($in_clause)
            $conglom_clause
          ORDER BY c.CreatedDate DESC";

$stmt = $mysqli->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    exit();
}

$stmt->bind_param('ss', $start_date_utc, $end_date_utc);
$stmt->execute();
$res = $stmt->get_result();

$tickets = [];
while ($row = $res->fetch_assoc()) {
    // Convert UTC CreatedDate and ClosedDate to America/Santiago local timezone
    $created = new DateTime($row['CreatedDate'], $utc_tz);
    $created->setTimezone($local_tz);
    $created_local_str = $created->format('Y-m-d H:i:s');

    $closed_local_str = null;
    if ($row['ClosedDate']) {
        $closed = new DateTime($row['ClosedDate'], $utc_tz);
        $closed->setTimezone($local_tz);
        $closed_local_str = $closed->format('Y-m-d H:i:s');
        $ref_end = $closed;
    } else {
        $ref_end = new DateTime('now', $local_tz);
    }
    
    $diff = $created->diff($ref_end);
    
    $parts = [];
    if ($diff->d > 0) $parts[] = $diff->d . 'd';
    if ($diff->h > 0) $parts[] = $diff->h . 'h';
    if ($diff->i > 0) $parts[] = $diff->i . 'm';
    if (empty($parts)) $parts[] = '0m';
    $duration = implode(' ', $parts);

    $tickets[] = [
        'Id' => $row['Id'],
        'CaseNumber' => $row['CaseNumber'],
        'Subject' => $row['Subject'] ?? '(Sin Asunto)',
        'Status' => $row['Status'],
        'Priority' => $row['Priority'],
        'CreatedDate' => $created_local_str,
        'ClosedDate' => $closed_local_str,
        'Description' => $row['Description'] ?? '',
        'Resolution' => $row['Resolution'] ?? '',
        'Faena' => $row['Faena'] ?? 'N/A',
        'Conglomerate' => $row['Conglomerate'] ?? 'Otros',
        'OwnerName' => $row['OwnerName'] ?? 'Sin Asignar',
        'Duration' => $duration,
        'Comments' => []
    ];
}
$stmt->close();

// Fetch comments for all fetched tickets in one query
if (count($tickets) > 0) {
    $caseIds = array_map(function($t) use ($mysqli) { return "'" . $mysqli->real_escape_string($t['Id']) . "'"; }, $tickets);
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
        $t['Comments'] = $commentsByTicket[$t['Id']] ?? [];
        
        // Strip HTML tags from description and comment bodies
        $t['Description'] = strip_tags($t['Description']);
        
        // Determine solution/last comment
        $isClosed = (strtolower($t['Status']) === 'closed');
        if ($isClosed) {
            $t['SolucionOComentario'] = !empty($t['Resolution']) ? strip_tags($t['Resolution']) : 'Sin solución registrada en Salesforce';
        } else {
            if (!empty($t['Comments'])) {
                $last_c = end($t['Comments']);
                $t['SolucionOComentario'] = "[" . $last_c['Author'] . "]: " . strip_tags($last_c['Body']);
            } else {
                $t['SolucionOComentario'] = 'Sin comentarios registrados';
            }
        }
    }
    unset($t);
}

echo json_encode([
    'conglomerate' => $conglomerate,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'tickets' => $tickets
]);
?>
