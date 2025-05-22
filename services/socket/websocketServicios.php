<?php

use Swoole\WebSocket\Server;
use Swoole\Timer;

// Configuración del servidor
$host = "0.0.0.0";
$port = 9506;

// Creamos el servidor WebSocket de OpenSwoole.
$server = new Server($host, $port);

// Evento: Cuando se inicia el servidor.
$server->on('start', function (Server $server) use ($host, $port) {
    echo "Servidor WebSocket OpenSwoole iniciado en {$host}:{$port}\n";
});

// Evento: Al establecer una conexión WebSocket.
$server->on('open', function (Server $server, $request) {
    echo "Conexión abierta: FD#{$request->fd}\n";
});

// Evento: Cuando se recibe un mensaje.
$server->on('message', function (Server $server, $frame) {
    echo "Mensaje recibido de FD#{$frame->fd}: {$frame->data}\n";
    // Aquí podrías procesar mensajes entrantes si fuera necesario.
});

// Evento: Al cerrar la conexión.
$server->on('close', function (Server $server, $fd) {
    echo "Conexión cerrada: FD#{$fd}\n";
});

// Timer: Cada 2 segundos revisa los archivos de log y las alertas, y transmite el estado a todos los clientes.
Timer::tick(2000, function () use ($server) {
    $data = check_services_status();
    $json = json_encode($data);

    // Enviamos el mensaje a todos los clientes WebSocket conectados.
    foreach ($server->connections as $fd) {
        if ($server->isEstablished($fd)) {
            $server->push($fd, $json);
        }
    }
});


/**
 * Función para revisar el estado de los servicios leyendo el último renglón de cada log.
 */
function check_services_status()
{
    $logsPath = '/home/jigsaw/Logs/';
    $services = [
        'oasgraf' => [
            'filename' => 'oasgraf.log',
            'pattern'  => '/\[(.*?)\]\s+TunelOAS\s+Activo/'
        ],
        'websocket' => [
            'filename' => 'websocket.log',
            'pattern'  => '/\[(.*?)\]\s+WebSocket\s+Activo/'
        ],
        'salesforce' => [
            'filename' => 'salesforce.log',
            'pattern'  => '/\[(.*?)\]\s+Salesforce.*ejecutado exitosamente/'
        ],
    ];

    $result = [];
    foreach ($services as $key => $service) {
        $file = $logsPath . $service['filename'];

        // Si el archivo no existe, agrega un resultado con información por defecto
        if (!file_exists($file)) {
            $result[$key] = [
                'minutosUltimoActivo' => null,
                'fechaUltimoActivo' => null,
                'tailLog' => '',
                'active' => false
            ];
            continue;
        }

        // Obtener la última línea relevante del log usando grep y tail
        $line = trim(shell_exec(
            ($key === 'salesforce')
                ? "grep -a -i  exitosamente " . escapeshellarg($file) . " | tail -n1"
                : "grep -a -i activo " . escapeshellarg($file) . " | tail -n1"
        ));

        $tailLog = trim(shell_exec("tail -n 30 " . escapeshellarg($file)));

        // Extraer la fecha con expresión regular
        preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches);
        $fechaUltimoActivo = $matches[1] ?? null;

        // Si la fecha es nula o no está en el formato esperado, asignar un valor por defecto
        if ($fechaUltimoActivo) {
            $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaUltimoActivo);
            if (!$fecha) {
                $fechaUltimoActivo = null;  // Si la fecha no es válida, la asignamos a null
            }
        }

        // Si la fecha no está presente o es inválida
        if (!$fechaUltimoActivo) {
            $minutosPasados = null;
            $active = false;
        } else {
            // Calcular los minutos pasados desde la última actividad
            $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaUltimoActivo);
            $minutosPasados = (new DateTime())->diff($fecha)->days * 1440 +
                              (new DateTime())->diff($fecha)->h * 60 +
                              (new DateTime())->diff($fecha)->i;
            $active = ($minutosPasados <= 2);  // Activo si han pasado menos de 2 minutos
        }

        // Guardar los resultados para cada servicio
        $result[$key] = [
            'minutosUltimoActivo' => $minutosPasados,
            'fechaUltimoActivo' => $fechaUltimoActivo,
            'tailLog' => $tailLog,
            'active' => $active,
            'debug' => $line
        ];
    }

    // Agregar alertas activas al resultado
    $result['alertas'] = check_alertas();

    return $result;
}



/**
 * Función para obtener alertas activas (no solucionadas) desde la base de datos.
 */
function check_alertas()
{
    // Parámetros de conexión a la base de datos.
    $host = "localhost";      // Cambiar según tu configuración
    $user = "jigsaw";       // Cambiar por tu usuario
    $password = "Jigsaw1"; // Cambiar por tu contraseña
    $database = "checksupport"; // Cambiar por el nombre de tu BD

    $mysqli = new mysqli($host, $user, $password, $database);
    if ($mysqli->connect_error) {
        return [];
    }

    // Consulta para obtener alertas activas (estado=1) y el alias de la faena asociada
    $sql = "SELECT a.id as alerta_id, a.id_faena, f.alias as alias, a.alerta, asis.alerta_sistema, 
                   asis.codigo_alerta, asis.gravedad, a.created_at, a.updated_at
            FROM alertas a
            JOIN alertas_sistema asis ON a.id_alerta_sistema = asis.id
            JOIN faenas f ON a.id_faena = f.id
            WHERE a.estado = 1
            ORDER BY a.updated_at DESC";
    $result = $mysqli->query($sql);

    $alertas = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $alertas[] = $row;
        }
    }

    $mysqli->close();
    return $alertas;
}

// Iniciamos el servidor.
$server->start();
