<?php
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

// ---------------------
// CONFIGURACIÓN GENERAL
// ---------------------
$host = '10.169.140.99';  // Escucha en todas las interfaces
$port = 15001;       // Puerto en el que se ejecuta el servidor


 

// Definir los archivos a monitorear
$csvFile = 'http://10.169.140.99/monitoreoLaboratorio/data/fileTemporal.csv';         // Archivo CSV a monitorear
//$csv_file = "../../data/
$logFile = 'logs/app.log';     // Archivo de log a monitorear

// Guardar la última modificación conocida de cada archivo
$csvLastMod = file_exists($csvFile) ? filemtime($csvFile) : 0;
$logLastMod = file_exists($logFile) ? filemtime($logFile) : 0;

// Aquí podrías definir variables o lógica para monitorear cambios en la DB
// Ejemplo (pseudo-código): $dbLastUpdate = obtenerUltimaFechaDeActualizacionDeLaTabla();

// ------------------------
// CREACIÓN DEL SOCKET MAIN
// ------------------------
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$socket) {
    die("Error al crear el socket: " . socket_strerror(socket_last_error()) . "\n");
}
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
if (!socket_bind($socket, $host, $port)) {
    die("Error en el bind: " . socket_strerror(socket_last_error($socket)) . "\n");
}
if (!socket_listen($socket)) {
    die("Error en el listen: " . socket_strerror(socket_last_error($socket)) . "\n");
}
echo "Servidor WebSocket iniciado en $host:$port\n";

// Array para almacenar todos los sockets (el socket principal + clientes)
$clients = array($socket);

// -----------------------
// BUCLE PRINCIPAL DEL SERVIDOR
// -----------------------
while (true) {
    $read = $clients;
    $write = array();
    $except = array();
    // socket_select: espera actividad en alguno de los sockets (0 segundos y 10 microsegundos de microtimeout)
    if (socket_select($read, $write, $except, 0, 10) < 1) {
        // Si no hay actividad en sockets, se revisan los archivos y la DB

        // --- MONITOREAR CSV ---
        if (file_exists($csvFile)) {
            $currentCsvMod = filemtime($csvFile);
            if ($currentCsvMod > $csvLastMod) {
                $csvLastMod = $currentCsvMod;
                $csvContent = file_get_contents($csvFile);
                // Enviamos el contenido con un prefijo "csv:" para identificar el tipo
                sendToClients($clients, $socket, "csv:" . $csvContent);
                echo "Cambio detectado en CSV\n";
            }
        }

        // --- MONITOREAR ARCHIVO DE LOG ---
        if (file_exists($logFile)) {
            $currentLogMod = filemtime($logFile);
            if ($currentLogMod > $logLastMod) {
                $logLastMod = $currentLogMod;
                $logContent = file_get_contents($logFile);
                sendToClients($clients, $socket, "log:" . $logContent);
                echo "Cambio detectado en el log\n";
            }
        }

        // --- MONITOREAR BASE DE DATOS (OPCIONAL) ---
        // Aquí podrías conectar a la base de datos, ejecutar una consulta y detectar si ha habido cambios.
        // Por ejemplo, si la tabla ha sido actualizada, enviar un mensaje:
        // if (dbChanged()) {
        //     sendToClients($clients, $socket, "db:Actualización detectada en la base de datos.");
        //     echo "Cambio detectado en la DB\n";
        // }

        // Continuar el bucle sin procesar más sockets en esta iteración
        continue;
    }

    // Si hay actividad, puede ser:
    // 1. Una nueva conexión entrante.
    // 2. Datos enviados por un cliente.

    // --- ACEPTAR NUEVAS CONEXIONES ---
    if (in_array($socket, $read)) {
        $newClient = socket_accept($socket);
        if ($newClient === false) {
            echo "Error al aceptar la conexión: " . socket_strerror(socket_last_error()) . "\n";
            continue;
        }
        $clients[] = $newClient;
        doHandshake($newClient);  // Realiza el handshake del protocolo WebSocket
        echo "Nuevo cliente conectado\n";
        $key = array_search($socket, $read);
        unset($read[$key]);
    }

    // --- PROCESAR MENSAJES DE CLIENTES (si los hay) ---
    foreach ($read as $client) {
        $bytes = @socket_recv($client, $buffer, 2048, 0);
        if ($bytes === false || $bytes === 0) {
            // El cliente se desconectó
            $key = array_search($client, $clients);
            socket_close($client);
            unset($clients[$key]);
            echo "Cliente desconectado\n";
        } else {
            // Aquí podrías procesar mensajes entrantes si es necesario.
            // En este ejemplo, el servidor solo envía notificaciones.
        }
    }
}

socket_close($socket);

// ---------------------------------
// FUNCIONES DE APOYO
// ---------------------------------

/**
 * Realiza el handshake para establecer la conexión WebSocket.
 */
function doHandshake($client)
{
    $buffer = socket_read($client, 1024);
    if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $matches)) {
        $key = trim($matches[1]);
        $acceptKey = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
        socket_write($client, $upgrade, strlen($upgrade));
    } else {
        socket_close($client);
    }
}

/**
 * Envía un mensaje (ya enmarcado) a todos los clientes conectados.
 * Se omite el socket principal.
 */
function sendToClients($clients, $serverSocket, $message)
{
    $frame = encodeFrame($message);
    foreach ($clients as $client) {
        if ($client === $serverSocket) continue;
        @socket_write($client, $frame, strlen($frame));
    }
}

/**
 * Empaqueta el mensaje en un frame según el protocolo WebSocket (para mensajes de texto).
 */
function encodeFrame($message)
{
    $b1 = 0x81;  // FIN = 1, opcode = 0x1 (texto)
    $length = strlen($message);
    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif ($length > 125 && $length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    } else {
        $header = pack('CCNN', $b1, 127, $length);
    }
    return $header . $message;
}
