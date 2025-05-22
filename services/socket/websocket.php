<?php
use Swoole\WebSocket\Server;
use Swoole\Timer;

// Función auxiliar para leer un CSV y retornar los registros (con cabecera) en forma de array asociativo
function read_csv($file) {
    // Inicializa un array vacío para almacenar los registros
    $records = [];
    // Abre el archivo CSV en modo lectura
    if (($handle = fopen($file, "r")) !== FALSE) {
        // Leer la cabecera. Lee la primera línea del CSV que contiene los encabezados (La función fgetcsv lee una línea del archivo CSV y la convierte en un array, separando los valores por comas (o el delimitador especificado).)
        $headers = fgetcsv($handle, 1000, ",");
        //inicializa un contador en cero
        $id = 0;
        // Leer cada fila de datos, al ser la segunda vez que utilizamos fgetcsv, se lee desde la siguiente línea del archivo CSV (o sea la 2da linea, no encabezados) y se convierte en un array, separando los valores por comas (o el delimitador especificado).
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $record = [];//Inicializa un array para el registro actual
            // Iterar sobre los encabezados y los datos de la fila actual
            /*
                as $index => $header: Esta sintaxis se utiliza para iterar sobre el array $headers. En cada iteración del bucle:
                $index: Es la clave del elemento actual del array $headers. En este caso, es el índice numérico del encabezado.
                $header: Es el valor del elemento actual del array $headers. En este caso, es el nombre del encabezado.
            */
            foreach($headers as $index => $header) {
                // Asignar el valor del campo actual al registro actual
                //Si isset($data[$index]) es true, se asigna $data[$index] a $record[$header]. Si isset($data[$index]) es false, se asigna una cadena vacía '' a $record[$header].
                $record[$header] = isset($data[$index]) ? $data[$index] : '';
            }
            $record['id'] = $id;
            $records[$id] = $record;
            $id++;
        }
        fclose($handle);
    }
    return $records;
}
/*$records = [
    0 => [
        'CaseId' => 'jojojojojjo',
        'CaseNumber' => '00503557',
        'Status' => 'Closed',
        'OwnerName' => 'Claudio Ponce',
        'AccountName' => 'Minera Caserones',
        'Subject' => 'Monitoreo - MLCC - consultas sql',
        'Description' => 'Se detecta por el monitoreo que las consultas sql se estan encolando',
        'CaseResolution' => '<p>Se eliminan las consultas y se reinician los servicios sql ,se monitorea para que no se sigan encolando</p>',
        'CreatedDate' => '2023-12-31 21:58:23',
        'ClosedDate' => '2024-01-01 06:41:50',
        'ProductName' => 'Other MineOperate',
        'ProductFamily' => 'Operations',
        'id' => 0
    ]
]; */

$tickets_file = "/var/www/globalData/tickets.csv";
$trazabilidad_file = "/var/www/globalData/trazabilidad.csv";


// Inicializamos tiempos y datos previos (sin cabecera)
$tickets_last_mod_time = filemtime($tickets_file);
$trazabilidad_last_mod_time = filemtime($trazabilidad_file);
// Inicializamos los datos previos como un array vacío
$tickets_previous_data = [];
$trazabilidad_previous_data = [];

