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

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 30;
$offset = ($page - 1) * $limit;

// The year criteria (for now, simply this calendar year)
$start_of_year = date('Y-01-01 00:00:00');

// UTC conversions for Salesforce dates
$local_tz = new DateTimeZone('America/Santiago');
$utc_tz = new DateTimeZone('UTC');

$start_dt = new DateTime($start_of_year, $local_tz);
$start_dt->setTimezone($utc_tz);
$start_date_utc = $start_dt->format('Y-m-d H:i:s');

// 1. Get Global Stats (Whole Year)
$stats_query = "SELECT c.Status, c.OwnerId 
                FROM rmmsalesforce.sf_cases c
                WHERE c.CreatedDate >= ? 
                  AND c.IsDeleted = 0";

$stmt = $mysqli->prepare($stats_query);
$stmt->bind_param('s', $start_date_utc);
$stmt->execute();
$res = $stmt->get_result();

$stats = [
    'total' => 0,
    'open' => 0, // Used for 'Working'
    'seeking' => 0,
    'escalado_pd' => 0,
    'escalado_gt' => 0,
    'closed' => 0,
    'queue' => 0,
    'assigned' => 0,
    'sa_queue' => 0
];

while ($row = $res->fetch_assoc()) {
    $stats['total']++;
    $st = strtolower($row['Status']);
    if ($st === 'closed') {
        $stats['closed']++;
    } elseif ($st === 'working') {
        $stats['open']++;
    } elseif ($st === 'assigned') {
        $stats['assigned']++;
    } elseif (strpos($st, 'seeking') !== false) {
        $stats['seeking']++;
    } elseif (strpos($st, 'escalado a pd') !== false) {
        $stats['escalado_pd']++;
    } elseif (strpos($st, 'escalado a gt') !== false) {
        $stats['escalado_gt']++;
    }

    if (strtolower($row['OwnerId']) === '00g1i00000249dwuai' && $st !== 'closed') {
        $stats['sa_queue']++;
    }
}
$stmt->close();

// 2. Get Paginated Tickets
$tickets_query = "SELECT SQL_CALC_FOUND_ROWS 
                         c.Id, c.CaseNumber, c.Subject, c.Status, c.Priority, c.CreatedDate, c.ClosedDate,
                         c.Description, c.Resolution, c.OwnerId,
                         s.name as Faena, s.conglomerate as Conglomerate,
                         u.Name as OwnerName
                  FROM rmmsalesforce.sf_cases c
                  LEFT JOIN rmmsalesforce.sf_accounts a ON c.AccountId = a.Id
                  LEFT JOIN monitoring_system.sites s ON a.internal_faena_alias = s.alias COLLATE utf8mb4_unicode_ci
                  LEFT JOIN rmmsalesforce.sf_users u ON c.OwnerId = u.Id
                  WHERE c.CreatedDate >= ? 
                    AND c.IsDeleted = 0
                  ORDER BY c.CreatedDate DESC
                  LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($tickets_query);
$stmt->bind_param('sii', $start_date_utc, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();

// Get total rows for pagination
$total_rows_res = $mysqli->query("SELECT FOUND_ROWS() as total");
$total_rows = $total_rows_res->fetch_assoc()['total'];

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
        'CommentCount' => 0,
        'Comments' => []
    ];
}
$stmt->close();

// Fetch comments
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
        $t['CommentCount'] = count($t['Comments']);
    }
    unset($t);
}

echo json_encode([
    'stats' => $stats,
    'tickets' => $tickets,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total_rows,
        'total_pages' => ceil($total_rows / $limit)
    ]
]);
?>
