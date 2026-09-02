<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$context = trim($input['context'] ?? 'general_tickets');

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'El mensaje no puede estar vacío.']);
    exit();
}

// Function to read variables from .env manually since dotenv library isn't loaded
function get_env_variable($key) {
    $env_file = __DIR__ . '/.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                list($name, $value) = $parts;
                if (trim($name) === $key) {
                    // Strip quotes if present
                    return trim($value, " \t\n\r\0\x0B\"'");
                }
            }
        }
    }
    return getenv($key);
}

$gemini_key = get_env_variable('GEMINI_API_KEY');

// --- DEMO MODE FALLBACK ---
if (empty($gemini_key)) {
    $msg_lower = mb_strtolower($message, 'UTF-8');
    $demo_response = '';
    
    if (strpos($msg_lower, 'allison') !== false || strpos($msg_lower, 'poblete') !== false) {
        $demo_response = "Hola. He buscado en la base de datos de Salesforce. Actualmente **Allison Poblete** tiene asignados **11 tickets** en estado de `Seeking Customer Clarification`. Los casos más recientes son:\n\n" .
                         "*   Ticket **00647804** (AMCEN-Solicitud de licencia para un pc nuevo) de Centinela.\n" .
                         "*   Ticket **00647751** (AMCEN-Solicitud del arbol de toolbox) de Centinela.\n\n" .
                         "Ambos se encuentran en espera de respuesta del cliente. Puedes pulsar en los números de ticket para abrirlos.";
    } elseif (strpos($msg_lower, 'victor') !== false || strpos($msg_lower, 'vrojas') !== false || strpos($msg_lower, 'rojas') !== false) {
        $demo_response = "Hola. He revisado los registros de Salesforce. **Víctor Rojas** tiene asignados **2 tickets** actualmente. El caso destacado es:\n\n" .
                         "*   Ticket **00647772** (CNRT - Reporte Tabla Indicadores Transporte) de la faena RT en estado `Assigned`.\n\n" .
                         "Haga clic en el ticket para desplegar su ficha de detalles.";
    } elseif (strpos($msg_lower, 'pd') !== false || strpos($msg_lower, 'escalad') !== false || strpos($msg_lower, 'armando') !== false || strpos($msg_lower, 'mestanza') !== false) {
        $demo_response = "Hola. Encontré **5 casos** en estado `Escalate PD` en la base de datos local. Los principales casos son:\n\n" .
                         "*   Ticket **00647658** (AMANT - GNSS Positioning stops working with PPP) asignado a **Armando Mestanza** (creado ayer).\n" .
                         "*   Ticket **00629293** (AMCN - Activity change after 1 second) asignado a **Maikol Salas** (creado en abril).\n" .
                         "*   Ticket **00641891** (AMANT - GNSS positioning stops working with PPP) asignado a **Armando Mestanza**.\n\n" .
                         "Todos ellos se encuentran actualmente bajo investigación del equipo de desarrollo de producto (PD).";
    } elseif (strpos($msg_lower, 'maik') !== false || strpos($msg_lower, 'maikol') !== false || strpos($msg_lower, 'salas') !== false) {
        $demo_response = "Hola Maikol. Consultando la base de datos de Salesforce, tienes **2 tickets abiertos** registrados a tu nombre:\n\n" .
                         "1.  Ticket **00645334** (Working) - *RT - eventos no se registran en la change_logs* (creado el 28 de julio).\n" .
                         "2.  Ticket **00629293** (Escalate PD) - *AMCN - Activity change after 1 second* (creado el 15 de abril).\n\n" .
                         "También tienes casos resueltos este año, como el ticket **00645330** (Closed). Puedes seleccionar cualquiera de ellos haciendo clic en su número.";
    } else {
        $demo_response = "Hola, soy **Gemini AI**. Actualmente estoy operando en **Modo Demostración** porque no se ha configurado una API Key válida en el archivo `api/.env`.\n\n" .
                         "Puedo simular búsquedas sobre la base de datos local de Salesforce. Intenta preguntarme por los tickets de **Maikol**, los casos de **Allison**, los tickets de **Víctor**, o los tickets **escalados a PD** para ver cómo enlazo los casos dinámicamente.";
    }

    echo json_encode([
        'response' => $demo_response,
        'sql' => '-- MOCK_QUERY (Modo Demostración sin API Key)'
    ]);
    exit();
}

// --- ACTUAL GEMINI TEXT-TO-SQL FLOW ---

function call_gemini_api($api_key, $prompt, $max_retries = 3) {
    // Primary model, with fallback if unavailable
    $models = ['gemini-flash-latest', 'gemini-flash-lite-latest'];
    
    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];

    $last_error = '';

    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($api_key);

        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200) {
                $json = json_decode($response, true);
                return $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
            }

            // On 503 (overloaded), wait and retry
            if ($http_code === 503 && $attempt < $max_retries) {
                sleep(2);
                continue;
            }

            // Any other error or last attempt — try next model
            $last_error = "HTTP {$http_code} (modelo {$model}): " . $response;
            break;
        }
    }

    throw new Exception("Error al consultar API de Gemini tras varios intentos. Último error: " . $last_error);
}

