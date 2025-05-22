<?php
require_once(__DIR__ . '/../../build/controller/controller-functions.php');
require_once(__DIR__ . '/../../build/controller/controller-faena.php');
require_once(__DIR__ . '/../../build/controller/controller-alerta.php');

date_default_timezone_set('America/Santiago');

$system  = new systemClass();
$faenaCl = new faena();
$system->validarSesion();

 $puertoWebsocket = 9504;  

 
 function obtenerFaenasConMetricas($system)
 {
     $conn = $system->conectaDB();
     if ($conn->connect_error) {
         echo "Error de conexión: " . $conn->connect_error;
         return [];
     }
 
     $queryFaenas = "
         SELECT 
             f.id AS id_faena,
             f.faena AS nombre_faena,
             s.id AS id_servidor,
             s.nombre AS nombre_servidor
         FROM faenas f
         JOIN faenas_servidores fs ON f.id = fs.id_faena
         JOIN servidores s ON fs.id_servidor = s.id
         WHERE s.nombre like '%FMS%';
     ";
 
     if (!$resultFaenas = $conn->query($queryFaenas)) {
         echo "Error en la consulta: " . $conn->error;
         $conn->close();
         return [];
     }
 
     $faenas = $resultFaenas->fetch_all(MYSQLI_ASSOC);
     if (empty($faenas)) {
         $conn->close();
         return [];
     }
 
     $serverIdsList = implode(',', array_unique(array_column($faenas, 'id_servidor')));
 
     $queryMetricas = "
         SELECT ms.id_servidor,m.simbolo, m.metrica, msv.valor
         FROM metricas_servidores_valor msv
         JOIN metricas_servidores ms ON ms.id = msv.id_metrica_servidor
         JOIN metricas m ON ms.id_metrica = m.id
         WHERE ms.id_servidor IN ($serverIdsList);
     ";
 
     if (!$resultMetricas = $conn->query($queryMetricas)) {
         echo "Error en la consulta de métricas: " . $conn->error;
         $conn->close();
         return $faenas; // Retornamos faenas sin métricas
     }
 
     $metricasPorServidor = [];
     while ($row = $resultMetricas->fetch_assoc()) {
         $metricasPorServidor[$row['id_servidor']][$row['simbolo']] = $row['valor'];
     }
 
     foreach ($faenas as &$faena) {
         $faena['metricas'] = $metricasPorServidor[$faena['id_servidor']] ?? [];
        // $faena = transformarMetricas($faena);
     }
     unset($faena);
 
     $conn->close();
     return $faenas;
 }
 



// Crear el servidor WebSocket
$server = new Swoole\WebSocket\Server("0.0.0.0", $puertoWebsocket);

$server->on('start', function ($server) use ($puertoWebsocket) {
    echo "Servidor WebSocket iniciado en 0.0.0.0:$puertoWebsocket\n";
});

$server->on('open', function ($server, $request) {
    echo "Nuevo cliente conectado: FD {$request->fd}\n";
});

// Enviar datos cada 2 segundos (2000 ms)
$server->tick(2000, function () use ($server, $system) {
    $faenas = obtenerFaenasConMetricas($system);
    $faenasJSON = json_encode($faenas);
    foreach ($server->connections as $fd) {
        if ($server->isEstablished($fd)) {
            $server->push($fd, $faenasJSON);
        }
    }
});

$server->on('message', function ($server, $frame) {
    echo "Mensaje recibido de {$frame->fd}: {$frame->data}\n";

    // Ejemplo: Si el cliente manda "reload", recarga los workers (útil en pruebas)
    if (trim($frame->data) === "reload") {
        echo "Recargando workers...\n";
        $server->reload();
    }
});

$server->on('close', function ($server, $fd) {
    echo "Cliente {$fd} desconectado\n";
});

$server->start();
