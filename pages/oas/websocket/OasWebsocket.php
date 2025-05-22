<?php
require_once __DIR__ . '/../../../build/controller/controller-functions.php';
require_once __DIR__ . '/../controllers/OasController.php';

date_default_timezone_set('America/Santiago');

$system = new systemClass();
$system->validarSesion();

$oasController = new OasController($system);

$server = new Swoole\WebSocket\Server("0.0.0.0", 9507);

$server->on('start', function ($server) {
    echo "Servidor WebSocket iniciado en 0.0.0.0:9507\n";
});

$server->on('open', function ($server, $request) {
    echo "Nuevo cliente conectado: FD {$request->fd}\n";
});

// Cada 10 segundos consulta la BD y envía datos actualizados
$server->tick(10000, function () use ($server, $oasController) {
    try {
        $faenas = $oasController->getFaenasConMetricas(true);
        // Agrega la fecha de actualización a cada registro
        foreach ($faenas as &$f) {
            $f['fecha_actualizacion'] = date("Y-m-d H:i:s");
        }
        unset($f);
        $faenasJSON = json_encode($faenas);
        foreach ($server->connections as $fd) {
            if ($server->isEstablished($fd)) {
                $server->push($fd, $faenasJSON);
            }
        }
    } catch (Exception $ex) {
        echo "Error: " . $ex->getMessage();
    }
});

$server->on('message', function ($server, $frame) {
    echo "Mensaje recibido de {$frame->fd}: {$frame->data}\n";
});

$server->on('close', function ($server, $fd) {
    echo "Cliente {$fd} desconectado\n";
});

$server->start();
?>
