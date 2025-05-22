<?php
require_once __DIR__ . '/../core/System.php';
require_once __DIR__ . '/../controllers/OasController.php';

date_default_timezone_set('America/Santiago');

$system = new systemClass();
$system->validarSesion();

$oasController = new OasController($system);
$faenas = $oasController->getFaenasConMetricas(true);
?>

<style>
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
</style>

<div class="row">
    <?php foreach ($faenas as $faena): ?>
        <?php
        // Procesa datos para Disco
        $lineaDiscoTotal = isset($faena['metricas']['Espacio Total Disco Duro']) ? trim($faena['metricas']['Espacio Total Disco Duro']) : "";
        $lineaDiscoUtilizado = isset($faena['metricas']['Espacio Utilizado Disco Duro']) ? trim($faena['metricas']['Espacio Utilizado Disco Duro']) : "";
        $discoTotalValue = floatval($lineaDiscoTotal);
        $discoUtilizadoValue = floatval($lineaDiscoUtilizado);
        $unidadTotal = strtoupper(substr($lineaDiscoTotal, -1));
        $unidadUtilizado = strtoupper(substr($lineaDiscoUtilizado, -1));
        if ($unidadTotal === 'M') {
            $discoTotalValue /= 1024;
        } elseif ($unidadTotal === 'T') {
            $discoTotalValue *= 1024;
        }
        if ($unidadUtilizado === 'M') {
            $discoUtilizadoValue /= 1024;
        } elseif ($unidadUtilizado === 'T') {
            $discoUtilizadoValue *= 1024;
        }
        $miValor = ($discoTotalValue > 0) ? (int)(($discoUtilizadoValue * 100) / $discoTotalValue) : 0;
        $graficoDonutColor = ($miValor <= 70) ? "GraficoVerde" : (($miValor <= 80) ? "GraficoAmarillo" : (($miValor <= 90) ? "GraficoAzul" : "GraficoRojo"));

        // Procesa datos para RAM
        $lineaRamUtilizada = isset($faena['metricas']['Memoria Ram Utilizada']) ? trim($faena['metricas']['Memoria Ram Utilizada']) : "";
        $lineaRamTotal = isset($faena['metricas']['Memoria Ram Total']) ? trim($faena['metricas']['Memoria Ram Total']) : "";
        $ramUsedStr = $lineaRamUtilizada;
        $ramTotalStr = $lineaRamTotal;
        $ramUtilizada = (stripos($lineaRamUtilizada, 'mi') !== false) ? floatval($lineaRamUtilizada) / 1024 : floatval($lineaRamUtilizada);
        $ramTotal = (stripos($lineaRamTotal, 'mi') !== false) ? floatval($lineaRamTotal) / 1024 : floatval($lineaRamTotal);
        $ramPorcentaje = ($ramTotal > 0) ? round(($ramUtilizada * 100) / $ramTotal) : 0;
        $graficoRamColor = ($ramPorcentaje <= 70) ? "GraficoVerde" : (($ramPorcentaje <= 80) ? "GraficoAmarillo" : (($ramPorcentaje <= 90) ? "GraficoAzul" : "GraficoRojo"));

        // Procesa Load Average
        $loadAverageString = isset($faena['metricas']['Load Average']) ? trim($faena['metricas']['Load Average']) : "";
        $loadAverageArray = ($loadAverageString !== "") ? array_map('floatval', array_map('trim', explode(',', $loadAverageString))) : [];
        $canvasID = "loadAverageChart_" . $faena['id_faena'];

        // Procesa Top c Lectura
        $topCLecturaRaw = isset($faena['metricas']['top c Lectura']) ? trim($faena['metricas']['top c Lectura']) : "";
        $lineasTopCLectura = (!empty($topCLecturaRaw)) ? array_values(array_filter(explode("\n", $topCLecturaRaw), 'strlen')) : [];

        // Procesa Particiones
        $particionesRaw = isset($faena['metricas']['Particiones Disco Duro']) ? trim($faena['metricas']['Particiones Disco Duro']) : "";
        $lineasParticiones = (!empty($particionesRaw)) ? array_values(array_filter(explode("\n", $particionesRaw), 'strlen')) : [];

        // Equipos desconectados
        $equiposDescRaw = isset($faena['metricas']['Equipos desconectados']) ? trim($faena['metricas']['Equipos desconectados']) : "";
        $lineasEquipos = array_values(array_filter(explode("\n", $equiposDescRaw), 'strlen'));
        if (count($lineasEquipos) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEquipos)))) {
            array_pop($lineasEquipos);
        }
        $numEquipos = (count($lineasEquipos) > 2) ? count($lineasEquipos) - 2 : 0;

        // Reporte Snapchots
        $snapchotsRaw = isset($faena['metricas']['Reporte Snapchots']) ? trim($faena['metricas']['Reporte Snapchots']) : "";
        $lineasSnapchots = array_values(array_filter(explode("\n", $snapchotsRaw), 'strlen'));
        if (count($lineasSnapchots) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasSnapchots)))) {
            array_pop($lineasSnapchots);
        }
        $countSnapchots = (count($lineasSnapchots) > 2) ? count($lineasSnapchots) - 2 : 0;

        // Eventos Generados
        $eventosGeneradosRaw = isset($faena['metricas']['Eventos Generados']) ? trim($faena['metricas']['Eventos Generados']) : "";
        $lineasEventos = array_values(array_filter(explode("\n", $eventosGeneradosRaw), 'strlen'));
        if (count($lineasEventos) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEventos)))) {
            array_pop($lineasEventos);
        }
        $countEventos = (count($lineasEventos) > 2) ? count($lineasEventos) - 2 : 0;

        // Eventos sin clasificar turno actual
        $eventosSinClasificarActualRaw = isset($faena['metricas']['Eventos Sin Clasificar Turno Actual']) ? trim($faena['metricas']['Eventos Sin Clasificar Turno Actual']) : "";
        $lineasEventosSinClasificarActual = array_values(array_filter(explode("\n", $eventosSinClasificarActualRaw), 'strlen'));
        if (count($lineasEventosSinClasificarActual) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEventosSinClasificarActual)))) {
            array_pop($lineasEventosSinClasificarActual);
        }
        $countEventosSinClasificarActual = (count($lineasEventosSinClasificarActual) > 2) ? count($lineasEventosSinClasificarActual) - 2 : 0;

        // Eventos sin clasificar turno anterior
        $eventosSinClasificarAnteriorRaw = isset($faena['metricas']['Eventos Sin Clasificar Turno Anterior']) ? trim($faena['metricas']['Eventos Sin Clasificar Turno Anterior']) : "";
        $lineasEventosSinClasificarAnterior = array_values(array_filter(explode("\n", $eventosSinClasificarAnteriorRaw), 'strlen'));
        if (count($lineasEventosSinClasificarAnterior) > 2 && preg_match('/^\(?\s*\d+\s+row(s)?\s*\)?$/i', trim(end($lineasEventosSinClasificarAnterior)))) {
            array_pop($lineasEventosSinClasificarAnterior);
        }
        $countEventosSinClasificarAnterior = (count($lineasEventosSinClasificarAnterior) > 2) ? count($lineasEventosSinClasificarAnterior) - 2 : 0;
        ?>
        <!-- Tarjeta de cada faena -->
        <div class="col-md-6">
            <div class="card card-widget shadow widget-user">
                <div class="widget-user-header bg-info" style="height: 30%;">
                    <h1 id="faenaName_<?php echo $faena['id_faena']; ?>"><b><?php echo htmlspecialchars($faena["nombre_faena"]); ?></b></h1>
                    <h6 id="updateTime_<?php echo $faena['id_faena']; ?>"><?php echo $system->datatimeCargaDiv(); ?></h6>
                </div>
                <div class="card-body" style="padding-top:10px;">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <!-- Bloque para Disco Duro -->
                                <div class="col-md-5">
                                    <div class="card card-navy">
                                        <div class="card-header">
                                            <h3 class="card-title"><?php echo htmlspecialchars($faena['nombre_servidor']); ?></h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h4>Disco Duro</h4>
                                                    <input type="text" id="diskKnob_<?php echo $faena['id_faena']; ?>" value="<?php echo $miValor; ?>" class="<?php echo $graficoDonutColor; ?>" data-width="150" data-height="150" data-readonly="true">
                                                    <div class="progress-group mt-2 px-2">
                                                        <h4>HD<span class="float-right"><b id="diskUsage_<?php echo $faena['id_faena']; ?>"><?php echo $lineaDiscoUtilizado; ?>/<?php echo $lineaDiscoTotal; ?></b></span></h4>
                                                        <div class="progress progress-sm">
                                                            <div class="progress-bar bg-primary" style="width: <?php echo $miValor; ?>%"></div>
                                                        </div>
                                                        <div class="row d-flex justify-content-center">
                                                            <button type="button" class="btn btn-outline-dark d-flex my-2" data-toggle="modal" data-target="#particionesModal_<?php echo $faena['id_faena']; ?>">Ver particiones</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Bloque para Memoria RAM -->
                                                <div class="col-md-6">
                                                    <h4>Memoria Ram</h4>
                                                    <input type="text" id="ramKnob_<?php echo $faena['id_faena']; ?>" value="<?php echo $ramPorcentaje; ?>" class="<?php echo $graficoRamColor; ?>" data-width="150" data-height="150" data-readonly="true">
                                                    <div class="progress-group mt-2 px-2">
                                                        <h4>RAM<span class="float-right"><b id="ramUsage_<?php echo $faena['id_faena']; ?>"><?php echo $ramUsedStr; ?>/<?php echo $ramTotalStr; ?></b></span></h4>
                                                        <div class="progress progress-sm">
                                                            <div class="progress-bar bg-primary" style="width: <?php echo $ramPorcentaje; ?>%"></div>
                                                        </div>
                                                        <div class="row d-flex justify-content-center">
                                                            <button type="button" class="btn btn-outline-dark d-flex my-2" data-toggle="modal" data-target="#topCLecturaModal_<?php echo $faena['id_faena']; ?>">Ver top c Lectura</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Gráfica de Load Average -->
                                            <div class="row">
                                                <div class="col-md-9 text-center d-flex justify-content-center">
                                                    <canvas id="<?php echo $canvasID; ?>" style="max-width:400px; height:200px;"></canvas>
                                                </div>
                                                <div class="col-md-3 d-flex align-items-center">
                                                    <h5 id="cpuLoadAverage_<?php echo $faena['id_faena']; ?>">CPU - Load Average: <?php echo (count($loadAverageArray) > 0) ? round(array_sum($loadAverageArray) / count($loadAverageArray), 2) : 0; ?></h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer" style="padding: 5px 0;">
                                            <div class="row">
                                                <div class="col-md-6 d-flex justify-content-center">
                                                    <p style="font-size: 20px;">Nom. Server <b class="d-block"><?php echo isset($faena['metricas']['Nombre Servidor']) ? htmlspecialchars($faena['metricas']['Nombre Servidor']) : ""; ?></b></p>
                                                </div>
                                                <div class="col-md-6 d-flex justify-content-center">
                                                    <p style="font-size: 20px;">IP <b class="d-block"><?php echo isset($faena['metricas']['Ip Servidor']) ? htmlspecialchars($faena['metricas']['Ip Servidor']) : ""; ?></b></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Bloque de Servicios -->
                                    <div class="card card-navy mt-3">
                                        <div class="card-header">
                                            <h3 class="card-title">Servicios</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row d-flex align-items-stretch">
                                                <?php
                                                $rawGrafana = isset($faena['metricas']['Servicio de Grafana']) ? $faena['metricas']['Servicio de Grafana'] : "";
                                                $cleanGrafana = trim($rawGrafana);
                                                $claseGrafana = (preg_match('/^\s*Active:\s+active/i', $cleanGrafana)) ? "bg-success" : "bg-danger";
                                                ?>
                                                <div class="col-lg-4 col-6 mb-3">
                                                    <div id="servicioGrafana_<?php echo $faena['id_faena']; ?>" class="small-box small-box-2 <?php echo $claseGrafana; ?> h-100" style="cursor: pointer;">
                                                        <div class="inner">
                                                            <h5>Grafana</h5>
                                                        </div>
                                                        <div class="icon"><i class="ion ion-stats-bars"></i></div>
                                                    </div>
                                                </div>
                                                <?php
                                                $rawPostgres = isset($faena['metricas']['Servicio de PostgreSQL']) ? $faena['metricas']['Servicio de PostgreSQL'] : "";
                                                $cleanPostgres = trim($rawPostgres);
                                                $clasePostgres = (preg_match('/^\s*Active:\s+active/i', $cleanPostgres)) ? "bg-success" : "bg-danger";
                                                ?>
                                                <div class="col-lg-4 col-6 mb-3">
                                                    <div id="servicioPostgreSQL_<?php echo $faena['id_faena']; ?>" class="small-box small-box-2 <?php echo $clasePostgres; ?> h-100" style="cursor: pointer;">
                                                        <div class="inner">
                                                            <h5>PostgreSQL</h5>
                                                        </div>
                                                        <div class="icon"><i class="fa-solid fa-database"></i></div>
                                                    </div>
                                                </div>
                                                <?php
                                                $rawRsyslog = isset($faena['metricas']['Servicio Rsyslog']) ? $faena['metricas']['Servicio Rsyslog'] : "";
                                                $cleanRsyslog = trim($rawRsyslog);
                                                $claseRsyslog = (preg_match('/^\s*Active:\s+active/i', $cleanRsyslog)) ? "bg-success" : "bg-danger";
                                                ?>
                                                <div class="col-lg-4 col-6 mb-3">
                                                    <div id="servicioRsyslog_<?php echo $faena['id_faena']; ?>" class="small-box small-box-2 <?php echo $claseRsyslog; ?> h-100" style="cursor: pointer;">
                                                        <div class="inner">
                                                            <h5>Rsyslog</h5>
                                                        </div>
                                                        <div class="icon"><i class="fas fa-file-alt"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row d-flex align-items-stretch">
                                                <?php
                                                $rawSSH = isset($faena['metricas']['Servicio SSH']) ? $faena['metricas']['Servicio SSH'] : "";
                                                $cleanSSH = trim($rawSSH);
                                                $claseSSH = (preg_match('/^\s*Active:\s+active/i', $cleanSSH)) ? "bg-success" : "bg-danger";
                                                ?>
                                                <div class="col-lg-4 col-6 mb-3">
                                                    <div id="servicioSSH_<?php echo $faena['id_faena']; ?>" class="small-box small-box-2 <?php echo $claseSSH; ?> h-100" style="cursor: pointer;">
                                                        <div class="inner">
                                                            <h5>SSH</h5>
                                                        </div>
                                                        <div class="icon"><i class="fa fa-plug"></i></div>
                                                    </div>
                                                </div>
                                                <?php
                                                $rawGvupload = isset($faena['metricas']['Servicio gvuploads']) ? $faena['metricas']['Servicio gvuploads'] : "";
                                                $cleanGvupload = trim($rawGvupload);
                                                $claseGvupload = (preg_match('/^\s*Active:\s+active/i', $cleanGvupload)) ? "bg-success" : "bg-danger";
                                                ?>
                                                <div class="col-lg-4 col-6 mb-3">
                                                    <div id="servicioGvupload_<?php echo $faena['id_faena']; ?>" class="small-box small-box-2 <?php echo $claseGvupload; ?> h-100" style="cursor: pointer;">
                                                        <div class="inner">
                                                            <h5>gvuploads</h5>
                                                        </div>
                                                        <div class="icon"><i class="ion ion-pie-graph"></i></div>
                                                    </div>
                                                </div>
                                                <?php
                                                $rawMosquitto = isset($faena['metricas']['Servicio Mosquitto']) ? $faena['metricas']['Servicio Mosquitto'] : "";
                                                $cleanMosquitto = trim($rawMosquitto);
                                                $claseMosquitto = (preg_match('/^\s*Active:\s+active/i', $cleanMosquitto)) ? "bg-success" : "bg-danger";
                                                ?>
                                                <div class="col-lg-4 col-6 mb-3">
                                                    <div id="servicioMosquitto_<?php echo $faena['id_faena']; ?>" class="small-box small-box-2 <?php echo $claseMosquitto; ?> h-100" style="cursor: pointer;">
                                                        <div class="inner">
                                                            <h5>Mosquitto</h5>
                                                        </div>
                                                        <div class="icon"><i class="fa fa-exchange"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row d-flex align-items-stretch">
                                                <?php
                                                $rawNTP = isset($faena['metricas']['Servicio NTP']) ? $faena['metricas']['Servicio NTP'] : "";
                                                $cleanNTP = trim($rawNTP);
                                                $claseNTP = (preg_match('/^\s*Active:\s+active/i', $cleanNTP)) ? "bg-success" : "bg-danger";
                                                ?>
                                                <div class="col-lg-4 col-6 mb-3">
                                                    <div id="servicioNTP_<?php echo $faena['id_faena']; ?>" class="small-box small-box-2 <?php echo $claseNTP; ?> h-100" style="cursor: pointer;">
                                                        <div class="inner">
                                                            <h5>NTP</h5>
                                                        </div>
                                                        <div class="icon"><i class="fa-solid fa-clock"></i></div>
                                                    </div>
                                                </div>
                                                <?php
                                                $rawVMTools = isset($faena['metricas']['Open VM Tools']) ? $faena['metricas']['Open VM Tools'] : "";
                                                $cleanVMTools = trim($rawVMTools);
                                                $claseVMTools = (preg_match('/^\s*Active:\s+active/i', $cleanVMTools)) ? "bg-success" : "bg-danger";
                                                ?>
                                                <div class="col-lg-4 col-6 mb-3">
                                                    <div id="servicioVMTools_<?php echo $faena['id_faena']; ?>" class="small-box small-box-2 <?php echo $claseVMTools; ?> h-100" style="cursor: pointer;">
                                                        <div class="inner">
                                                            <h5>Open VM Tools</h5>
                                                        </div>
                                                        <div class="icon"><i class="fa fa-sitemap"></i></div>
                                                    </div>
                                                </div>
                                                <?php
                                                $rawVPN = isset($faena['metricas']['Servicio VPN']) ? $faena['metricas']['Servicio VPN'] : "";
                                                $cleanVPN = trim($rawVPN);
                                                $claseVPN = (preg_match('/^\s*Active:\s+active/i', $cleanVPN)) ? "bg-success" : "bg-danger";
                                                ?>
                                                <div class="col-lg-4 col-6 mb-3">
                                                    <div id="servicioVPN_<?php echo $faena['id_faena']; ?>" class="small-box small-box-2 <?php echo $claseVPN; ?> h-100" style="cursor: pointer;">
                                                        <div class="inner">
                                                            <h5>VPN</h5>
                                                        </div>
                                                        <div class="icon"><i class="fa fa-wifi"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Bloque de Métricas adicionales -->
                                <div class="col-md-7">
                                    <div class="card card-navy">
                                        <div class="card-header">
                                            <h3 class="card-title">Consultas Grafana</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row d-flex justify-content-center mb-4">
                                                <h3>Tiempos de clasificación monitoreo turno actual</h3>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-5 mx-auto">
                                                    <div class="card border-info mx-sm-1 p-3">
                                                        <div class="card border-info shadow text-info p-3 my-card">
                                                            <span class="fa fa-clock"></span>
                                                        </div>
                                                        <div class="text-info text-center mt-3">
                                                            <h4>Tiempo máximo</h4>
                                                        </div>
                                                        <div class="text-info text-center mt-2">
                                                            <h1 id="tclasValue_<?php echo $faena['id_faena']; ?>" data-raw-tclas="<?php echo $faena['tclasValue']; ?>">
                                                                <?php
                                                                function formatTimeMaxPhp($valueInMinutes)
                                                                {
                                                                    if ($valueInMinutes >= 1440) {
                                                                        $totalSeconds = round($valueInMinutes * 60);
                                                                        $days = floor($totalSeconds / 86400);
                                                                        $remainder = $totalSeconds % 86400;
                                                                        $hours = floor($remainder / 3600);
                                                                        $mins = floor(($remainder % 3600) / 60);
                                                                        return sprintf("%d:%02d:%02d [d]", $days, $hours, $mins);
                                                                    } elseif ($valueInMinutes >= 60) {
                                                                        $horas = floor($valueInMinutes / 60);
                                                                        $mins = $valueInMinutes % 60;
                                                                        return sprintf("%d:%02d [h]", $horas, $mins);
                                                                    } else {
                                                                        $wholeMins = floor($valueInMinutes);
                                                                        $fraction = $valueInMinutes - $wholeMins;
                                                                        $secs = round($fraction * 60);
                                                                        return sprintf("%d:%02d [m]", $wholeMins, $secs);
                                                                    }
                                                                }
                                                                echo formatTimeMaxPhp($faena['tclasValue']);
                                                                ?>
                                                            </h1>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-5 mx-auto">
                                                    <div class="card border-info mx-sm-1 p-3">
                                                        <div class="card border-info shadow text-info p-3 my-card">
                                                            <span class="fa fa-clock"></span>
                                                        </div>
                                                        <div class="text-info text-center mt-3">
                                                            <h4>Tiempo mínimo</h4>
                                                        </div>
                                                        <div class="text-info text-center mt-2">
                                                            <h1 id="tclasMinValue_<?php echo $faena['id_faena']; ?>" data-raw-tclasmin="<?php echo $faena['tclasMinValue'] ?? 0; ?>">
                                                                <?php
                                                                function formatTimeMinPhp($valueInSeconds)
                                                                {
                                                                    $valueInSeconds = floatval($valueInSeconds);
                                                                    if ($valueInSeconds >= 3600) {
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
                                                                echo formatTimeMinPhp($faena['tclasMinValue'] ?? 0);
                                                                ?>
                                                            </h1>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row d-flex justify-content-center mb-4">
                                                <h3>Tiempos de clasificación monitoreo turno anterior</h3>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-5 mx-auto">
                                                    <div class="card border-info mx-sm-1 p-3">
                                                        <div class="card border-info shadow text-info p-3 my-card">
                                                            <span class="fa fa-clock"></span>
                                                        </div>
                                                        <div class="text-info text-center mt-3">
                                                            <h4>Tiempo máximo</h4>
                                                        </div>
                                                        <div class="text-info text-center mt-2">
                                                            <h1 id="tturnAMaxValue_<?php echo $faena['id_faena']; ?>" data-raw-tturnamax="<?php echo $faena['tturnAMaxValue'] ?? 0; ?>">
                                                                <?php echo formatTimeMaxPhp($faena['tturnAMaxValue'] ?? 0); ?>
                                                            </h1>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-5 mx-auto">
                                                    <div class="card border-info mx-sm-1 p-3">
                                                        <div class="card border-info shadow text-info p-3 my-card">
                                                            <span class="fa fa-clock"></span>
                                                        </div>
                                                        <div class="text-info text-center mt-3">
                                                            <h4>Tiempo mínimo</h4>
                                                        </div>
                                                        <div class="text-info text-center mt-2">
                                                            <h1 id="tturnAMinValue_<?php echo $faena['id_faena']; ?>" data-raw-tturnamin="<?php echo $faena['tturnAMinValue'] ?? 0; ?>">
                                                                <?php echo formatTimeMinPhp($faena['tturnAMinValue'] ?? 0); ?>
                                                            </h1>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row d-flex justify-content-center mb-4">
                                                <h3>Equipos desconectados</h3>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mx-auto">
                                                    <div class="card border-info mx-sm-1 p-3">
                                                        <div class="card border-info shadow text-info p-3 my-card">
                                                            <span class="fa fa-window-close"></span>
                                                        </div>
                                                        <div class="text-info text-center mt-3">
                                                            <h4>n° equipos desconectados</h4>
                                                        </div>
                                                        <div class="text-info text-center mt-2">
                                                            <h1 id="numEquipos_<?php echo $faena['id_faena']; ?>"><?php echo $numEquipos; ?></h1>
                                                        </div>
                                                        <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#equiposModal_<?php echo $faena['id_faena']; ?>">ver detalle</button>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mx-auto">
                                                    <div class="card border-info mx-sm-1 p-3">
                                                        <div class="card border-info shadow text-info p-3 my-card">
                                                            <span class="fa fa-window-close"></span>
                                                        </div>
                                                        <div class="text-info text-center mt-3">
                                                            <h4>reportes de snapchots</h4>
                                                        </div>
                                                        <div class="text-info text-center mt-2">
                                                            <h1 id="countSnapchots_<?php echo $faena['id_faena']; ?>"><?php echo $countSnapchots; ?></h1>
                                                        </div>
                                                        <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#snapchotsModal_<?php echo $faena['id_faena']; ?>">ver detalle</button>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mx-auto">
                                                    <div class="card border-info mx-sm-1 p-3">
                                                        <div class="card border-info shadow text-info p-3 my-card">
                                                            <span class="fa fa-window-close"></span>
                                                        </div>
                                                        <div class="text-info text-center mt-3">
                                                            <h4>n° eventos generados</h4>
                                                        </div>
                                                        <div class="text-info text-center mt-2">
                                                            <h1 id="countEventos_<?php echo $faena['id_faena']; ?>"><?php echo $countEventos; ?></h1>
                                                        </div>
                                                        <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#eventosModal_<?php echo $faena['id_faena']; ?>">ver detalle</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row d-flex justify-content-center mb-4">
                                                <h3>Eventos sin clasificar</h3>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-5 mx-auto">
                                                    <div class="card border-info mx-sm-1 p-3">
                                                        <div class="card border-info shadow text-info p-3 my-card">
                                                            <span class="fa fa-clock"></span>
                                                        </div>
                                                        <div class="text-info text-center mt-3">
                                                            <h4>Turno actual</h4>
                                                        </div>
                                                        <div class="text-info text-center mt-2">
                                                            <h1 id="countEventosSinClasificarActual_<?php echo $faena['id_faena']; ?>"><?php echo $countEventosSinClasificarActual; ?></h1>
                                                        </div>
                                                        <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#eventosActualModal_<?php echo $faena['id_faena']; ?>">ver detalle</button>
                                                    </div>
                                                </div>
                                                <div class="col-md-5 mx-auto">
                                                    <div class="card border-info mx-sm-1 p-3">
                                                        <div class="card border-info shadow text-info p-3 my-card">
                                                            <span class="fa fa-clock"></span>
                                                        </div>
                                                        <div class="text-info text-center mt-3">
                                                            <h4>Turno anterior</h4>
                                                        </div>
                                                        <div class="text-info text-center mt-2">
                                                            <h1 id="countEventosSinClasificarAnterior_<?php echo $faena['id_faena']; ?>"><?php echo $countEventosSinClasificarAnterior; ?></h1>
                                                        </div>
                                                        <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#eventosAnteriorModal_<?php echo $faena['id_faena']; ?>">ver detalle</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modales -->
        <div class="modal fade" id="particionesModal_<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="particionesModalLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-custom" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="particionesModalLabel_<?php echo $faena['id_faena']; ?>">Particiones Disco Duro - <?php echo htmlspecialchars($faena['nombre_servidor']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <?php if (!empty($lineasParticiones)): ?>
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>% Uso</th>
                                        <th>Espacio</th>
                                        <th>Punto de Montaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lineasParticiones as $linea):
                                        $part = preg_split('/\s+/', trim($linea));
                                        if (count($part) < 3) continue;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($part[0]); ?></td>
                                            <td><?php echo htmlspecialchars($part[1]); ?></td>
                                            <td><?php echo htmlspecialchars(implode(" ", array_slice($part, 2))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">No hay datos disponibles</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="topCLecturaModal_<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="topCLecturaModalLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-custom" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="topCLecturaModalLabel_<?php echo $faena['id_faena']; ?>">Top c Lectura - <?php echo htmlspecialchars($faena['nombre_servidor']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <?php if (!empty($lineasTopCLectura)): ?>
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
                                    <?php foreach ($lineasTopCLectura as $linea):
                                        $parts = preg_split('/\s+/', trim($linea));
                                        if (!empty($parts) && is_numeric($parts[0])): ?>
                                            <tr>
                                                <?php foreach ($parts as $part): ?>
                                                    <td><?php echo htmlspecialchars($part); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                    <?php endif;
                                    endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">No hay datos disponibles</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="equiposModal_<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="equiposModalLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-custom" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="equiposModalLabel_<?php echo $faena['id_faena']; ?>">Equipos desconectados - <?php echo htmlspecialchars($faena['nombre_servidor']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <table id="equiposTable_<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>última conexión</th>
                                    <th>equipo</th>
                                    <th>estado</th>
                                    <th>desconectado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalEquipos = count($lineasEquipos);
                                for ($i = 2; $i < $totalEquipos; $i++):
                                    $linea = trim($lineasEquipos[$i]);
                                    if (empty($linea)) continue;
                                    $cols = array_map('trim', explode("|", $linea));
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cols[0]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[1]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[2]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[3]); ?></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="snapchotsModal_<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="snapchotsModalLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-custom" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="snapchotsModalLabel_<?php echo $faena['id_faena']; ?>">Reporte Snapchots - <?php echo htmlspecialchars($faena['nombre_servidor']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <table id="snapchotsTable_<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>último envío</th>
                                    <th>equipo</th>
                                    <th>enviado hace</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalSnap = count($lineasSnapchots);
                                for ($i = 2; $i < $totalSnap; $i++):
                                    $linea = trim($lineasSnapchots[$i]);
                                    if (empty($linea)) continue;
                                    $cols = array_map('trim', explode("|", $linea));
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cols[0]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[1]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[2]); ?></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="eventosModal_<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="eventosModalLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-custom" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventosModalLabel_<?php echo $faena['id_faena']; ?>">Eventos Generados - <?php echo htmlspecialchars($faena['nombre_servidor']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <table id="eventosTable_<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>último envío</th>
                                    <th>equipo</th>
                                    <th>enviado hace</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalEventos = count($lineasEventos);
                                for ($i = 2; $i < $totalEventos; $i++):
                                    $linea = trim($lineasEventos[$i]);
                                    if (empty($linea)) continue;
                                    $cols = array_map('trim', explode("|", $linea));
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cols[0]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[1]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[2]); ?></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="eventosActualModal_<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="eventosActualModalLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-custom" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventosActualModalLabel_<?php echo $faena['id_faena']; ?>">Eventos sin clasificar - Turno Actual - <?php echo htmlspecialchars($faena['nombre_servidor']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <table id="eventosActualTable_<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Equipo</th>
                                    <th>Creado</th>
                                    <th>Llegó al servidor</th>
                                    <th>Demora revisión</th>
                                    <th>Tipo evento</th>
                                    <th>Estado</th>
                                    <th>ID</th>
                                    <th>Time Server</th>
                                    <th>Turno</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalEventosActual = count($lineasEventosSinClasificarActual);
                                for ($i = 2; $i < $totalEventosActual; $i++):
                                    $linea = trim($lineasEventosSinClasificarActual[$i]);
                                    if (empty($linea)) continue;
                                    $cols = array_map('trim', explode("|", $linea));
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cols[0]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[1]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[2]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[3]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[4]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[5]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[6]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[7]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[8]); ?></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="eventosAnteriorModal_<?php echo $faena['id_faena']; ?>" tabindex="-1" role="dialog" aria-labelledby="eventosAnteriorModalLabel_<?php echo $faena['id_faena']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-custom" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventosAnteriorModalLabel_<?php echo $faena['id_faena']; ?>">Eventos sin clasificar - Turno Anterior - <?php echo htmlspecialchars($faena['nombre_servidor']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <table id="eventosAnteriorTable_<?php echo $faena['id_faena']; ?>" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Equipo</th>
                                    <th>Creado</th>
                                    <th>Llegó al servidor</th>
                                    <th>Demora revisión</th>
                                    <th>Tipo evento</th>
                                    <th>Estado</th>
                                    <th>ID</th>
                                    <th>Time Server</th>
                                    <th>Turno</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalEventosAnterior = count($lineasEventosSinClasificarAnterior);
                                for ($i = 2; $i < $totalEventosAnterior; $i++):
                                    $linea = trim($lineasEventosSinClasificarAnterior[$i]);
                                    if (empty($linea)) continue;
                                    $cols = array_map('trim', explode("|", $linea));
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cols[0]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[1]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[2]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[3]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[4]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[5]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[6]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[7]); ?></td>
                                        <td><?php echo htmlspecialchars($cols[8]); ?></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Inicialización de DataTables -->
<script>
    $(document).ready(function() {
        <?php foreach ($faenas as $faena): ?>
            $('#equiposTable_<?php echo $faena['id_faena']; ?>').DataTable({
                "order": [
                    [3, "desc"]
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
            $('#snapchotsTable_<?php echo $faena['id_faena']; ?>').DataTable({
                "order": [
                    [2, "desc"]
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
            $('#eventosTable_<?php echo $faena['id_faena']; ?>').DataTable({
                "order": [
                    [2, "desc"]
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
            $('#eventosActualTable_<?php echo $faena['id_faena']; ?>').DataTable({
                "order": [
                    [1, "desc"]
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
            $('#eventosAnteriorTable_<?php echo $faena['id_faena']; ?>').DataTable({
                "order": [
                    [1, "desc"]
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
        <?php endforeach; ?>
    });
</script>
<!-- Inicialización de jQuery Knob -->
<script>
    $(document).ready(function() {
        $('.GraficoAzul').knob({
            readOnly: true,
            rotation: 'anticlockwise',
            thickness: '.3',
            width: 150,
            height: 150,
            fgColor: '#3c8dbc',
            draw: function() {
                $(this.i).val($(this.i).val() + '%');
            }
        });
        $('.GraficoVerde').knob({
            readOnly: true,
            rotation: 'anticlockwise',
            thickness: '.3',
            width: 150,
            height: 150,
            fgColor: '#09DA06',
            draw: function() {
                $(this.i).val($(this.i).val() + '%');
            }
        });
        $('.GraficoAmarillo').knob({
            readOnly: true,
            rotation: 'anticlockwise',
            thickness: '.3',
            width: 150,
            height: 150,
            fgColor: '#ffc107',
            draw: function() {
                $(this.i).val($(this.i).val() + '%');
            }
        });
        $('.GraficoRojo').knob({
            readOnly: true,
            rotation: 'anticlockwise',
            thickness: '.3',
            width: 150,
            height: 150,
            fgColor: 'red',
            draw: function() {
                $(this.i).val($(this.i).val() + '%');
            }
        });
    });
</script>
<!-- Inicialización de Chart.js para Load Average -->
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
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false
                        }
                    }],
                    yAxes: [{
                        gridLines: {
                            display: false
                        },
                        ticks: {
                            min: 0,
                            max: 5,
                            stepSize: 1
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
        window.loadCharts[canvasID] = chart;
    }
    <?php foreach ($faenas as $faena):
        $loadDataJS = json_encode($faena['loadAverageArray']);
    ?>
        initLoadAverageChart("loadAverageChart_<?php echo $faena['id_faena']; ?>", <?php echo $loadDataJS; ?>);
    <?php endforeach; ?>
</script>
<!-- WebSocket para actualización en tiempo real -->
<script>
    var ws = new WebSocket("ws://10.40.90.99:9507");
    ws.onopen = function() {
        console.log("Conexión WebSocket establecida");
    };
    ws.onerror = function(error) {
        console.error("WebSocket error:", error);
    };
    ws.onmessage = function(event) {
        var data = JSON.parse(event.data);
        console.log("Datos recibidos:", data);
        data.forEach(function(item) {
            var nameElem = document.getElementById("faenaName_" + item.id_faena);
            if (nameElem) {
                nameElem.innerText = item.nombre_faena;
            }
            var updateTimeElem = document.getElementById("updateTime_" + item.id_faena);
            if (updateTimeElem && item.fecha_actualizacion) {
                updateTimeElem.innerText = item.fecha_actualizacion;
            }
            var diskKnob = $("#diskKnob_" + item.id_faena);
            if (diskKnob.length) {
                diskKnob.val(item.diskPorcentaje).trigger('change');
            }
            var diskUsage = document.getElementById("diskUsage_" + item.id_faena);
            if (diskUsage) {
                diskUsage.innerText = item.discoUsado + "/" + item.discoTotal;
            }
            var ramKnob = $("#ramKnob_" + item.id_faena);
            if (ramKnob.length) {
                ramKnob.val(item.ramPorcentaje).trigger('change');
            }
            var ramUsage = document.getElementById("ramUsage_" + item.id_faena);
            if (ramUsage) {
                ramUsage.innerText = item.ramUsedStr + "/" + item.ramTotalStr;
            }
            if (window.loadCharts && window.loadCharts["loadAverageChart_" + item.id_faena]) {
                window.loadCharts["loadAverageChart_" + item.id_faena].data.datasets[0].data = item.loadAverageArray;
                window.loadCharts["loadAverageChart_" + item.id_faena].update();
            }
            var cpuLoadElem = document.getElementById("cpuLoadAverage_" + item.id_faena);
            if (cpuLoadElem && item.loadAverageArray && item.loadAverageArray.length > 0) {
                var sum = item.loadAverageArray.reduce(function(a, b) {
                    return a + b;
                }, 0);
                var avg = (sum / item.loadAverageArray.length).toFixed(2);
                cpuLoadElem.innerText = "CPU - Load Average: " + avg;
            }
            // Actualización de servicios
            var grafanaElem = document.getElementById("servicioGrafana_" + item.id_faena);
            if (grafanaElem && item.metricas && item.metricas["Servicio de Grafana"]) {
                var isActive = /^Active:\s+active/i.test(item.metricas["Servicio de Grafana"].trim());
                grafanaElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
            }
            var postgresElem = document.getElementById("servicioPostgreSQL_" + item.id_faena);
            if (postgresElem && item.metricas && item.metricas["Servicio de PostgreSQL"]) {
                var isActive = /^Active:\s+active/i.test(item.metricas["Servicio de PostgreSQL"].trim());
                postgresElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
            }
            var rsyslogElem = document.getElementById("servicioRsyslog_" + item.id_faena);
            if (rsyslogElem && item.metricas && item.metricas["Servicio Rsyslog"]) {
                var isActive = /^Active:\s+active/i.test(item.metricas["Servicio Rsyslog"].trim());
                rsyslogElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
            }
            var sshElem = document.getElementById("servicioSSH_" + item.id_faena);
            if (sshElem && item.metricas && item.metricas["Servicio SSH"]) {
                var isActive = /^Active:\s+active/i.test(item.metricas["Servicio SSH"].trim());
                sshElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
            }
            var gvuploadElem = document.getElementById("servicioGvupload_" + item.id_faena);
            if (gvuploadElem && item.metricas && item.metricas["Servicio gvuploads"]) {
                var isActive = /^Active:\s+active/i.test(item.metricas["Servicio gvuploads"].trim());
                gvuploadElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
            }
            var mosquittoElem = document.getElementById("servicioMosquitto_" + item.id_faena);
            if (mosquittoElem && item.metricas && item.metricas["Servicio Mosquitto"]) {
                var isActive = /^Active:\s+active/i.test(item.metricas["Servicio Mosquitto"].trim());
                mosquittoElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
            }
            var ntpElem = document.getElementById("servicioNTP_" + item.id_faena);
            if (ntpElem && item.metricas && item.metricas["Servicio NTP"]) {
                var isActive = /^Active:\s+active/i.test(item.metricas["Servicio NTP"].trim());
                ntpElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
            }
            var vmToolsElem = document.getElementById("servicioVMTools_" + item.id_faena);
            if (vmToolsElem && item.metricas && item.metricas["Open VM Tools"]) {
                var isActive = /^Active:\s+active/i.test(item.metricas["Open VM Tools"].trim());
                vmToolsElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
            }
            var vpnElem = document.getElementById("servicioVPN_" + item.id_faena);
            if (vpnElem && item.metricas && item.metricas["Servicio VPN"]) {
                var isActive = /^Active:\s+active/i.test(item.metricas["Servicio VPN"].trim());
                vpnElem.className = "small-box small-box-2 " + (isActive ? "bg-success" : "bg-danger") + " h-100";
            }
            // Actualización de contadores
            var numEquiposElem = document.getElementById("numEquipos_" + item.id_faena);
            if (numEquiposElem) {
                numEquiposElem.innerText = item.numEquipos;
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
        });
    };
</script>
<!-- Funciones de formateo -->
<script>
    function formatTimeMax(valueInMinutes) {
        let val = parseFloat(valueInMinutes) || 0;
        if (val >= 1440) {
            let totalSeconds = Math.round(val * 60);
            let days = Math.floor(totalSeconds / 86400);
            let remainder = totalSeconds % 86400;
            let hours = Math.floor(remainder / 3600);
            let mins = Math.floor((remainder % 3600) / 60);
            return days + ":" + (hours < 10 ? "0" + hours : hours) + ":" + (mins < 10 ? "0" + mins : mins) + " [d]";
        } else if (val >= 60) {
            let hours = Math.floor(val / 60);
            let mins = Math.floor(val % 60);
            return hours + ":" + (mins < 10 ? "0" + mins : mins) + " [h]";
        } else {
            let wholeMins = Math.floor(val);
            let fraction = val - wholeMins;
            fraction = Math.round(fraction * 100) / 100;
            let secs = Math.round(fraction * 60);
            return wholeMins + ":" + (secs < 10 ? "0" + secs : secs) + " [m]";
        }
    }

    function formatTimeMin(valueInSeconds) {
        let val = parseFloat(valueInSeconds) || 0;
        if (val >= 3600) {
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
</script>