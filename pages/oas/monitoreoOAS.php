<?php
// Habilitar la visualización de errores (solo en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'controllerOAS.php'; ?>


<style>
  /*
  body {
    font-size: 70%;
  }

  .widget-user-header {
    background-color: black !important;
    color: white;
  }

  .chart-container {
    position: relative;
    height: 200px;
    width: 200px;
    margin: 0 auto;
  }

  .small-box {
    padding: 20px;
  }

  .small-box .icon {
    font-size: 20px;
    line-height: 90px;
  }

  .my-card {
    position: absolute;
    left: 50%;
    top: -20px;
    transform: translateX(-50%);
    border-radius: 50%;
  }

  .modal-dialog.modal-custom {
    max-width: 90%;
  }

  .modal-content {
    font-size: 2em;
  }

  .progress-group {

    display: flex;

    flex-direction: column;

    align-items: center;

  }



  .progress-group h4 {

    text-align: center;

  }



  .knob-container input {

    width: 100% !important;

    height: auto !important;

  }



  @media (max-width: 768px) {

    .progress-group {

      flex-direction: column;

    }



    .knob-container {

      max-width: 100px;

    }

  }
  */
</style>

<div class="row">
  <?php foreach ($faenas as $faena):

    // ===== Procesamos "Tamanio Db" =====
    $tamanioDbRaw = $faena['metricas']['Tamanio Db'] ?? "";
    $tamanioDbValue = 0;
    $unidadDb = "";
    if (!empty($tamanioDbRaw)) {
      $lineas = explode("\n", trim($tamanioDbRaw));
      if (isset($lineas[2])) {
        $parts = preg_split('/\s+/', trim($lineas[2]));
        $tamanioDbValue = floatval($parts[0]);
        $unidadDb = $parts[1] ?? "";
      }
    }

    // ===== Procesamos "Tabla Mas Registros" =====
    $tablaMasRegistrosRaw = $faena['metricas']['Tabla Mas Registros'] ?? "";
    $schemaName = "";
    $tableName = "";
    $rowEstimate = 0;
    if (!empty($tablaMasRegistrosRaw)) {
      $lineas = explode("\n", trim($tablaMasRegistrosRaw));
      if (isset($lineas[2])) {
        $parts = preg_split('/\s+\|\s+/', trim($lineas[2])); // Dividimos por "|"
        if (count($parts) === 3) {
          $schemaName = trim($parts[0]);
          $tableName = trim($parts[1]);
          $rowEstimate = intval($parts[2]); // Convertimos a entero
        }
      }
    }

    // ===== Procesamos "Id Max Tabla Mas Registros" =====
    $idMaxTablaMasRegistrosRaw = $faena['metricas']['Id Max Tabla Mas Registros'] ?? "";
    $idMaxTablaMasRegistrosValue = 0;

    if (!empty($idMaxTablaMasRegistrosRaw)) {
      $lineas = explode("\n", trim($idMaxTablaMasRegistrosRaw));
      if (isset($lineas[2])) { // Verificamos que exista la línea con el valor
        $idMaxTablaMasRegistrosValue = intval(trim($lineas[2])); // Convertimos el valor a entero
      }
    }

    // ===== Procesamos "Top Tablas Mas Pesadas" =====
    $topTablasMasPesadasRaw = $faena['metricas']['Top Tablas Mas Pesadas'] ?? "";
    $topTablasMasPesadas = []; // Array para almacenar las tablas procesadas

    if (!empty($topTablasMasPesadasRaw)) {
      $lineas = array_filter(explode("\n", trim($topTablasMasPesadasRaw)), 'strlen'); // Separamos las líneas y eliminamos vacías
      foreach ($lineas as $linea) {
        // Ignoramos las líneas de encabezado y las líneas que no contienen datos válidos
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
          $topTablasMasPesadas[] = [
            'schema' => $parts[0], // Nombre del esquema
            'table' => $parts[1],  // Nombre de la tabla
            'size' => $parts[2]    // Tamaño total
          ];
        }
      }
    }

    // ===== Procesamos "Espacio Disco Duro" =====
    $lineaDiscoTotal = isset($faena['metricas']['Espacio Total Disco Duro']) ? $faena['metricas']['Espacio Total Disco Duro'] : "";
    $lineaDiscoUtilizado = isset($faena['metricas']['Espacio Utilizado Disco Duro']) ? $faena['metricas']['Espacio Utilizado Disco Duro'] : "";

    // Quitamos espacios en blanco al inicio y fin
    $lineaDatosDTotal = trim($lineaDiscoTotal);   // Ej: "815G"
    $lineaDatosDUtilizado = trim($lineaDiscoUtilizado); // Ej: "200G"

    // Extraemos la parte numérica (por ejemplo, 815 o 200)
    $discoTotalValue = floatval($lineaDatosDTotal);
    $discoUtilizadoValue = floatval($lineaDatosDUtilizado);

    // Extraemos la unidad (la última letra de la cadena)
    $unidadTotal = strtoupper(substr($lineaDatosDTotal, -1));
    $unidadUtilizado = strtoupper(substr($lineaDatosDUtilizado, -1));

    // Convertimos ambos valores a GB (esto asume que si la unidad es 'G', ya está en GB)
    // Si la unidad es 'M' (megabytes), lo dividimos entre 1024.
    // Si es 'T' (terabytes), lo multiplicamos por 1024.
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

    // Calculamos el porcentaje de uso
    $porcentaje = ($discoUtilizadoValue * 100) / $discoTotalValue;
    $miValor = (int)$porcentaje;  // Se convierte a entero

    // Asignamos el color para la gráfica de dona basado en el porcentaje
    if ($miValor <= 75) {
      $graficoDonutColor = "GraficoVerde";
    } elseif ($miValor <= 90) {
      $graficoDonutColor = "GraficoAmarillo";
    } else {
      $graficoDonutColor = "GraficoRojo";
    }


    // ===== Procesamos "Particiones Disco Duro" =====
    $particionesRaw = isset($faena['metricas']['Particiones Disco Duro']) ? $faena['metricas']['Particiones Disco Duro'] : "";
    $lineasParticiones = [];
    if (!empty($particionesRaw)) {
      // Separamos las líneas y filtramos vacíos
      $lineasParticiones = array_filter(explode("\n", trim($particionesRaw)), 'strlen');
    }


    // ===== Procesamos "Memoria RAM" =====
    $lineaRamUtilizada = isset($faena['metricas']['Memoria Ram Utilizada']) ? $faena['metricas']['Memoria Ram Utilizada'] : "";
    $lineaRamTotal     = isset($faena['metricas']['Memoria Ram Total']) ? $faena['metricas']['Memoria Ram Total'] : "";

    $ramPorcentaje = 0;
    $ramUsedStr    = trim($lineaRamUtilizada); // Ej: "913Mi"
    $ramTotalStr   = trim($lineaRamTotal);     // Ej: "7.7Gi"

    // Convertimos a Gi usando el condicional: si se encuentra "mi" (sin importar mayúsculas/minúsculas) se divide entre 1024, de lo contrario asumimos que ya está en Gi.
    $ramUtilizada = (stripos($ramUsedStr, 'mi') !== false) ? floatval($ramUsedStr) / 1024 : floatval($ramUsedStr);
    $ramTotal     = (stripos($ramTotalStr, 'mi') !== false) ? floatval($ramTotalStr) / 1024 : floatval($ramTotalStr);

    // Calculamos el porcentaje de uso
    if ($ramTotal > 0) {
      $ramPorcentaje = round(($ramUtilizada * 100) / $ramTotal);
    }

    // Asignamos el color de la gráfica basado en el porcentaje
    if ($ramPorcentaje <= 75) {
      $graficoRamColor = "GraficoVerde";
    } elseif ($ramPorcentaje <= 90) {
      $graficoRamColor = "GraficoAmarillo";
    } else {
      $graficoRamColor = "GraficoRojo";
    }


    // ===== Procesamos "top c Lectura" =====
    $topCLecturaRaw = isset($faena['metricas']['top c Lectura']) ? $faena['metricas']['top c Lectura'] : "";
    $lineasTopCLectura = [];
    if (!empty($topCLecturaRaw)) {
      $lineasTopCLectura = array_filter(explode("\n", trim($topCLecturaRaw)), 'strlen');
    }
    $faena['lineasTopCLectura'] = array_values($lineasTopCLectura);



    // ===== Procesamos "Load Average" =====
    $loadAverageString = isset($faena['metricas']['Load Average']) ? $faena['metricas']['Load Average'] : "";
    if ($loadAverageString !== "") {
      $loadAverageArray = array_map('floatval', array_map('trim', explode(',', $loadAverageString)));
    } else {
      $loadAverageArray = array();
    }
    $canvasID = "loadAverageChart_" . $faena['id_faena'];

    // ===== Procesamos "Tclas Max Tiempo Clasificar Eventos" =====
    $tclasRaw = $faena['metricas']['Tclas Max Tiempo Clasificar Eventos'] ?? "";
    $tclasValue = 0;
    if (!empty($tclasRaw)) {
      $lineasTclas = explode("\n", $tclasRaw);
      if (count($lineasTclas) >= 3) {
        $rowData = explode("|", $lineasTclas[2]);
        if (count($rowData) == 2) {
          $tclasValue = floatval(trim($rowData[0]));
        }
      }
    }
    $tclasValueRaw = $tclasValue;  // Guardamos el valor sin formatear
    $tclasCanvasID = "tclasMaxChart_" . $faena['id_faena'];

    // ===== Procesamos "Tclas Min Tiempo Clasificar Eventos" =====
    $tclasMinRaw = $faena['metricas']['Tclas Min Tiempo Clasificar Eventos'] ?? "";
    $tclasMinValue = 0;
    if (!empty($tclasMinRaw)) {
      $lineasTclasMin = explode("\n", $tclasMinRaw);
      if (count($lineasTclasMin) >= 3) {
        $rowDataMin = explode("|", $lineasTclasMin[2]);
        if (count($rowDataMin) == 2) {
          $tclasMinValue = floatval(trim($rowDataMin[0]));
        }
      }
    }
    $tclasMinValueRaw = $tclasMinValue;  // Valor crudo en segundos
    $tclasMinCanvasID = "tclasMinChart_" . $faena['id_faena'];

    // ===== Procesamos "TturnA Max Tiempo Clasificar Eventos" =====
    $tturnAMaxRaw = $faena['metricas']['TturnA Max Tiempo Clasificar Eventos'] ?? "";
    $tturnAMaxValue = 0;
    if (!empty($tturnAMaxRaw)) {
      $lineasTturnAMax = explode("\n", $tturnAMaxRaw);
      if (count($lineasTturnAMax) >= 3) {
        $rowData = explode("|", $lineasTturnAMax[2]);
        if (count($rowData) == 2) {
          $tturnAMaxValue = floatval(trim($rowData[0]));
        }
      }
    }
    $tturnAMaxValueRaw = $tturnAMaxValue;  // Valor crudo en minutos
    $tturnAMaxCanvasID = "tturnAMaxChart_" . $faena['id_faena'];

    // ===== Procesamos "TturnA Min Tiempo Clasificar Eventos" =====
    $tturnAMinRaw = $faena['metricas']['TturnA Min Tiempo Clasificar Eventos'] ?? "";
    $tturnAMinValue = 0;
    if (!empty($tturnAMinRaw)) {
      $lineasTturnAMin = explode("\n", $tturnAMinRaw);
      if (count($lineasTturnAMin) >= 3) {
        $rowDataMin = explode("|", $lineasTturnAMin[2]);
        if (count($rowDataMin) == 2) {
          $tturnAMinValue = floatval(trim($rowDataMin[0]));
        }
      }
    }
    $tturnAMinValueRaw = $tturnAMinValue;  // Valor crudo en segundos
    $tturnAMinCanvasID = "tturnAMinChart_" . $faena['id_faena'];

    // ===== Procesamos Equipos desconectados =====
    $equiposDescData = isset($faena['metricas']['Equipos desconectados']) ? $faena['metricas']['Equipos desconectados'] : "";
    $lineasEquipos = array_values(array_filter(explode("\n", trim($equiposDescData)), 'strlen'));
    if (count($lineasEquipos) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEquipos)))) {
      array_pop($lineasEquipos);
    }
    $numEquipos = (count($lineasEquipos) > 2) ? count($lineasEquipos) - 2 : 0;

    // ===== Procesamos Equipos conectados =====
    $equiposConData = isset($faena['metricas']['Equipos Conectados']) ? $faena['metricas']['Equipos Conectados'] : "";
    $lineasEquiposCon = array_values(array_filter(explode("\n", trim($equiposConData)), 'strlen'));
    if (count($lineasEquiposCon) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEquiposCon)))) {
      array_pop($lineasEquiposCon);
    }
    $numEquiposCon = (count($lineasEquiposCon) > 2) ? count($lineasEquiposCon) - 2 : 0;

    // ===== Procesamos Reporte Snapchots =====
    $reporteSnapchotsData = isset($faena['metricas']['Reporte Snapchots']) ? $faena['metricas']['Reporte Snapchots'] : "";
    $lineasSnapchots = array_values(array_filter(explode("\n", trim($reporteSnapchotsData)), 'strlen'));
    if (count($lineasSnapchots) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasSnapchots)))) {
      array_pop($lineasSnapchots);
    }
    $countSnapchots = (count($lineasSnapchots) > 2) ? count($lineasSnapchots) - 2 : 0;

    // ===== Procesamos Eventos Generados =====
    $eventosGeneradosData = isset($faena['metricas']['Eventos Generados']) ? $faena['metricas']['Eventos Generados'] : "";
    $lineasEventos = array_values(array_filter(explode("\n", trim($eventosGeneradosData)), 'strlen'));
    if (count($lineasEventos) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEventos)))) {
      array_pop($lineasEventos);
    }
    $countEventos = (count($lineasEventos) > 2) ? count($lineasEventos) - 2 : 0;

    // ===== Procesamos Eventos sin clasificar turno actual =====
    $eventosSinClasificarActualData = isset($faena['metricas']['Eventos Sin Clasificar Turno Actual']) ? $faena['metricas']['Eventos Sin Clasificar Turno Actual'] : "";
    $lineasEventosSinClasificarActual = array_values(array_filter(explode("\n", trim($eventosSinClasificarActualData)), 'strlen'));
    if (count($lineasEventosSinClasificarActual) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEventosSinClasificarActual)))) {
      array_pop($lineasEventosSinClasificarActual);
    }
    $countEventosSinClasificarActual = (count($lineasEventosSinClasificarActual) > 2) ? count($lineasEventosSinClasificarActual) - 2 : 0;

    // ===== Procesamos Eventos sin clasificar turno anterior =====
    $eventosSinClasificarAnteriorData = isset($faena['metricas']['Eventos Sin Clasificar Turno Anterior']) ? $faena['metricas']['Eventos Sin Clasificar Turno Anterior'] : "";
    $lineasEventosSinClasificarAnterior = array_values(array_filter(explode("\n", trim($eventosSinClasificarAnteriorData)), 'strlen'));
    if (count($lineasEventosSinClasificarAnterior) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEventosSinClasificarAnterior)))) {
      array_pop($lineasEventosSinClasificarAnterior);
    }
    $countEventosSinClasificarAnterior = (count($lineasEventosSinClasificarAnterior) > 2) ? count($lineasEventosSinClasificarAnterior) - 2 : 0;


  ?>

    <div class="col-md-6">

      <div class="card card-widget shadow widget-user">

        <div class="card-header">
          <!-- Asignamos IDs únicos para el nombre y fecha de actualización -->
          <h3 id="faenaName_<?php echo $faena['id_faena']; ?>" class="card-title"> <b><?php echo $faena["nombre_faena"]; ?></b> </h3>
          <div class="card-tools">
            <span class="badge" style="font-size: 1.0em">
              <i class="fas fa-calendar"></i>
              <?php echo date("d", strtotime($system->datatimeCargaDiv())); ?>
              <i class="fas fa-clock ml-1"></i>
              <?php echo date("H:i", strtotime($system->datatimeCargaDiv())); ?>
            </span>
          </div>
        </div>

        <div class="card-body" style="padding-top:10px;">
          <div class="row">
            <div class="col-md-12">
              <div class="row">

                <!-- Bloque para Disco Duro -->
                <div class="col-md-5">
                  <div class="card card-navy">
                    <div class="card-header">
                      <h3 class="card-title"><?php echo $faena['nombre_servidor']; ?></h3>
                    </div>
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="text-left">Disco Duro</div>
                          <!-- Input Knob con ID único -->

                          <input type="text" id="diskKnob_<?php echo $faena['id_faena']; ?>"
                            value="<?php echo $miValor; ?>" class="<?php echo $graficoDonutColor; ?>"
                            data-width="100%" data-height="100%" data-readonly="true">


                          <div class="progress-group mt-2 px-2">

                            HD
                            <span class="float-right">
                              <b id="diskUsage_<?php echo $faena['id_faena']; ?>">
                                <?php echo $lineaDatosDUtilizado; ?>/<?php echo $lineaDatosDTotal; ?>
                              </b>
                            </span>

                            <div class="progress progress-sm">
                              <div id="diskProgressBar_<?php echo $faena['id_faena']; ?>"
                                class="progress-bar bg-primary"
                                style="width: <?php echo $miValor; ?>%;">
                              </div>
                            </div>

                            <div class="row">
                              <div class="col-12 my-2">
                                <button type="button" class="btn btn-outline-dark w-100" data-toggle="modal" data-target="#particionesModal_<?php echo $faena['id_faena']; ?>">
                                  Ver detalle
                                </button>
                              </div>
                            </div>

                          </div>
                        </div>


                        <!-- Bloque para Memoria Ram -->
                        <div class="col-md-6">
                          <div class="text-left">Memoria Ram</div>
                          <input type="text" id="ramKnob_<?php echo $faena['id_faena']; ?>"
                            value="<?php echo $ramPorcentaje; ?>" class="<?php echo $graficoRamColor; ?>"
                            data-width="100%" data-height="100%" data-readonly="true">

                          <div class="progress-group mt-2 px-2">
                            RAM<span class="float-right">
                              <b id="ramUsage_<?php echo $faena['id_faena']; ?>"><?php echo $ramUsedStr; ?>/<?php echo $ramTotalStr; ?></b>
                            </span>

                            <div class="progress progress-sm">
                              <div id="ramProgressBar_<?php echo $faena['id_faena']; ?>"
                                class="progress-bar bg-primary">
                              </div>
                            </div>
                            <div class="row">
                              <div class="col-12 my-2">
                                <button type="button" class="btn btn-outline-dark w-100" data-toggle="modal" data-target="#topCLecturaModal_<?php echo $faena['id_faena']; ?>">
                                  Ver detalle
                                </button>
                              </div>
                            </div>


                          </div>
                        </div>
                      </div>
                      <!-- Bloque para la gráfica de Load Average -->
                      <hr>
                      <div class="row d-flex mt-3">
                        <div class="row d-flex align-items-center justify-content-center mx-auto">

                          <div class="text-left" id="cpuLoadAverage_<?php echo $faena['id_faena']; ?>">
                            CPU - Load Average: <?php echo round(array_sum($loadAverageArray) / count($loadAverageArray), 2); ?>
                          </div>

                        </div>
                        <div class="col-md-12 text-center d-flex justify-content-center pt-3">
                          <canvas id="<?php echo $canvasID; ?>" style="width: 100%; max-height: 300px; aspect-ratio: 2 / 1;"></canvas>
                        </div>

                      </div>
                    </div>
                    <div class="card-footer" style="padding: 5px 0;">
                      <div class="row">
                        <div class="col-md-6 d-flex justify-content-center">
                          <p>Nom. Server
                            <b id="nombreServidor_<?php echo $faena['id_faena']; ?>" class="d-block">
                              <?php echo htmlspecialchars($faena['metricas']["Nombre Servidor"] ?? ""); ?>
                            </b>
                          </p>
                        </div>
                        <div class="col-md-6 d-flex justify-content-center">
                          <p>IP
                            <b id="ipServidor_<?php echo $faena['id_faena']; ?>" class="d-block">
                              <?php echo htmlspecialchars($faena['metricas']["Ip Servidor"] ?? ""); ?>
                            </b>
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!------------------------------------------------------------------------------------------------------>
                  <!-----------------------------------------  Bloque de "Servicios" ------------------------------------->
                  <!------------------------------------------------------------------------------------------------------>

                  <?php
                  $servicios = [
                    ['key' => 'Servicio de Grafana',     'label' => 'Grafana',        'icon' => 'ion ion-stats-bars'],
                    ['key' => 'Servicio de PostgreSQL',  'label' => 'PostgreSQL',     'icon' => 'fa-solid fa-database'],
                    ['key' => 'Servicio Rsyslog',        'label' => 'Rsyslog',        'icon' => 'fas fa-file-alt'],
                    ['key' => 'Servicio SSH',            'label' => 'SSH',            'icon' => 'fa fa-plug'],
                    ['key' => 'Servicio gvuploads',      'label' => 'gvuploads',      'icon' => 'ion ion-pie-graph'],
                    ['key' => 'Servicio Mosquitto',      'label' => 'Mosquitto',      'icon' => 'fa fa-exchange'],
                    ['key' => 'Servicio NTP',            'label' => 'NTP',            'icon' => 'fa-solid fa-clock'],
                    ['key' => 'Open VM Tools',           'label' => 'Open VM Tools',  'icon' => 'fa fa-sitemap'],
                    ['key' => 'Servicio VPN',            'label' => 'VPN',            'icon' => 'fa fa-wifi'],
                  ];
                  ?>

                  <div class="card card-navy mt-3">
                    <div class="card-header">
                      <h3 class="card-title">Servicios</h3>
                    </div>
                    <div class="card-body">
                      <div class="row d-flex align-items-stretch flex-wrap">
                        <?php foreach ($servicios as $servicio): ?>
                          <?php
                          $raw = $faena['metricas'][$servicio['key']] ?? "";
                          $estado = trim($raw);
                          $clase = (preg_match('/^\s*Active:\s+active/i', $estado)) ? "bg-success" : "bg-danger";
                          ?>
                          <div class="col-lg-4 col-6 mb-3">
                            <div id="servicio_<?php echo preg_replace('/\s+/', '', $servicio['label']) . "_" . $faena['id_faena']; ?>" class="small-box small-box-2 <?php echo $clase; ?> h-100" style="cursor: pointer;">
                              <div class="inner">
                                <h6><?php echo $servicio['label']; ?></h6>
                              </div>
                              <div class="icon">
                                <i class="<?php echo $servicio['icon']; ?>" style="font-size: 30px !important;"></i>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>



                </div>




                <div class="col-md-7">
                  <div class="card card-navy">
                    <div class="card-header">
                      <h3 class="card-title">Servicio OAS</h3>
                    </div>
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-12"><span>Estado del servicio OAS mes de <?php echo date("%F") ?> </span></div>
                        <?php
                        $online = (int) $faena['metricas']['Seg Online Oas Service'];
                        $offline = (int) $faena['metricas']['Seg Offline Oas Service'];
                        $total = $online + $offline;

                        $porcentaje = $total > 0 ? ($online / $total) * 100 : 0;
                        $porcentaje_redondeado = round($porcentaje, 3);
                        if ($offline === 0) $porcentaje_redondeado = 100;
                        $total = $online + $offline;

                        $horas = floor($online / 3600);
                        $minutos = floor(($online % 3600) / 60);

                        ?>

                        <div class="col-md-6 d-flex flex-column justify-content-center align-items-center fs-1 text-success">
                          <div class="text-success">
                            <span class="h1" style="font-family: 'Anton', sans-serif; font-size: 3rem; font-weight: 800;"><?php echo $horas; ?></span><span class="h6">H </span>
                            <span class="h1" style="font-family: 'Anton', sans-serif; font-size: 3rem; font-weight: 800;"><?php echo $minutos; ?></span><span class="h6">Min</span>
                          </div>
                          <div class="fs-6 text-muted">Tiempo online</div>
                        </div>

                        <div class="col-md-6 d-flex flex-column justify-content-center align-items-center fs-1 text-success">
                          <div class="text-success">
                            <span class="h1" style="font-family: 'Anton', sans-serif; font-size: 3rem; font-weight: 800;"><?php echo $porcentaje_redondeado; ?></span><span class="h6">% </span>
                          </div>
                          <div class="fs-6 text-muted">Disponibilidad</div>
                        </div>


                      </div>


                    </div>
                    <div class="card-footer" style="padding: 5px 1px;">
                      <p class="text-muted">* El servicio está corriendo indefinidamente desde </p>
                    </div>
                  </div>



                  <div class="card card-navy">
                    <div class="card-header">
                      <h3 class="card-title">Consultas Grafana</h3>
                    </div>
                    <div class="card-body">



                      <div class="row">

                        <div class="col-md-6 mx-auto my-sm-1">
                          <div class="card ">

                            <div class="text-center mt-3">
                              n° equipos desconectados
                            </div>
                            <div class="text-info text-center mt-2">
                              <h1 id="numEquipos_<?php echo $faena['id_faena']; ?>"><?php echo $numEquipos; ?></h1>
                            </div>
                            <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#miModal_<?php echo $faena['id_faena']; ?>">
                              ver detalle
                            </button>
                          </div>
                        </div>

                        <div class="col-md-6 mx-auto my-sm-1">
                          <div class="card">
                            <div class="text-center mt-3">
                              n° equipos conectados
                            </div>
                            <div class="text-info text-center mt-2">
                              <h1 id="countConectados_<?php echo $faena['id_faena']; ?>"><?php echo $numEquiposCon; ?></h1>
                            </div>
                            <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#eConectados<?php echo $faena['id_faena']; ?>">
                              ver detalle
                            </button>
                          </div>
                        </div>


                        <div class="col-md-6 mx-auto my-sm-1">
                          <div class="card  ">

                            <div class="text-center mt-3">
                              reportes de snapchots
                            </div>
                            <div class="text-info text-center mt-2">
                              <h1 id="countSnapchots_<?php echo $faena['id_faena']; ?>"><?php echo $countSnapchots; ?></h1>
                            </div>
                            <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#snapchots<?php echo $faena['id_faena']; ?>">
                              ver detalle
                            </button>
                          </div>
                        </div>


                        <div class="col-md-6 mx-auto my-sm-1">
                          <div class="card ">

                            <div class="text-center mt-3">
                              n° eventos generados
                            </div>
                            <div class="text-info  text-center mt-2">
                              <h1 id="countEventos_<?php echo $faena['id_faena']; ?>"><?php echo $countEventos; ?></h1>
                            </div>
                            <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#egenerados<?php echo $faena['id_faena']; ?>">
                              ver detalle
                            </button>
                          </div>
                        </div>

                      </div>

                      <!-------------------------Contenido del collapse grafana------------------------->
                      <div class="collapse" id="extraContent_<?php echo $faena['id_faena']; ?>">
                        <div class="row d-flex justify-content-center mb-4">
                          <h5>Tiempos de clasificación monitoreo turno actual</h5>
                        </div>
                        <!-- Tarjeta para Tclas Max -->
                        <div class="row">

                          <div class="col-md-6 mx-auto my-sm-1">
                            <div class="card">
                              <div class="text-center mt-3">
                                Tiempo máximo
                              </div>
                              <div class="text-info text-center mt-2">
                                <h1 id="tclasValue_<?php echo $faena['id_faena']; ?>"
                                  data-raw-tclas="<?php echo $tclasValueRaw; ?>">
                                  <?php echo formatTimeMaxPhp($tclasValueRaw); ?>
                                </h1>
                              </div>
                            </div>
                          </div>


                          <!-- Tarjeta para Tclas Min -->
                          <div class="col-md-6 mx-auto my-sm-1">
                            <div class="card ">

                              <div class="text-center mt-3">
                                Tiempo mínimo
                              </div>
                              <div class="text-info text-center mt-2">
                                <!-- ID para Tclas Min -->
                                <h1 id="tclasMinValue_<?php echo $faena['id_faena']; ?>"
                                  data-raw-tclasmin="<?php echo $tclasMinValueRaw; ?>">
                                  <?php echo formatTimeMinPhp($tclasMinValueRaw); ?>
                                </h1>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="row d-flex justify-content-center mb-4">
                          <h5>Tiempos de clasificación monitoreo turno anterior</h5>
                        </div>


                        <div class="row">
                          <div class="col-md-5 mx-auto  my-sm-1">
                            <div class="card ">
                              <div class="text-center mt-3">
                                Tiempo máximo
                              </div>
                              <div class="text-info text-center mt-2">
                                <!-- ID para TturnA Max -->
                                <h1 id="tturnAMaxValue_<?php echo $faena['id_faena']; ?>"
                                  data-raw-tturnamax="<?php echo $tturnAMaxValueRaw; ?>">
                                  <?php echo formatTimeMaxPhp($tturnAMaxValueRaw); ?>
                                </h1>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-5 mx-auto">
                            <div class="card">

                              <div class="text-center mt-3">
                                Tiempo mínimo
                              </div>
                              <div class="text-info text-center mt-2">
                                <!-- ID para TturnA Min -->
                                <h1 id="tturnAMinValue_<?php echo $faena['id_faena']; ?>"
                                  data-raw-tturnamin="<?php echo $tturnAMinValueRaw; ?>">
                                  <?php echo formatTimeMinPhp($tturnAMinValueRaw); ?>
                                </h1>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="row d-flex justify-content-center mb-4">
                          <h5>Eventos sin clasificar</h5>
                        </div>
                        <div class="row">
                          <div class="col-md-5 mx-auto">
                            <div class="card">

                              <div class="text-center mt-3">
                                Turno actual
                              </div>
                              <div class="text-info text-center mt-2">
                                <h1 id="countEventosSinClasificarActual_<?php echo $faena['id_faena']; ?>"><?php echo $countEventosSinClasificarActual; ?></h1>
                              </div>
                              <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#eturnoactual<?php echo $faena['id_faena']; ?>">
                                ver detalle
                              </button>
                            </div>
                          </div>
                          <div class="col-md-5 mx-auto">
                            <div class="card">

                              <div class="text-center mt-3">
                                Turno anterior
                              </div>
                              <div class="text-info text-center mt-2">
                                <h1 id="countEventosSinClasificarAnterior_<?php echo $faena['id_faena']; ?>"><?php echo $countEventosSinClasificarAnterior; ?></h1>
                              </div>
                              <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#eturnoanterior<?php echo $faena['id_faena']; ?>">
                                ver detalle
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>


                    </div>
                    <div class="card-footer p-2"> <!-- reduce el padding del footer -->
                      <button class="btn btn-light w-100 py-2" type="button"
                        data-toggle="collapse"
                        data-target="#extraContent_<?php echo $faena['id_faena']; ?>"
                        aria-expanded="false"
                        aria-controls="extraContent_<?php echo $faena['id_faena']; ?>">
                        Ver detalle
                      </button>
                    </div>

                  </div>





                  <div class="card card-navy">
                    <div class="card-header">
                      <h3 class="card-title">Base de datos</h3>
                    </div>
                    <div class="card-body">

                      <div class="row">
                        <!-- Columna del ícono + texto -->
                        <div class="col-md-12 d-flex align-items-center mb-3">
                          <i class="far fa-database fa-2x me-2"></i> <!-- Ícono con margen a la derecha -->
                          <h6 id="tamanioDb_<?php echo $faena['id_faena']; ?>" class="mb-0">
                            <?php echo " Nombre db: " . $faena['metricas']['Nombre Db']; ?>
                            <?php echo " / " . $tamanioDbValue . $unidadDb; ?>
                          </h6>
                        </div>

                        <!-- Columna de la tabla -->
                        <div class="col-md-12">
                          <table id="tablaMayorId_<?php echo $faena['id_faena']; ?>" class="table table-bordered">
                            <thead>
                              <tr>
                                <th>Tabla con mayor ID</th>
                                <th>PK</th>
                                <th>Max. Val. Permitido</th>
                                <th>Falta para el límite</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td><?php echo htmlspecialchars($tableName); ?></td>
                                <td><?php echo number_format($idMaxTablaMasRegistrosValue); ?></td>
                                <td><?php echo number_format(2147483647); ?></td>
                                <td>
                                  <?php
                                  $faltaParaLimite = 2147483647 - $idMaxTablaMasRegistrosValue;
                                  echo number_format($faltaParaLimite);
                                  ?>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <!-------------------------Contenido del collapse grafana------------------------->

                      <div class="collapse" id="extraDBContent_<?php echo $faena['id_faena']; ?>">
                        <div class="row d-flex justify-content-center mb-4">
                          <h5>Tablas más pesadas en la base de datos</h5>
                        </div>
                        <div class="row">
                          <div class="col-md-12">
                            <table id="topTablasMasPesadas_<?php echo $faena['id_faena']; ?>" class="table  table-bordered">
                              <thead>
                                <tr>
                                  <th>Esquema</th>
                                  <th>Tabla</th>
                                  <th>Tamaño</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php if (!empty($topTablasMasPesadas)): ?>
                                  <?php foreach ($topTablasMasPesadas as $tabla): ?>
                                    <tr>
                                      <td><?php echo htmlspecialchars($tabla['schema']); ?></td>
                                      <td><?php echo htmlspecialchars($tabla['table']); ?></td>
                                      <td><?php echo htmlspecialchars($tabla['size']); ?></td>
                                    </tr>
                                  <?php endforeach; ?>
                                <?php endif; ?>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>




                    </div>

                    <div class="card-footer p-2"> <!-- reduce el padding del footer -->
                      <button class="btn btn-light w-100 py-2" type="button"
                        data-toggle="collapse"
                        data-target="#extraDBContent_<?php echo $faena['id_faena']; ?>"
                        aria-expanded="false"
                        aria-controls="extraDBContent_<?php echo $faena['id_faena']; ?>">
                        Ver detalle
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- Fin del card widget -->
    </div>

    <!-- Modal para Particiones -->
    <div class="modal fade" id="particionesModal_<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="particionesModalLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-custom" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="particionesModalLabel_<?php echo $faena['id_faena']; ?>">Particiones Disco Duro - <?php echo $faena['nombre_servidor']; ?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>% Uso</th>
                  <th>Espacio</th>
                  <th>Punto de Montaje</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($lineasParticiones)): ?>
                  <?php foreach ($lineasParticiones as $linea):
                    $part = preg_split('/\s+/', trim($linea));
                    if (count($part) < 3) continue;
                  ?>
                    <tr>
                      <td><?php echo htmlspecialchars($part[0]); ?></td>
                      <td><?php echo htmlspecialchars($part[1]); ?></td>
                      <td><?php echo htmlspecialchars($part[2]); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>


    <!-- Modal para top c Lectura -->
    <div class="modal fade" id="topCLecturaModal_<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="topCLecturaModalLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-custom" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="topCLecturaModalLabel_<?php echo $faena['id_faena']; ?>">top c Lectura - <?php echo $faena['nombre_servidor']; ?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>PID</th>
                  <th>USER</th>
                  <th>PR</th>
                  <th>NI</th>
                  <th>VIRT</th>
                  <th>RES</th>
                  <th>SHR</th>
                  <th>S</th>
                  <th>%CPU</th>
                  <th>%MEM</th>
                  <th>TIME+</th>
                  <th>COMMAND</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($faena['lineasTopCLectura'])): ?>
                  <?php
                  // Recorremos cada línea; solo mostramos aquellas cuyo primer campo es numérico (los procesos)
                  foreach ($faena['lineasTopCLectura'] as $lineaTop):
                    $parts = preg_split('/\s+/', trim($lineaTop));
                    if (!empty($parts) && is_numeric($parts[0])): ?>
                      <tr>
                        <?php foreach ($parts as $part): ?>
                          <td><?php echo htmlspecialchars($part); ?></td>
                        <?php endforeach; ?>
                      </tr>
                  <?php
                    endif;
                  endforeach;
                  ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>




    <!-- Modal para Equipos desconectados -->
    <div class="modal fade" id="miModal_<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="miModalLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-custom" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="miModalLabel_<?php echo $faena['id_faena']; ?>">Detalle de Equipos desconectados</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table id="equiposTable_<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>ultima_conexion</th>
                  <th>equipo</th>
                  <th>estado</th>
                  <th>desconectado [m]</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $totalLineas = count($lineasEquipos);
                for ($i = 2; $i < $totalLineas; $i++) {
                  $linea = trim($lineasEquipos[$i]);
                  if (empty($linea)) continue;
                  $columnas = array_map('trim', explode("|", $linea));
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($columnas[0]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[1]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[2]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[3]) . "</td>";
                  echo "</tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para Equipos conectados -->
    <div class="modal fade" id="eConectados<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="conectadosLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-custom" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="conectadoslLabel_<?php echo $faena['id_faena']; ?>">Detalle de Equipos conectados</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table id="equiposConTable_<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Última_conexion</th>
                  <th>Equipo</th>
                  <th>Estado</th>
                  <th>Conectado [m]</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $totalLineas = count($lineasEquiposCon);
                for ($i = 2; $i < $totalLineas; $i++) {
                  $linea = trim($lineasEquiposCon[$i]);
                  if (empty($linea)) continue;
                  $columnas = array_map('trim', explode("|", $linea));
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($columnas[0]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[1]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[2]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[3]) . "</td>";
                  echo "</tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>


    <!-- Modal para Reporte Snapchots -->
    <div class="modal fade" id="snapchots<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="snapchotsLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-custom" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="snapchotsLabel_<?php echo $faena['id_faena']; ?>">Detalle de Reporte Snapchots</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table id="snapchotsTable_<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>ult_envío</th>
                  <th>equipo</th>
                  <th>enviado_hace [m]</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $totalSnap = count($lineasSnapchots);
                for ($i = 2; $i < $totalSnap; $i++) {
                  $linea = trim($lineasSnapchots[$i]);
                  if (empty($linea)) continue;
                  $cols = array_map('trim', explode("|", $linea));
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($cols[0]) . "</td>";
                  echo "<td>" . htmlspecialchars($cols[1]) . "</td>";
                  echo "<td>" . htmlspecialchars($cols[2]) . "</td>";
                  echo "</tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal para Eventos Generados -->
    <div class="modal fade" id="egenerados<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="egeneradosLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-custom" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="egeneradosLabel_<?php echo $faena['id_faena']; ?>">Detalle de Eventos Generados</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table id="egeneradosTable_<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>ult_envío</th>
                  <th>equipo</th>
                  <th>enviado_hace [m]</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $totalEventos = count($lineasEventos);
                for ($i = 2; $i < $totalEventos; $i++) {
                  $linea = trim($lineasEventos[$i]);
                  if (empty($linea)) continue;
                  $cols = array_map('trim', explode("|", $linea));
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($cols[0]) . "</td>";
                  echo "<td>" . htmlspecialchars($cols[1]) . "</td>";
                  echo "<td>" . htmlspecialchars($cols[2]) . "</td>";
                  echo "</tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal para Eventos sin clasificar turno actual -->
    <div class="modal fade" id="eturnoactual<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="eturnoActualLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-custom" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="eturnoActualLabel_<?php echo $faena['id_faena']; ?>">Detalle de los eventos sin clasificar del turno actual</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table id="eturnoActual<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Equipo</th>
                  <th>Creado</th>
                  <th>Llegó al servidor</th>
                  <th>demora revisión [m]</th>
                  <th>Tipo de evento</th>
                  <th>Estado</th>
                  <th>Id</th>
                  <th>Time Server</th>
                  <th>Turno</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $totalLineas = count($lineasEventosSinClasificarActual);
                for ($i = 2; $i < $totalLineas; $i++) {
                  $linea = trim($lineasEventosSinClasificarActual[$i]);
                  if (empty($linea)) continue;
                  $columnas = array_map('trim', explode("|", $linea));
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($columnas[0]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[1]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[2]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[3]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[4]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[5]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[6]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[7]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[8]) . "</td>";
                  echo "</tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal para Eventos sin clasificar turno anterior -->
    <div class="modal fade" id="eturnoanterior<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="eturnoAnteriorLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-custom" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="eturnoAnteriorLabel_<?php echo $faena['id_faena']; ?>">Detalle de los eventos sin clasificar del turno anterior</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table id="eturnoAnterior<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Equipo</th>
                  <th>Creado</th>
                  <th>Llegó al servidor</th>
                  <th>demora revisión [m]</th>
                  <th>Tipo de evento</th>
                  <th>Estado</th>
                  <th>Id</th>
                  <th>Time Server</th>
                  <th>Turno</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $totalLineas = count($lineasEventosSinClasificarAnterior);
                for ($i = 2; $i < $totalLineas; $i++) {
                  $linea = trim($lineasEventosSinClasificarAnterior[$i]);
                  if (empty($linea)) continue;
                  $columnas = array_map('trim', explode("|", $linea));
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($columnas[0]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[1]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[2]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[3]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[4]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[5]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[6]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[7]) . "</td>";
                  echo "<td>" . htmlspecialchars($columnas[8]) . "</td>";
                  echo "</tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>





    <!-- Inicialización de DataTables para cada modal, esto es solo necesario para tablas que requieran funcionalidades como de ordenamiento, paginacion, etc -->
    <script>
      $(document).ready(function() {
        $('#equiposTable_<?php echo $faena['id_faena']; ?>').DataTable({
          "order": [
            [3, "desc"]
          ],
          "columnDefs": [{
            "type": "num",
            "targets": 3,
            "render": function(data, type, row, meta) {
              // Supongamos que data viene en minutos y deseas formatearlo con formatTimeMax
              if (type === 'display') {
                return formatTimeMax(data);
              }
              return data;
            }
          }],
          "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
          }
        });
        $('#equiposConTable_<?php echo $faena['id_faena']; ?>').DataTable({
          "order": [
            [3, "desc"]
          ],
          "columnDefs": [{
            "type": "num",
            "targets": 3,
            "render": function(data, type, row, meta) {
              // Supongamos que data viene en minutos y deseas formatearlo con formatTimeMax
              if (type === 'display') {
                return formatTimeMax(data);
              }
              return data;
            }
          }],
          "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
          }
        });
        $('#snapchotsTable_<?php echo $faena['id_faena']; ?>').DataTable({
          "order": [
            [2, "desc"]
          ],
          "columnDefs": [{
            "type": "num",
            "targets": 2,
            "render": function(data, type, row, meta) {
              // Supongamos que data viene en minutos y deseas formatearlo con formatTimeMax
              if (type === 'display') {
                return formatTimeMax(data);
              }
              return data;
            }
          }],
          "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
          }
        });
        $('#egeneradosTable_<?php echo $faena['id_faena']; ?>').DataTable({
          "order": [
            [2, "desc"]
          ],
          "columnDefs": [{
            "type": "num",
            "targets": 2,
            "render": function(data, type, row, meta) {
              // Supongamos que data viene en minutos y deseas formatearlo con formatTimeMax
              if (type === 'display') {
                return formatTimeMax(data);
              }
              return data;
            }
          }],
          "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
          }
        });
        $('#eturnoActual<?php echo $faena['id_faena']; ?>').DataTable({
          "order": [
            [1, "desc"]
          ],
          "columnDefs": [{
            "type": "date",
            "targets": 3,
            "render": function(data, type, row, meta) {
              // Supongamos que data viene en minutos y deseas formatearlo con formatTimeMax
              if (type === 'display') {
                return formatTimeMax(data);
              }
              return data;
            }
          }],
          "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
          }
        });
        $('#eturnoAnterior<?php echo $faena['id_faena']; ?>').DataTable({
          "order": [
            [1, "desc"]
          ],
          "columnDefs": [{
            "type": "date",
            "targets": 3,
            "render": function(data, type, row, meta) {
              // Supongamos que data viene en minutos y deseas formatearlo con formatTimeMax
              if (type === 'display') {
                return formatTimeMax(data);
              }
              return data;
            }
          }],
          "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
          }
        });

        // Aquí agregamos la inicialización para quitar el foco de los modales de particiones
        $('[id^="particionesModal_"]').on('hidden.bs.modal', function() {
          $(this).find(':focus').blur();
        });

      });
    </script>

    <!-- Inicialización de Chart.js para la gráfica de Load Average -->
    <script>
      function initLoadAverageChart(canvasID, loadData) {
        var ctxElem = document.getElementById(canvasID);
        if (!ctxElem) return;
        var ctx = ctxElem.getContext("2d");

        var labels = ["7''", "6''", "5''", "4''", "3''", "2''", "1''"];
        var dataConfig = {
          labels: labels,
          datasets: [{
            label: "Load Average",
            data: loadData,
            backgroundColor: "#09DA06",
            borderColor: "#09DA06",
            borderWidth: 2,
            fill: true,
            pointRadius: 0
          }]
        };

        var chart = new Chart(ctx, {
          type: "line",
          data: dataConfig,
          options: {
            responsive: true,
            scales: {
              xAxes: [{
                gridLines: {
                  display: false
                },
                ticks: {
                  fontSize: 12, // reducido
                  fontColor: "#000000"
                }
              }],
              yAxes: [{
                gridLines: {
                  display: false
                },
                ticks: {
                  min: 0,
                  max: 5,
                  stepSize: 1,
                  padding: 5,
                  fontSize: 12, // reducido
                  fontColor: "#000000"
                }
              }]
            },
            legend: {
              display: false
            },
            animation: {
              duration: 0
            }
          }
        });

        window.loadCharts = window.loadCharts || {};
        window.loadCharts["<?php echo $faena['id_faena']; ?>"] = chart;
      }


      var loadData = [<?php echo implode(',', $loadAverageArray); ?>];
      initLoadAverageChart("<?php echo $canvasID; ?>", loadData);
    </script>
  <?php endforeach; ?>