try {
    // 1. Text-to-SQL Prompt
    $schema_prompt = "Eres un asistente traductor de lenguaje natural a consultas SQL MariaDB/MySQL. 
Debes generar exclusivamente una consulta SQL de tipo SELECT para responder la pregunta del usuario sobre la base de datos de tickets de Salesforce.

Esquema de las tablas disponibles en la base de datos:
Tabla: rmmsalesforce.sf_cases
Columnas:
- Id (varchar(18)) - Identificador de Salesforce
- CaseNumber (varchar(50)) - Número de caso visible (ej. 00647804)
- Subject (text) - Asunto del ticket
- Status (varchar(100)) - Estados posibles: 'Closed', 'Working', 'Assigned', 'Seeking Customer Clarification', 'Escalate PD', 'Escalate GT'
- Priority (varchar(100))
- Description (text)
- Resolution (text)
- CreatedDate (datetime)
- ClosedDate (datetime)
- OwnerId (varchar(18)) - Llave foránea para el usuario
- AccountId (varchar(18)) - Llave foránea para la cuenta

Tabla: rmmsalesforce.sf_users (unir con sf_cases.OwnerId = sf_users.Id)
Columnas:
- Id (varchar(18))
- Name (varchar(121)) - Nombre completo del responsable del ticket (ej. Allison Poblete, Víctor Rojas, Maikol Salas)

Tabla: rmmsalesforce.sf_accounts (unir con sf_cases.AccountId = sf_accounts.Id)
Columnas:
- Id (varchar(18))
- internal_faena_alias (varchar(255)) - Alias de la faena de minería (ej. Centinela, Antucoya, RT)

Reglas estrictas de generación:
1. Devuelve ÚNICAMENTE la consulta SQL formateada en texto plano. No agregues bloques de código markdown como ```sql o ```.
2. Tu respuesta debe comenzar con SELECT. Cualquier otro comando será bloqueado.
3. Asegúrate de añadir un LIMIT 15 al final para optimizar el rendimiento.
4. Si la consulta involucra nombres de personas o faenas, realiza un JOIN e integra filtros con LIKE (ej. u.Name LIKE '%Allison%').
5. Para tickets abiertos o activos, filtra con LOWER(c.Status) != 'closed'.
6. Si la pregunta del usuario no tiene nada que ver con tickets o base de datos, simplemente genera la consulta 'SELECT 1;'.

Pregunta del usuario: \"{$message}\"
Consulta SQL:";

    $generated_sql = trim(call_gemini_api($gemini_key, $schema_prompt));
    
    // Clean code block ticks if Gemini returns them anyway
    if (strpos($generated_sql, '```') !== false) {
        $generated_sql = preg_replace('/```(?:sql)?|```/', '', $generated_sql);
        $generated_sql = trim($generated_sql);
    }

    // 2. Strict Security Check
    $sql_lower = strtolower($generated_sql);
    
    // Must start with SELECT (ignoring whitespace/comments)
    $clean_sql_lower = ltrim($sql_lower, " \t\n\r\0\x0B(");
    if (strpos($clean_sql_lower, 'select') !== 0) {
        throw new Exception("Consulta no autorizada. El sistema solo permite lecturas. SQL generado: " . $generated_sql);
    }
    
    // Forbidden commands
    $forbidden = ['insert', 'update', 'delete', 'drop', 'alter', 'create', 'truncate', 'into', 'replace', 'grant', 'revoke'];
    foreach ($forbidden as $word) {
        // Regex word boundary check to avoid catching words like "description" or "interseccion"
        if (preg_match('/\b' . $word . '\b/', $sql_lower)) {
            throw new Exception("Operación prohibida detectada en SQL: '" . $word . "'. SQL generado: " . $generated_sql);
        }
    }

    // 3. Execute SQL Query safely
    $db_results = [];
    $res = $mysqli->query($generated_sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $db_results[] = $row;
        }
    } else {
        throw new Exception("Error al ejecutar la consulta SQL generada: " . $mysqli->error);
    }

    // 4. Conversational Response Prompt
    $results_json = json_encode($db_results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    $summary_prompt = "Eres un asistente de inteligencia artificial del laboratorio de monitoreo técnico (Gemini AI).
Tu labor es responder de forma amable y clara, en español, a la consulta del usuario basándote únicamente en los datos reales obtenidos de la base de datos de tickets que se muestran a continuación.

Normas de Redacción:
1. Explica los resultados de manera resumida y en un lenguaje profesional pero cercano.
2. Es OBLIGATORIO que incluyas siempre los números de caso como **00647804** en negrita (respetando exactamente el formato de 8 dígitos) cuando hables de un ticket. Esto permitirá que la interfaz los detecte como enlaces para abrirlos.
3. Si la base de datos no arrojó resultados, explícalo de manera amable indicando que no se encontraron tickets que cumplan con esa descripción en el sistema de Salesforce.
4. Si hay errores o problemas, menciónalos de manera constructiva.

Pregunta del usuario: \"{$message}\"

Resultados obtenidos en formato JSON:
{$results_json}

Respuesta conversacional:";

    $conversational_response = call_gemini_api($gemini_key, $summary_prompt);

    echo json_encode([
        'response' => $conversational_response,
        'sql' => $generated_sql
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error de procesamiento de IA',
        'details' => $e->getMessage()
    ]);
}
?>
