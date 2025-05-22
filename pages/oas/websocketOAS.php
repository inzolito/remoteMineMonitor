<?php
// ws_server.php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");
require_once("../../build/controller/controller-alerta.php");

date_default_timezone_set('America/Santiago');

$system  = new systemClass();
$faenaCl = new faena();
$system->validarSesion();

/**
 * Función para transformar las métricas de cada faena y agregar propiedades adicionales.
 */
function transformarMetricas($faena)
{
    $metricas = $faena['metricas'];

    // Procesamiento para Tamanio Db
    $faena['tamanioDbValue'] = 0;
    $faena['unidadDb'] = "";
    if (isset($metricas['Tamanio Db'])) {
        $tamanioDbRaw = trim($metricas['Tamanio Db']);
        if (!empty($tamanioDbRaw)) {
            // Separamos las líneas y filtramos las vacías
            $lineasTamanio = array_values(array_filter(explode("\n", $tamanioDbRaw), 'strlen'));
            // Verificamos que haya al menos 3 líneas (estructura esperada)
            if (count($lineasTamanio) >= 3) {
                $valueLine = trim($lineasTamanio[2]); // Ejemplo: "4227 MB"
                $parts = preg_split('/\s+/', $valueLine);
                if (count($parts) >= 2) {
                    $faena['tamanioDbValue'] = floatval($parts[0]); // Convertimos el valor a número
                    $faena['unidadDb'] = strtoupper($parts[1]); // Normalizamos la unidad a mayúsculas
                }
            }
        }
    }

    // --- Transformación para Tabla con mayor ID ---
    $faena['tableName'] = ""; // Nombre de la tabla con más registros


    // Procesar el nombre de la tabla con más registros
    if (isset($metricas['Tabla Mas Registros'])) {
        $tablaMasRegistrosRaw = trim($metricas['Tabla Mas Registros']);
        if (!empty($tablaMasRegistrosRaw)) {
            $lineas = explode("\n", $tablaMasRegistrosRaw);
            if (isset($lineas[2])) { // Verificamos que exista la línea con los datos
                $parts = array_map('trim', explode("|", $lineas[2]));
                if (count($parts) >= 2) {
                    $faena['tableName'] = $parts[1]; // Nombre de la tabla
                }
            }
        }
    }


    // Procesar el ID máximo de la tabla con más registros
    $faena['idMaxTablaMasRegistrosValue'] = 0;
    if (isset($metricas['Id Max Tabla Mas Registros'])) {
        $idMaxTablaRaw = trim($metricas['Id Max Tabla Mas Registros']);

        if (!empty($idMaxTablaRaw)) {
            $lineas = explode("\n", $idMaxTablaRaw);
            if (isset($lineas[2])) {
                $valorLimpio = trim($lineas[2]);
                if (is_numeric($valorLimpio)) {
                    $faena['idMaxTablaMasRegistrosValue'] = intval($valorLimpio);
                }
            }
        }
    }

    // --- Transformación para "Top Tablas Mas Pesadas" ---
    $faena['topTablasMasPesadas'] = []; // Inicializamos el array para almacenar las tablas procesadas

    if (isset($metricas['Top Tablas Mas Pesadas'])) {
        $topTablasMasPesadasRaw = trim($metricas['Top Tablas Mas Pesadas']);
        if (!empty($topTablasMasPesadasRaw)) {
            $lineas = array_filter(explode("\n", $topTablasMasPesadasRaw), 'strlen'); // Separamos las líneas y eliminamos vacías
            foreach ($lineas as $linea) {
                // Ignoramos encabezados, separadores y líneas no válidas
                if (
                    preg_match('/^\s*schemaname\s*\|\s*tablename\s*\|\s*total_size\s*$/i', $linea) ||
                    preg_match('/^\s*[-+]+\s*$/', $linea) ||
                    preg_match('/^\(\d+\s+rows\)$/i', $linea)
                ) {
                    continue;
                }

                // Dividimos la línea por el separador "|"
                $parts = array_map('trim', explode("|", $linea));
                if (count($parts) === 3) {
                    $faena['topTablasMasPesadas'][] = [
                        'schema' => $parts[0], // Nombre del esquema
                        'table' => $parts[1],  // Nombre de la tabla
                        'size' => $parts[2]    // Tamaño total
                    ];
                }
            }
        }
    }


    // --- Transformación para Disco Duro ---
    $faena['diskPorcentaje'] = 0;
    $faena['discoUsado'] = "";
    $faena['discoTotal'] = "";
    if (isset($metricas['Espacio Total Disco Duro']) && isset($metricas['Espacio Utilizado Disco Duro'])) {
        // Quitamos espacios en blanco (y saltos de línea)
        $lineaDiscoTotal = trim($metricas['Espacio Total Disco Duro']);    // Ejemplo: "1013G"
        $lineaDiscoUtilizado = trim($metricas['Espacio Utilizado Disco Duro']); // Ejemplo: "764G"

        // Guardamos los valores para la vista (tal como vienen)
        $faena['discoTotal'] = $lineaDiscoTotal;
        $faena['discoUsado'] = $lineaDiscoUtilizado;

        // Extraemos la parte numérica de la cadena
        $discoTotalValue = floatval($lineaDiscoTotal);
        $discoUtilizadoValue = floatval($lineaDiscoUtilizado);

        // Obtenemos la unidad (último carácter, en mayúsculas)
        $unidadTotal = strtoupper(substr($lineaDiscoTotal, -1));
        $unidadUtilizado = strtoupper(substr($lineaDiscoUtilizado, -1));

        // Convertimos ambos valores a GB
        // Suponemos que si la unidad es 'G' ya están en GB, si es 'M' es MB y si es 'T' es TB
        if ($unidadTotal === 'M') {
            $discoTotalValue = $discoTotalValue / 1024;
        } elseif ($unidadTotal === 'T') {
            $discoTotalValue = $discoTotalValue * 1024;
        }
        if ($unidadUtilizado === 'M') {
            $discoUtilizadoValue = $discoUtilizadoValue / 1024;
        } elseif ($unidadUtilizado === 'T') {
            $discoUtilizadoValue = $discoUtilizadoValue * 1024;
        }

        // Calculamos el porcentaje (evitando división por cero)
        if ($discoTotalValue > 0) {
            $porcentaje = ($discoUtilizadoValue * 100) / $discoTotalValue;
            $faena['diskPorcentaje'] = (int)$porcentaje;
        }
    }

    // --- Transformación para Particiones Disco Duro ---
    $faena['lineasParticiones'] = [];
    if (isset($metricas['Particiones Disco Duro'])) {
        $particionesRaw = trim($metricas['Particiones Disco Duro']);
        if (!empty($particionesRaw)) {
            // Separamos por salto de línea y filtramos las líneas vacías
            $faena['lineasParticiones'] = array_values(array_filter(explode("\n", $particionesRaw), 'strlen'));
        }
    }


    // --- Transformación para Memoria RAM ---
    $faena['ramPorcentaje'] = 0;
    $faena['ramTotalStr'] = "";
    $faena['ramUsedStr'] = "";
    if (isset($metricas['Memoria Ram Utilizada']) && isset($metricas['Memoria Ram Total'])) {
        // Obtenemos los valores tal como vienen y eliminamos espacios en blanco
        $lineaRamUtilizada = trim($metricas['Memoria Ram Utilizada']); // Ej: "913Mi"
        $lineaRamTotal     = trim($metricas['Memoria Ram Total']);     // Ej: "7.7Gi"

        // Guardamos los strings para la vista
        $faena['ramUsedStr'] = $lineaRamUtilizada;
        $faena['ramTotalStr'] = $lineaRamTotal;

        // Convertimos a Gi: si se encuentra "mi" (no importa mayúsculas/minúsculas) dividimos entre 1024
        $ramUtilizada = (stripos($lineaRamUtilizada, 'mi') !== false) ? floatval($lineaRamUtilizada) / 1024 : floatval($lineaRamUtilizada);
        $ramTotal     = (stripos($lineaRamTotal, 'mi') !== false) ? floatval($lineaRamTotal) / 1024 : floatval($lineaRamTotal);

        // Calculamos el porcentaje de uso, siempre que el total sea mayor a cero
        if ($ramTotal > 0) {
            $faena['ramPorcentaje'] = round(($ramUtilizada * 100) / $ramTotal);
        }
    }

    // --- Transformación para top c Lectura ---
    $faena['lineasTopCLectura'] = [];
    if (isset($metricas['top c Lectura'])) {
        $topCLecturaRaw = trim($metricas['top c Lectura']);
        if (!empty($topCLecturaRaw)) {
            $faena['lineasTopCLectura'] = array_values(array_filter(explode("\n", $topCLecturaRaw), 'strlen'));
        }
    }

    // --- Transformación para Load Average ---
    $faena['loadAverageArray'] = [];
    if (isset($metricas['Load Average'])) {
        $loadAverageString = $metricas['Load Average'];
        if ($loadAverageString !== "") {
            $faena['loadAverageArray'] = array_map('floatval', array_map('trim', explode(',', $loadAverageString)));
        }
    }

    // --- Transformación para Tclas Max ---
    $faena['tclasValue'] = 0;
    if (isset($metricas['Tclas Max Tiempo Clasificar Eventos'])) {
        $tclasRaw = $metricas['Tclas Max Tiempo Clasificar Eventos'];
        $lineasTclas = explode("\n", $tclasRaw);
        if (count($lineasTclas) >= 3) {
            $rowData = explode("|", $lineasTclas[2]);
            if (count($rowData) == 2) {
                $faena['tclasValue'] = floatval(trim($rowData[0]));
            }
        }
    }

    // --- Transformación para Tclas Min ---
    $faena['tclasMinValue'] = 0;
    if (isset($metricas['Tclas Min Tiempo Clasificar Eventos'])) {
        $tclasMinRaw = $metricas['Tclas Min Tiempo Clasificar Eventos'];
        $lineasTclasMin = explode("\n", $tclasMinRaw);
        if (count($lineasTclasMin) >= 3) {
            $rowDataMin = explode("|", $lineasTclasMin[2]);
            if (count($rowDataMin) == 2) {
                $faena['tclasMinValue'] = floatval(trim($rowDataMin[0]));
            }
        }
    }

    // --- Transformación para TturnA Max ---
    $faena['tturnAMaxValue'] = 0;
    if (isset($metricas['TturnA Max Tiempo Clasificar Eventos'])) {
        $tturnAMaxRaw = $metricas['TturnA Max Tiempo Clasificar Eventos'];
        $lineasTturnAMax = explode("\n", $tturnAMaxRaw);
        if (count($lineasTturnAMax) >= 3) {
            $rowData = explode("|", $lineasTturnAMax[2]);
            if (count($rowData) == 2) {
                $faena['tturnAMaxValue'] = floatval(trim($rowData[0]));
            }
        }
    }

    // --- Transformación para TturnA Min ---
    $faena['tturnAMinValue'] = 0;
    if (isset($metricas['TturnA Min Tiempo Clasificar Eventos'])) {
        $tturnAMinRaw = $metricas['TturnA Min Tiempo Clasificar Eventos'];
        $lineasTturnAMin = explode("\n", $tturnAMinRaw);
        if (count($lineasTturnAMin) >= 3) {
            $rowDataMin = explode("|", $lineasTturnAMin[2]);
            if (count($rowDataMin) == 2) {
                $faena['tturnAMinValue'] = floatval(trim($rowDataMin[0]));
            }
        }
    }

    // --- Transformación para Equipos desconectados ---
    $faena['equipos'] = [];
    if (isset($metricas['Equipos desconectados'])) {
        $equiposDescData = $metricas['Equipos desconectados'];
        $lineasEquipos = array_values(array_filter(explode("\n", trim($equiposDescData)), 'strlen'));
        if (count($lineasEquipos) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEquipos)))) {
            array_pop($lineasEquipos);
        }
        for ($i = 2; $i < count($lineasEquipos); $i++) {
            $linea = trim($lineasEquipos[$i]);
            if (empty($linea)) continue;
            $columnas = array_map('trim', explode("|", $linea));
            if (count($columnas) >= 4) {
                $faena['equipos'][] = [
                    "ultima_conexion" => $columnas[0],
                    "equipo" => $columnas[1],
                    "estado" => $columnas[2],
                    "desconectado" => $columnas[3]
                ];
            }
        }
    }

    // --- Transformación para Equipos conectados ---
    $faena['equiposCon'] = [];
    if (isset($metricas['Equipos Conectados'])) {
        $equiposConData = $metricas['Equipos Conectados'];
        $lineasEquiposCon = array_values(array_filter(explode("\n", trim($equiposConData)), 'strlen'));
        if (count($lineasEquiposCon) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEquiposCon)))) {
            array_pop($lineasEquiposCon);
        }
        for ($i = 2; $i < count($lineasEquiposCon); $i++) {
            $linea = trim($lineasEquiposCon[$i]);
            if (empty($linea)) continue;
            $columnas = array_map('trim', explode("|", $linea));
            if (count($columnas) >= 4) {
                $faena['equiposCon'][] = [
                    "ultima_conexion" => $columnas[0],
                    "equipo" => $columnas[1],
                    "estado" => $columnas[2],
                    "conectado" => $columnas[3]
                ];
            }
        }
    }




    // --- Transformación para Reporte Snapchots ---
    $faena['snapchots'] = [];
    if (isset($metricas['Reporte Snapchots'])) {
        $reporteSnapchotsData = $metricas['Reporte Snapchots'];
        $lineasSnapchots = array_values(array_filter(explode("\n", trim($reporteSnapchotsData)), 'strlen'));
        if (count($lineasSnapchots) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasSnapchots)))) {
            array_pop($lineasSnapchots);
        }
        for ($i = 2; $i < count($lineasSnapchots); $i++) {
            $linea = trim($lineasSnapchots[$i]);
            if (empty($linea)) continue;
            $cols = array_map('trim', explode("|", $linea));
            if (count($cols) >= 3) {
                $faena['snapchots'][] = [
                    "ult_envio" => $cols[0],
                    "equipo" => $cols[1],
                    "enviado_hace" => $cols[2]
                ];
            }
        }
    }

    // --- Transformación para Eventos Generados ---
    $faena['eventosGenerados'] = [];
    if (isset($metricas['Eventos Generados'])) {
        $eventosGeneradosData = $metricas['Eventos Generados'];
        $lineasEventos = array_values(array_filter(explode("\n", trim($eventosGeneradosData)), 'strlen'));
        if (count($lineasEventos) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEventos)))) {
            array_pop($lineasEventos);
        }
        for ($i = 2; $i < count($lineasEventos); $i++) {
            $linea = trim($lineasEventos[$i]);
            if (empty($linea)) continue;
            $cols = array_map('trim', explode("|", $linea));
            if (count($cols) >= 3) {
                $faena['eventosGenerados'][] = [
                    "ult_envio" => $cols[0],
                    "equipo" => $cols[1],
                    "enviado_hace" => $cols[2]
                ];
            }
        }
    }

    // --- Transformación para Eventos sin clasificar turno actual ---
    $faena['eventosSinClasificarActual'] = [];
    if (isset($metricas['Eventos Sin Clasificar Turno Actual'])) {
        $eventosSinClasificarActualData = $metricas['Eventos Sin Clasificar Turno Actual'];
        $lineasEventosSinClasificarActual = array_values(array_filter(explode("\n", trim($eventosSinClasificarActualData)), 'strlen'));
        if (count($lineasEventosSinClasificarActual) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEventosSinClasificarActual)))) {
            array_pop($lineasEventosSinClasificarActual);
        }
        for ($i = 2; $i < count($lineasEventosSinClasificarActual); $i++) {
            $linea = trim($lineasEventosSinClasificarActual[$i]);
            if (empty($linea)) continue;
            $cols = array_map('trim', explode("|", $linea));
            if (count($cols) >= 9) {
                $faena['eventosSinClasificarActual'][] = [
                    "equipo" => $cols[0],
                    "creado" => $cols[1],
                    "llego_al_servidor" => $cols[2],
                    "demora_revision" => $cols[3],
                    "tipo_evento" => $cols[4],
                    "estado" => $cols[5],
                    "id" => $cols[6],
                    "time_server" => $cols[7],
                    "turno" => $cols[8]
                ];
            }
        }
    }

    // --- Transformación para Eventos sin clasificar turno anterior ---
    $faena['eventosSinClasificarAnterior'] = [];
    if (isset($metricas['Eventos Sin Clasificar Turno Anterior'])) {
        $eventosSinClasificarAnteriorData = $metricas['Eventos Sin Clasificar Turno Anterior'];
        $lineasEventosSinClasificarAnterior = array_values(array_filter(explode("\n", trim($eventosSinClasificarAnteriorData)), 'strlen'));
        if (count($lineasEventosSinClasificarAnterior) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEventosSinClasificarAnterior)))) {
            array_pop($lineasEventosSinClasificarAnterior);
        }
        for ($i = 2; $i < count($lineasEventosSinClasificarAnterior); $i++) {
            $linea = trim($lineasEventosSinClasificarAnterior[$i]);
            if (empty($linea)) continue;
            $cols = array_map('trim', explode("|", $linea));
            if (count($cols) >= 9) {
                $faena['eventosSinClasificarAnterior'][] = [
                    "equipo" => $cols[0],
                    "creado" => $cols[1],
                    "llego_al_servidor" => $cols[2],
                    "demora_revision" => $cols[3],
                    "tipo_evento" => $cols[4],
                    "estado" => $cols[5],
                    "id" => $cols[6],
                    "time_server" => $cols[7],
                    "turno" => $cols[8]
                ];
            }
        }
    }

    // --- Agregar contadores para el cliente ---
    $faena['numEquipos'] = isset($faena['equipos']) ? count($faena['equipos']) : 0;
    $faena['numEquiposCon'] = isset($faena['equiposCon']) ? count($faena['equiposCon']) : 0;
    $faena['countSnapchots'] = isset($faena['snapchots']) ? count($faena['snapchots']) : 0;
    $faena['countEventos'] = isset($faena['eventosGenerados']) ? count($faena['eventosGenerados']) : 0;
    $faena['countEventosSinClasificarActual'] = isset($faena['eventosSinClasificarActual']) ? count($faena['eventosSinClasificarActual']) : 0;
    $faena['countEventosSinClasificarAnterior'] = isset($faena['eventosSinClasificarAnterior']) ? count($faena['eventosSinClasificarAnterior']) : 0;

    return $faena;
}