</div>

<!-- Inicialización de jQuery Knob para cada input de dona -->
<script>
  $(document).ready(function() {
    $('.GraficoVerde').knob({
      readOnly: true,
      rotation: 'anticlockwise',
      thickness: '.3',
      width: 90,
      height: 90,
      fgColor: '#09DA06',
      draw: function() {
        $(this.i).val($(this.i).val() + '%');
      }
    });
    $('.GraficoAmarillo').knob({
      readOnly: true,
      rotation: 'anticlockwise',
      thickness: '.3',
      width: 90,
      height: 90,
      fgColor: '#ffc107',
      draw: function() {
        $(this.i).val($(this.i).val() + '%');
      }
    });
    $('.GraficoRojo').knob({
      readOnly: true,
      rotation: 'anticlockwise',
      thickness: '.3',
      width: 90,
      height: 90,
      fgColor: 'red',
      draw: function() {
        $(this.i).val($(this.i).val() + '%');
      }
    });
  });
</script>

<!-- WebSocket para actualización en tiempo real -->
<script>
  // Cambia la URL del WebSocket según corresponda, por ejemplo:
  var websocketStatusElem = document.getElementById("websocketStatus");

  function connectWebSocket() {
    var ws = new WebSocket("ws://10.169.140.99:9505");

    ws.onopen = function() {
      console.log("Conexión WebSocket establecida");
      hideWebSocketMessage(); // Oculta el mensaje de reconexión
    };

    ws.onerror = function(error) {
      console.error("WebSocket error:", error);
    };

    var maxAllowedTimeDifference = 5 * 60 * 1000; // 2 minutos en milisegundos
    var outdatedServers = []; // Lista de servidores con datos desactualizados

    ws.onmessage = function(event) {
      var data = JSON.parse(event.data);
      console.log("Datos recibidos:", data);

      outdatedServers = []; // Reinicia la lista de servidores desactualizados

      data.forEach(function(item) {
        // Actualiza el nombre de la faena
        var nameElem = document.getElementById("faenaName_" + item.id_faena);
        if (nameElem) {
          nameElem.innerText = item.nombre_faena;
        }

        // Actualiza la hora de actualización (si se envía la propiedad 'fecha_actualizacion')
        var updateTimeElem = document.getElementById("updateTime_" + item.id_faena);
        if (updateTimeElem && item.fecha_actualizacion) {
          updateTimeElem.innerText = item.fecha_actualizacion;
        }

        // Actualización en tiempo real para la métrica Tamanio Db
        var tamanioDbElem = document.getElementById("tamanioDb_" + item.id_faena);
        if (tamanioDbElem && item.tamanioDbValue && item.unidadDb) {
          var nombreDb = "";
          if (item.metricas && item.metricas["Nombre Db"]) {
            nombreDb = "Nombre db: " + item.metricas["Nombre Db"];
          }
          tamanioDbElem.innerHTML = " " + nombreDb + " / " + item.tamanioDbValue + " " + item.unidadDb;
        }

        // Actualiza el nombre del servidor y la IP
        var nombreServidorElem = document.getElementById("nombreServidor_" + item.id_faena);
        if (nombreServidorElem && item.metricas && item.metricas["Nombre Servidor"]) {
          nombreServidorElem.innerText = item.metricas["Nombre Servidor"];
        }

        var ipServidorElem = document.getElementById("ipServidor_" + item.id_faena);
        if (ipServidorElem && item.metricas && item.metricas["Ip Servidor"]) {
          ipServidorElem.innerText = item.metricas["Ip Servidor"];
        }

        // Actualiza el knob del Disco Duro y el texto de uso
        var diskKnob = $("#diskKnob_" + item.id_faena);
        if (diskKnob.length) {
          // Calculamos el color basado en el porcentaje
          var graficoDonutColor = "GraficoVerde"; // Verde por defecto
          if (item.diskPorcentaje > 90) {
            graficoDonutColor = "GraficoRojo"; // Rojo
          } else if (item.diskPorcentaje > 75) {
            graficoDonutColor = "GraficoAmarillo"; // Amarillo
          }

          // Asignamos el color al knob
          var fgColor = "";
          if (graficoDonutColor === "GraficoVerde") {
            fgColor = "#09DA06"; // Verde
          } else if (graficoDonutColor === "GraficoAmarillo") {
            fgColor = "#ffc107"; // Amarillo
          } else if (graficoDonutColor === "GraficoRojo") {
            fgColor = "red"; // Rojo
          }

          // Actualiza el color y el valor del knob
          diskKnob.trigger("configure", {
            fgColor: fgColor
          });
          diskKnob.val(item.diskPorcentaje).trigger("change");
          // Cambia el color del texto en el centro del gráfico
          diskKnob.css("color", fgColor);
        }

        var diskUsage = document.getElementById("diskUsage_" + item.id_faena);
        if (diskUsage) {
          diskUsage.innerText = item.discoUsado + "/" + item.discoTotal;
        }

        // Actualiza la barra de progreso del Disco Duro, usamos item.diskporcentaje ya que este dato que envia el websocket es el mismo que contiene $mivalue que es el que muestra inicialmente la barra de progreso
        var progressBarDisk = document.querySelector("#diskProgressBar_" + item.id_faena);
        if (progressBarDisk && item.diskPorcentaje !== undefined) {
          progressBarDisk.style.width = item.diskPorcentaje + "%"; // Actualiza el ancho de la barra
          progressBarDisk.setAttribute("aria-valuenow", item.diskPorcentaje); // Actualiza el atributo aria-valuenow
        }

        // Actualiza el knob de Memoria RAM y el texto de uso
        var ramKnob = $("#ramKnob_" + item.id_faena);
        if (ramKnob.length) {
          // Calculamos el color basado en el porcentaje
          var graficoDonutColor = "GraficoVerde"; // Verde por defecto
          if (item.ramPorcentaje > 90) {
            graficoDonutColor = "GraficoRojo"; // Rojo
          } else if (item.ramPorcentaje > 75) {
            graficoDonutColor = "GraficoAmarillo"; // Amarillo
          }

          // Asignamos el color al knob
          var fgColor = "";
          if (graficoDonutColor === "GraficoVerde") {
            fgColor = "#09DA06"; // Verde
          } else if (graficoDonutColor === "GraficoAmarillo") {
            fgColor = "#ffc107"; // Amarillo
          } else if (graficoDonutColor === "GraficoRojo") {
            fgColor = "red"; // Rojo
          }

          // Actualiza el color y el valor del knob
          ramKnob.trigger("configure", {
            fgColor: fgColor
          });
          ramKnob.val(item.ramPorcentaje).trigger("change");
        }

        // Actualiza la barra de progreso de la Memoria RAM
        var progressBarRam = document.querySelector("#ramProgressBar_" + item.id_faena);
        if (progressBarRam && item.ramPorcentaje !== undefined) {
          progressBarRam.style.width = item.ramPorcentaje + "%"; // Actualiza el ancho de la barra
          progressBarRam.setAttribute("aria-valuenow", item.ramPorcentaje); // Actualiza el atributo aria-valuenow
        }

        // Actualiza el texto de uso de RAM
        var ramUsage = document.getElementById("ramUsage_" + item.id_faena);
        if (ramUsage) {
          ramUsage.innerText = item.ramUsedStr + "/" + item.ramTotalStr;
        }

        // Calcula el promedio del Load Average
        var average = item.loadAverageArray.reduce((sum, value) => sum + value, 0) / item.loadAverageArray.length;

        // Determina el color según el promedio
        var color = average > 4.5 ? "red" : average > 3 ? "orange" : "#09DA06"; // Rojo, Naranja o Verde

        // Actualiza los datos y el color de la gráfica
        if (window.loadCharts && window.loadCharts[item.id_faena]) {
          var chart = window.loadCharts[item.id_faena];
          chart.data.datasets[0].data = item.loadAverageArray;
          chart.data.datasets[0].backgroundColor = color;
          chart.data.datasets[0].borderColor = color;
          chart.update();
        }

        // Actualiza el promedio de Load Average en el texto
        var cpuLoadElem = document.getElementById("cpuLoadAverage_" + item.id_faena);
        if (cpuLoadElem && item.loadAverageArray && item.loadAverageArray.length > 0) {
          var sum = item.loadAverageArray.reduce(function(a, b) {
            return a + b;
          }, 0);
          var avg = (sum / item.loadAverageArray.length).toFixed(2);
          cpuLoadElem.innerText = "CPU - Load Average: " + avg;
        }

        // Servicio de Grafana
        var grafanaElem = document.getElementById("servicioGrafana_" + item.id_faena);
        if (grafanaElem) {
          if (item.metricas && item.metricas["Servicio de Grafana"]) {
            var rawGrafana = item.metricas["Servicio de Grafana"];
            var isActive = /^Active:\s+active/i.test(rawGrafana.trim());
            grafanaElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
          } else {
            // Si no hay datos, coloca la small-box en rojo
            grafanaElem.className = "small-box small-box-2 bg-danger h-100";
          }
        }

        // Servicio de PostgreSQL
        var postgresElem = document.getElementById("servicioPostgreSQL_" + item.id_faena);
        if (postgresElem) {
          if (item.metricas && item.metricas["Servicio de PostgreSQL"]) {
            var rawPostgres = item.metricas["Servicio de PostgreSQL"];
            var isActive = /^Active:\s+active/i.test(rawPostgres.trim());
            postgresElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
          } else {
            postgresElem.className = "small-box small-box-2 bg-danger h-100";
          }
        }

        // Servicio de Rsyslog
        var rsyslogElem = document.getElementById("servicioRsyslog_" + item.id_faena);
        if (rsyslogElem) {
          if (item.metricas && item.metricas["Servicio Rsyslog"]) {
            var rawRsyslog = item.metricas["Servicio Rsyslog"];
            var isActive = /^Active:\s+active/i.test(rawRsyslog.trim());
            rsyslogElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
          } else {
            rsyslogElem.className = "small-box small-box-2 bg-danger h-100";
          }
        }

        // Servicio de SSH
        var sshElem = document.getElementById("servicioSSH_" + item.id_faena);
        if (sshElem) {
          if (item.metricas && item.metricas["Servicio SSH"]) {
            var rawSSH = item.metricas["Servicio SSH"];
            var isActive = /^Active:\s+active/i.test(rawSSH.trim());
            sshElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
          } else {
            sshElem.className = "small-box small-box-2 bg-danger h-100";
          }
        }

        // Servicio de gvuploads
        var gvuploadElem = document.getElementById("servicioGvupload_" + item.id_faena);
        if (gvuploadElem) {
          if (item.metricas && item.metricas["Servicio gvuploads"]) {
            var rawGvupload = item.metricas["Servicio gvuploads"];
            var isActive = /^Active:\s+active/i.test(rawGvupload.trim());
            gvuploadElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
          } else {
            gvuploadElem.className = "small-box small-box-2 bg-danger h-100";
          }
        }

        // Servicio de Mosquitto
        var mosquittoElem = document.getElementById("servicioMosquitto_" + item.id_faena);
        if (mosquittoElem) {
          if (item.metricas && item.metricas["Servicio Mosquitto"]) {
            var rawMosquitto = item.metricas["Servicio Mosquitto"];
            var isActive = /^Active:\s+active/i.test(rawMosquitto.trim());
            mosquittoElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
          } else {
            mosquittoElem.className = "small-box small-box-2 bg-danger h-100";
          }
        }

        // Servicio de NTP
        var ntpElem = document.getElementById("servicioNTP_" + item.id_faena);
        if (ntpElem) {
          if (item.metricas && item.metricas["Servicio NTP"]) {
            var rawNTP = item.metricas["Servicio NTP"];
            var isActive = /^Active:\s+active/i.test(rawNTP.trim());
            ntpElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
          } else {
            ntpElem.className = "small-box small-box-2 bg-danger h-100";
          }
        }

        // Servicio de Open VM Tools
        var vmToolsElem = document.getElementById("servicioVMTools_" + item.id_faena);
        if (vmToolsElem) {
          if (item.metricas && item.metricas["Open VM Tools"]) {
            var rawVMTools = item.metricas["Open VM Tools"];
            var isActive = /^Active:\s+active/i.test(rawVMTools.trim());
            vmToolsElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
          } else {
            vmToolsElem.className = "small-box small-box-2 bg-danger h-100";
          }
        }

        // Servicio de VPN
        var vpnElem = document.getElementById("servicioVPN_" + item.id_faena);
        if (vpnElem) {
          if (item.metricas && item.metricas["Servicio VPN"]) {
            var rawVPN = item.metricas["Servicio VPN"];
            var isActive = /^Active:\s+active/i.test(rawVPN.trim());
            vpnElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
          } else {
            vpnElem.className = "small-box small-box-2 bg-danger h-100";
          }
        }

        // Actualiza los valores de Tclas y TturnA

        // Tiempo máximo turno actual (Tclas Max)
        var tclasElem = document.getElementById("tclasValue_" + item.id_faena);
        if (tclasElem) {
          let formatted = formatTimeMax(item.tclasValue);
          updateTextIfChanged(tclasElem, formatted);
        }
        // Tiempo mínimo turno actual (Tclas Min)
        var tclasMinElem = document.getElementById("tclasMinValue_" + item.id_faena);
        if (tclasMinElem) {
          let formatted = formatTimeMin(item.tclasMinValue);
          updateTextIfChanged(tclasMinElem, formatted);
        }
        // Tiempo máximo turno anterior (TturnA Max)
        var tturnAMaxElem = document.getElementById("tturnAMaxValue_" + item.id_faena);
        if (tturnAMaxElem) {
          let formatted = formatTimeMax(item.tturnAMaxValue);
          updateTextIfChanged(tturnAMaxElem, formatted);
        }
        // Tiempo mínimo turno anterior (TturnA Min)
        var tturnAMinElem = document.getElementById("tturnAMinValue_" + item.id_faena);
        if (tturnAMinElem) {
          let formatted = formatTimeMin(item.tturnAMinValue);
          updateTextIfChanged(tturnAMinElem, formatted);
        }

        // Actualiza los contadores en la vista principal 
        var numEquiposElem = document.getElementById("numEquipos_" + item.id_faena);
        if (numEquiposElem) {
          numEquiposElem.innerText = item.numEquipos;
        }

        var numEquiposConElem = document.getElementById("countConectados_" + item.id_faena);
        if (numEquiposConElem) {
          numEquiposConElem.innerText = item.numEquiposCon;
        }

        var countSnapchotsElem = document.getElementById("countSnapchots_" + item.id_faena);
        if (countSnapchotsElem) {
          countSnapchotsElem.innerText = item.countSnapchots;
        }
        var countEventosElem = document.getElementById("countEventos_" + item.id_faena);
        if (countEventosElem) {
          countEventosElem.innerText = item.countEventos;
        }
        var countEventosSinClasificarActualElem = document.getElementById("countEventosSinClasificarActual_" + item.id_faena);
        if (countEventosSinClasificarActualElem) {
          countEventosSinClasificarActualElem.innerText = item.countEventosSinClasificarActual;
        }
        var countEventosSinClasificarAnteriorElem = document.getElementById("countEventosSinClasificarAnterior_" + item.id_faena);
        if (countEventosSinClasificarAnteriorElem) {
          countEventosSinClasificarAnteriorElem.innerText = item.countEventosSinClasificarAnterior;
        }

        // --- Actualización de datos en modales usando la API de DataTables ---
        // --- Actualización en tiempo real del modal de Particiones Disco Duro ---
        if (item.lineasParticiones && Array.isArray(item.lineasParticiones)) {
          // Selecciona el <tbody> de la tabla dentro del modal de particiones usando el id del modal.
          var tbodyParticiones = document.querySelector("#particionesModal_" + item.id_faena + " table tbody");
          if (tbodyParticiones) {
            var html = "";
            // Recorre cada línea de particiones para construir las filas de la tabla.
            item.lineasParticiones.forEach(function(linea) {
              // Separa la línea usando espacios en blanco (esto maneja múltiples espacios)
              var parts = linea.trim().split(/\s+/);
              if (parts.length >= 3) {
                // Si el punto de montaje tiene espacios, se unen los elementos desde el índice 2.
                var mount = parts.slice(2).join(" ");
                html += "<tr><td>" + parts[0] + "</td><td>" + parts[1] + "</td><td>" + mount + "</td></tr>";
              }
            });
            tbodyParticiones.innerHTML = html;
          }
        }

        // --- Actualización en tiempo real del modal de top c Lectura ---
        if (item.lineasTopCLectura && Array.isArray(item.lineasTopCLectura)) {
          // Selecciona el <tbody> de la tabla dentro del modal "top c Lectura"
          var tbodyTopCLectura = document.querySelector("#topCLecturaModal_" + item.id_faena + " table tbody");
          if (tbodyTopCLectura) {
            var html = "";
            item.lineasTopCLectura.forEach(function(linea) {
              var parts = linea.trim().split(/\s+/);
              // Solo muestra la línea si el primer campo es numérico (registro de proceso)
              if (parts.length >= 12 && !isNaN(parts[0])) {
                html += "<tr>";
                parts.forEach(function(part) {
                  html += "<td>" + part + "</td>";
                });
                html += "</tr>";
              }
            });
            tbodyTopCLectura.innerHTML = html;
          }
        }

        // Actualiza la tabla de "Tabla con mayor ID"
        var tablaMayorIdElem = document.querySelector("#tablaMayorId_" + item.id_faena + " tbody");
        if (tablaMayorIdElem && item.tableName && !isNaN(item.idMaxTablaMasRegistrosValue)) {
          var maxValPermitido = 2147483647; // Valor constante
          var idMaxValue = parseInt(item.idMaxTablaMasRegistrosValue, 10) || 0;
          var faltaParaLimite = maxValPermitido - idMaxValue;

          // Construimos la nueva fila
          var html = `
            <tr>
              <td>${item.tableName}</td>
              <td>${new Intl.NumberFormat().format(idMaxValue)}</td>
              <td>${new Intl.NumberFormat().format(maxValPermitido)}</td>
              <td>${new Intl.NumberFormat().format(faltaParaLimite)}</td>
            </tr>
          `;

          // Actualizamos el contenido del tbody
          tablaMayorIdElem.innerHTML = html;
        }

        // Actualiza la tabla de "Tablas más pesadas"
        var tablaMasPesadasElem = document.querySelector("#topTablasMasPesadas_" + item.id_faena + " tbody");
        if (tablaMasPesadasElem && item.topTablasMasPesadas && Array.isArray(item.topTablasMasPesadas)) {
          var html = "";

          // Construimos las filas de la tabla
          item.topTablasMasPesadas.forEach(function(tabla) {
            html += `
              <tr>
                <td>${tabla.schema}</td>
                <td>${tabla.table}</td>
                <td>${tabla.size}</td>
              </tr>
            `;
          });

          // Actualizamos el contenido del tbody
          tablaMasPesadasElem.innerHTML = html;
        }

        // 1) Actualiza la tabla de Equipos desconectados
        if (item.equipos && Array.isArray(item.equipos)) {
          var tableEquipos = $('#equiposTable_' + item.id_faena).DataTable();
          var newRowsEquipos = [];
          item.equipos.forEach(function(row) {
            newRowsEquipos.push([
              row.ultima_conexion,
              row.equipo,
              row.estado,
              row.desconectado
            ]);
          });
          tableEquipos.clear();
          tableEquipos.rows.add(newRowsEquipos);
          tableEquipos.draw();
        }

        // Actualiza la tabla de equipos conectados
        if (item.equiposCon && Array.isArray(item.equiposCon)) {
          var tableEquiposCon = $('#equiposConTable_' + item.id_faena).DataTable();
          var newRowsEquiposCon = [];
          item.equiposCon.forEach(function(row) {
            newRowsEquiposCon.push([
              row.ultima_conexion,
              row.equipo,
              row.estado,
              row.conectado
            ]);
          });
          tableEquiposCon.clear();
          tableEquiposCon.rows.add(newRowsEquiposCon);
          tableEquiposCon.draw();
        }

        // Actualiza la tabla de Reporte Snapchots
        if (item.snapchots && Array.isArray(item.snapchots)) {
          var tableSnap = $('#snapchotsTable_' + item.id_faena).DataTable();
          var newRowsSnap = [];
          item.snapchots.forEach(function(row) {
            newRowsSnap.push([
              row.ult_envio,
              row.equipo,
              row.enviado_hace
            ]);
          });
          tableSnap.clear();
          tableSnap.rows.add(newRowsSnap);
          tableSnap.draw();
        }

        // Actualiza la tabla de Eventos Generados
        if (item.eventosGenerados && Array.isArray(item.eventosGenerados)) {
          var tableEgen = $('#egeneradosTable_' + item.id_faena).DataTable();
          var newRowsEgen = [];
          item.eventosGenerados.forEach(function(row) {
            newRowsEgen.push([
              row.ult_envio,
              row.equipo,
              row.enviado_hace
            ]);
          });
          tableEgen.clear();
          tableEgen.rows.add(newRowsEgen);
          tableEgen.draw();
        }

        // Actualiza la tabla de Eventos sin clasificar del turno actual
        if (item.eventosSinClasificarActual && Array.isArray(item.eventosSinClasificarActual)) {
          var tableTurnoActual = $('#eturnoActual' + item.id_faena).DataTable();
          var newRowsTurnoActual = [];
          item.eventosSinClasificarActual.forEach(function(row) {
            newRowsTurnoActual.push([
              row.equipo,
              row.creado,
              row.llego_al_servidor,
              row.demora_revision,
              row.tipo_evento,
              row.estado,
              row.id,
              row.time_server,
              row.turno
            ]);
          });
          tableTurnoActual.clear();
          tableTurnoActual.rows.add(newRowsTurnoActual);
          tableTurnoActual.draw();
        }

        // Actualiza la tabla de Eventos sin clasificar del turno anterior
        if (item.eventosSinClasificarAnterior && Array.isArray(item.eventosSinClasificarAnterior)) {
          var tableTurnoAnterior = $('#eturnoAnterior' + item.id_faena).DataTable();
          var newRowsTurnoAnterior = [];
          item.eventosSinClasificarAnterior.forEach(function(row) {
            newRowsTurnoAnterior.push([
              row.equipo,
              row.creado,
              row.llego_al_servidor,
              row.demora_revision,
              row.tipo_evento,
              row.estado,
              row.id,
              row.time_server,
              row.turno
            ]);
          });
          tableTurnoAnterior.clear();
          tableTurnoAnterior.rows.add(newRowsTurnoAnterior);
          tableTurnoAnterior.draw();
        }

        // Verifica si los datos del servidor están actualizados
        if (item.metricas && item.metricas["Fecha Servidor"]) {
          var serverTimestamp = new Date(item.metricas["Fecha Servidor"]).getTime(); // Convierte a milisegundos
          var currentTimestamp = Date.now(); // Hora actual en milisegundos

          // Calcular la diferencia de tiempo
          var timeDifference = currentTimestamp - serverTimestamp;

          if (timeDifference > maxAllowedTimeDifference) {
            console.warn("Los datos de la faena " + item.nombre_faena + " no son actuales.");
            outdatedServers.push({
              faena: item.nombre_faena,
              servidor: item.metricas["Nombre Servidor"] || "Servidor desconocido",
              diferencia: Math.round(timeDifference / 1000) // Diferencia en segundos
            });
          }
        }
      });

      // Mostrar el mensaje si hay servidores desactualizados
      if (outdatedServers.length > 0) {
        showOutdatedMessage(outdatedServers);
      } else {
        autoHideOutdatedMessage(); // Oculta la alerta si los datos vuelven a estar actualizados
      }
    };

    // Función para mostrar mensajes en el elemento websocketStatus
    function showWebSocketMessage(message, backgroundColor = "red", textColor = "white") {
      const websocketStatusElem = document.getElementById("websocketStatus");
      if (websocketStatusElem) {
        websocketStatusElem.innerHTML = message;
        websocketStatusElem.style.backgroundColor = backgroundColor;
        websocketStatusElem.style.color = textColor;
        websocketStatusElem.style.fontFamily = "Arial, sans-serif"; // Tipo de letra blanca
        websocketStatusElem.style.fontWeight = "bold"; // Negrita
        websocketStatusElem.style.display = "block";
      }
    }

    // Función para ocultar el mensaje
    function hideWebSocketMessage() {
      const websocketStatusElem = document.getElementById("websocketStatus");
      if (websocketStatusElem) {
        websocketStatusElem.style.display = "none";
      }
    }

    // Función para ocultar el mensaje de datos desactualizados si los datos vuelven a estar actualizados
    function autoHideOutdatedMessage() {
      const websocketStatusElem = document.getElementById("websocketStatus");
      if (websocketStatusElem && websocketStatusElem.style.display === "block") {
        websocketStatusElem.style.display = "none";
      }
    }

    // Actualiza el código de servidores desactualizados
    function showOutdatedMessage(servers) {
      let messageContent = "<strong>Los siguientes servidores tienen datos desactualizados:</strong><ul>";
      servers.forEach(function(server) {
        messageContent += `<li>Faena: ${server.faena}, Servidor: ${server.servidor} (hace ${server.diferencia} segundos)</li>`;
      });
      messageContent += "</ul><button type='button' class='btn btn-light' id='minimizeButton' style='margin-top: 10px;'>Minimizar</button>";
      showWebSocketMessage(messageContent, "red", "white"); // Fondo rojo, texto blanco

      // Agrega el evento al botón para minimizar
      document.getElementById("minimizeButton").addEventListener("click", hideWebSocketMessage);
    }

    ws.onclose = function() {
      console.warn("Conexión WebSocket cerrada. Intentando reconectar...");
      showWebSocketMessage("Conexión perdida con el websocket. Reintentando conectar...");
      setTimeout(connectWebSocket, 5000); // Reintenta la conexión
    };
  }
  // Cerrar la conexión de manera ordenada al salir de la página
  window.addEventListener("beforeunload", function() {
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.close();
    }
  });


  // Inicia la conexión al cargar la página
  connectWebSocket();