// Crear el servidor WebSocket en el puerto 9503
$server = new Server("0.0.0.0", 9503);
// Escuchar eventos de inicio, apertura, mensaje y cierre de conexión
$server->on("start", function (Server $server) use (
    $tickets_file, $trazabilidad_file, 
    &$tickets_last_mod_time, &$trazabilidad_last_mod_time, 
    &$tickets_previous_data, &$trazabilidad_previous_data
) {
    echo "Servidor WebSocket iniciado en ws://0.0.0.0:9503\n";
    // Iniciar un temporizador para verificar los cambios en los archivos CSV cada 2 segundos
    Timer::tick(2000, function () use (
        $server, $tickets_file, $trazabilidad_file, 
        &$tickets_last_mod_time, &$trazabilidad_last_mod_time, 
        &$tickets_previous_data, &$trazabilidad_previous_data
    ) {
        // --- Procesar tickets.csv ---
        clearstatcache(true, $tickets_file);
        //Si la fecha de modificación del archivo tickets.csv es mayor que la fecha de modificación almacenada en $tickets_last_mod_time, se actualiza $tickets_last_mod_time con la fecha de modificación del archivo tickets.csv y se procesan los cambios.
        if (filemtime($tickets_file) > $tickets_last_mod_time) {
            $tickets_last_mod_time = filemtime($tickets_file);
            //Leer los registros del archivo tickets.csv y almacenarlos en $current_data
            $current_data = read_csv($tickets_file);
            $changes = [];
            //Iterar sobre los registros actuales, read_csv asigna un identificador unico a cada registro leido del csv.
            foreach ($current_data as $key => $record) {
                //Si el registro actual no existe en los datos previos o si el registro actual es diferente al registro previo, se agrega el registro actual a $changes.
                if (!isset($tickets_previous_data[$key]) || $tickets_previous_data[$key] !== $record) {
                    //Se agrega el registro actual a $changes.
                    $changes[] = $record;
                }
            }
            //Iterar sobre los registros previos, esta vez para eliminar registros que ya no existen
            foreach ($tickets_previous_data as $key => $record) {
                //Si el registro previo no existe en los datos actuales, se agrega el registro previo a $changes con la propiedad 'deleted' establecida en true.
                if (!isset($current_data[$key])) {
                    $changes[] = ["id" => $key, "deleted" => true];
                }
            }
            //Si hay cambios, se envían a todos los clientes conectados.
            if (!empty($changes)) {
                foreach ($server->connections as $fd) {
                    $server->push($fd, json_encode([
                        "action" => "update",
                        "source" => "tickets",
                        "users"  => $changes
                    ]));
                }
                echo "Cambios detectados en tickets y enviados.\n";
            }
            $tickets_previous_data = $current_data;
        }

        // --- Procesar trazabilidad.csv ---
        clearstatcache(true, $trazabilidad_file);
        //Si la fecha de modificación del archivo trazabilidad.csv es mayor que la fecha de modificación almacenada en $trazabilidad_last_mod_time, se actualiza $trazabilidad_last_mod_time con la fecha de modificación del archivo trazabilidad.csv y se procesan los cambios.
        if (filemtime($trazabilidad_file) > $trazabilidad_last_mod_time) {
            $trazabilidad_last_mod_time = filemtime($trazabilidad_file);
            $current_data = read_csv($trazabilidad_file);
            $changes = [];
            foreach ($current_data as $key => $record) {
                if (!isset($trazabilidad_previous_data[$key]) || $trazabilidad_previous_data[$key] !== $record) {
                    $changes[] = $record;
                }
            }
            //Iterar sobre los registros previos, esta vez para eliminar registros que ya no existen
            foreach ($trazabilidad_previous_data as $key => $record) {
                if (!isset($current_data[$key])) {
                    $changes[] = ["id" => $key, "deleted" => true];
                }
            }
            //Si hay cambios, se envían a todos los clientes conectados.
            if (!empty($changes)) {
                foreach ($server->connections as $fd) {
                    $server->push($fd, json_encode([
                        "action" => "update",
                        "source" => "trazabilidad",
                        "users"  => $changes
                    ]));
                }
                echo "Cambios detectados en trazabilidad y enviados.\n";
            }
            //Actualizar los datos previos con los datos actuales
            $trazabilidad_previous_data = $current_data;
        }
    });
});

$server->on("open", function (Server $server, $request) use ($tickets_file, $trazabilidad_file) {
    echo "Nueva conexión: Cliente {$request->fd}\n";
    
    // Enviar datos iniciales para tickets.csv
    $tickets_records = read_csv($tickets_file);
    $server->push($request->fd, json_encode([
        "action" => "sync",
        "source" => "tickets",
        "users"  => array_values($tickets_records)
    ]));
    
    // Enviar datos iniciales para trazabilidad.csv
    $trazabilidad_records = read_csv($trazabilidad_file);
    $server->push($request->fd, json_encode([
        "action" => "sync",
        "source" => "trazabilidad",
        "users"  => array_values($trazabilidad_records)
    ]));
    
    echo "Datos iniciales enviados al cliente {$request->fd}.\n";
});

$server->on("message", function (Server $server, $frame) {
    echo "Mensaje recibido: {$frame->data}\n";
});

$server->on("close", function (Server $server, $fd) {
    echo "Cliente {$fd} desconectado\n";
});

$server->start();