/**
 * Función para obtener las faenas y sus métricas.
 */
function obtenerFaenasConMetricas($system)
{
    $conn = $system->conectaDB();
    if ($conn->connect_error) {
        echo "Error de conexión: " . $conn->connect_error;
        return [];
    }

    $queryFaenas = "
        SELECT 
        f.id          AS id_faena,
        f.faena       AS nombre_faena,
        s.id          AS id_servidor,
        s.nombre      AS nombre_servidor
    FROM faenas f
    JOIN faenas_servidores fs ON f.id = fs.id_faena
    JOIN servidores s         ON fs.id_servidor = s.id
    WHERE s.tipo_servidor = 'OAS';
    ";
    $resultFaenas = $conn->query($queryFaenas);
    if ($resultFaenas === false) {
        echo "Error en la consulta: " . $conn->error;
        $conn->close();
        return [];
    }

    $faenas = [];
    if ($resultFaenas->num_rows > 0) {
        $faenas = $resultFaenas->fetch_all(MYSQLI_ASSOC);
    }

    if (!empty($faenas)) {
        $serverIds = array_unique(array_column($faenas, 'id_servidor'));
        $serverIdsList = implode(",", $serverIds);
        $queryMetricas = "
            select ms.id_servidor, m.metrica, msv.valor 
        from metricas_servidores_valor msv join metricas_servidores ms on (ms.id = msv.id_metrica_servidor) 
        join metricas m on (ms.id_metrica=m.id) 
        
        where id_metrica_servidor in 
             ( SELECT id 
               FROM `metricas_servidores` 
               where id_servidor in (select id from servidores where id in ($serverIdsList)) 
             )
        ";
        $resultMetricas = $conn->query($queryMetricas);
        if ($resultMetricas === false) {
            echo "Error en la consulta de métricas: " . $conn->error;
            $conn->close();
            return $faenas;
        }

        $metricasPorServidor = [];
        while ($row = $resultMetricas->fetch_assoc()) {
            $idServidor    = $row['id_servidor'];
            $nombreMetrica = $row['metrica'];
            $valorMetrica  = $row['valor'];
            $metricasPorServidor[$idServidor][$nombreMetrica] = $valorMetrica;
        }

        foreach ($faenas as &$faena) {
            $idServidor = $faena['id_servidor'];
            $faena['metricas'] = isset($metricasPorServidor[$idServidor])
                ? $metricasPorServidor[$idServidor]
                : [];
            // Transformamos las métricas para añadir propiedades adicionales
            $faena = transformarMetricas($faena);
        }
        unset($faena);
    }

    $conn->close();
    return $faenas;
}

// Crear el servidor WebSocket en el puerto 9507
$server = new Swoole\WebSocket\Server("0.0.0.0", 9507);

$server->on('start', function ($server) {
    echo "Servidor WebSocket iniciado en 0.0.0.0:9507\n";
});

$server->on('open', function ($server, $request) {
    echo "Nuevo cliente conectado: FD {$request->fd}\n";
});

// Cada 20 segundos se consulta la DB y se envían los datos
$server->tick(10000, function () use ($server, $system) {
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
});

$server->on('close', function ($server, $fd) {
    echo "Cliente {$fd} desconectado\n";
});

$server->start();