</script>

<!-- Funciones de formateo, inicialización y actualización en tiempo real -->

<script>
  // Función para formatear tiempos máximos (valor en minutos)
  function formatTimeMax(valueInMinutes) {
    let val = parseFloat(valueInMinutes) || 0;
    // Si el tiempo es mayor o igual a 1440 minutos (24 horas)
    if (val >= 1440) {
      // Convertimos el tiempo a segundos para hacer una conversión precisa
      let totalSeconds = Math.round(val * 60);
      let days = Math.floor(totalSeconds / 86400); // 86400 segundos = 24 * 3600
      let remainder = totalSeconds % 86400;
      let hours = Math.floor(remainder / 3600);
      let mins = Math.floor((remainder % 3600) / 60);
      return days + ":" + (hours < 10 ? "0" + hours : hours) + ":" + (mins < 10 ? "0" + mins : mins) + " [d]";
    } else if (val >= 60) {
      // Si es entre 60 y 1439 minutos: formateamos en horas y minutos
      let hours = Math.floor(val / 60);
      let mins = Math.floor(val % 60);
      return hours + ":" + (mins < 10 ? "0" + mins : mins) + " [h]";
    } else {
      // Menos de 60 minutos: usamos minutos y segundos
      let wholeMins = Math.floor(val);
      let fraction = val - wholeMins;
      // Redondeamos la fracción a dos decimales para evitar fluctuaciones
      fraction = Math.round(fraction * 100) / 100;
      let secs = Math.round(fraction * 60);
      return wholeMins + ":" + (secs < 10 ? "0" + secs : secs) + " [m]";
    }
  }

  // Función para formatear tiempos mínimos (valor en segundos)
  function formatTimeMin(valueInSeconds) {
    let val = parseFloat(valueInSeconds) || 0;
    if (val >= 3600) { // 3600 segundos = 60 minutos
      let hours = Math.floor(val / 3600);
      let remainder = val % 3600;
      let mins = Math.floor(remainder / 60);
      let secs = Math.floor(remainder % 60);
      return hours + ":" + (mins < 10 ? "0" + mins : mins) + ":" + (secs < 10 ? "0" + secs : secs) + " [h]";
    } else if (val >= 60) {
      let mins = Math.floor(val / 60);
      let secs = Math.floor(val % 60);
      return mins + ":" + (secs < 10 ? "0" + secs : secs) + " [m]";
    } else {
      return Math.floor(val) + " [s]";
    }
  }

  // Función para evitar actualizar el DOM si el texto no cambia
  function updateTextIfChanged(elem, newText) {
    if (elem && elem.innerText !== newText) {
      elem.innerText = newText;
    }
  }

  // Paso 3: Inicializa la vista al cargar el DOM usando los valores raw
  document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll("[id^='tclasValue_']").forEach(function(elem) {
      let raw = elem.getAttribute("data-raw-tclas");
      if (raw !== null) {
        elem.innerText = formatTimeMax(raw);
      }
    });
    document.querySelectorAll("[id^='tclasMinValue_']").forEach(function(elem) {
      let raw = elem.getAttribute("data-raw-tclasmin");
      if (raw !== null) {
        elem.innerText = formatTimeMin(raw);
      }
    });
    document.querySelectorAll("[id^='tturnAMaxValue_']").forEach(function(elem) {
      let raw = elem.getAttribute("data-raw-tturnamax");
      if (raw !== null) {
        elem.innerText = formatTimeMax(raw);
      }
    });
    document.querySelectorAll("[id^='tturnAMinValue_']").forEach(function(elem) {
      let raw = elem.getAttribute("data-raw-tturnamin");
      if (raw !== null) {
        elem.innerText = formatTimeMin(raw);
      }
    });
  });

  // Inicia la conexión al cargar la página
  connectWebSocket();
</script>
<?php
// 1) Definimos la función de formateo en PHP
function formatTimeMaxPhp($valueInMinutes)
{
  if ($valueInMinutes >= 1440) { // 1440 minutos = 24 horas
    // Convertir el valor a segundos para obtener una precisión adecuada
    $totalSeconds = round($valueInMinutes * 60);
    $days    = floor($totalSeconds / 86400);       // 86400 segundos = 24 * 3600
    $remainder = $totalSeconds % 86400;
    $hours   = floor($remainder / 3600);
    $mins    = floor(($remainder % 3600) / 60);
    return sprintf("%d:%02d:%02d [d]", $days, $hours, $mins);
  } elseif ($valueInMinutes >= 60) {
    $horas = floor($valueInMinutes / 60);
    $mins  = $valueInMinutes % 60;
    return sprintf("%d:%02d [h]", $horas, $mins);
  } else {
    $wholeMins = floor($valueInMinutes);
    $fraction  = $valueInMinutes - $wholeMins;
    $secs      = round($fraction * 60);
    return sprintf("%d:%02d [m]", $wholeMins, $secs);
  }
}

function formatTimeMinPhp($valueInSeconds)
{
  $valueInSeconds = floatval($valueInSeconds);
  if ($valueInSeconds >= 3600) { // 3600 segundos = 60 minutos
    $hours = floor($valueInSeconds / 3600);
    $remainder = $valueInSeconds % 3600;
    $mins = floor($remainder / 60);
    $secs = $remainder % 60;
    return sprintf("%d:%02d:%02d [h]", $hours, $mins, $secs);
  } elseif ($valueInSeconds >= 60) {
    $mins = floor($valueInSeconds / 60);
    $secs = $valueInSeconds % 60;
    return sprintf("%d:%02d [m]", $mins, $secs);
  } else {
    return sprintf("%d [s]", floor($valueInSeconds));
  }
}

?>
<div id="websocketStatus" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: red; color: white; padding: 20px; border-radius: 10px; z-index: 1000; font-size: 2em; text-align: center; font-family: Arial, sans-serif; font-weight: bold;">
  <!-- El contenido del mensaje se llenará dinámicamente -->
</div>