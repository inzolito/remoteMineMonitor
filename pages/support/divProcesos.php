<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");
require_once("../../build/controller/controller-alerta.php");
$alertas = new alertas();

$system = new systemClass();
$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();
$id_faena = $_REQUEST["id"];


//Falta validar cuando no existe la faena
$faenaDatos = $fenaCl->datos($id_faena);
$estadoCheckFaena = $fenaCl->estado($id_faena);
$checkDatos = $fenaCl->datosCheck($id_faena);

$carpeta = $faenaDatos->alias . "/";
$ruta = "/home/jigsaw/monitoreoRemoto/" . $carpeta;




// ------------------------------------------------

$impo = file($ruta . "ProcesoImpoMon.log");
$sqlServer = file($ruta . "ProcesoSqlMon.log");
$sql_Back = file($ruta . "ProcesoSqlBackMon.log");
$sumarizadorLog = file($ruta . "ProcesosSumarizadorMon.log");

$logSumarizadorDatos = file($ruta . "SummarizerMon.log");

$jamsActivoLog = file($ruta . "ProcesosJamsMon.log");
$jamsSec = file($ruta . "ProcesosJamsSecMon.log");
$Ntp = file($ruta . "NtpMon.log");
$reconcile = file($ruta . "ReconcileMon.log");
$BackupSec = file($ruta . "BackupSecMon.log");
$procSumCrontab = file($ruta . "crontabSummMon.log");
$listaJams = file($ruta . "ListaJamsMon.log");

$reiniciosJAMS = file($ruta . "reiniciosJAMSMon.log");


//count de lineas Impo
$largoImpo = count($impo) - 2;
//count sqlServer
$largoSqlServer = count($sqlServer) - 3;
//count sql back
$largoSqlBack = count($sql_Back) - 3;
//count Summ
$largoSummarizador = count($sumarizadorLog) - 3;
//count Ntp
$largoNtp = count($Ntp) - 2;
//count reconcile
$largoReconcile = count($reconcile) - 5;


// Validacion importador por tiempo












//largo sum por Crontab
//$largoValidarCrontab = count($procSumCrontab);
//for para obtener si esta corriendo por crontab
//for ($x = 1; $x < $largoValidarCrontab; $x++) {
//   $validarCrontab = $procSumCrontab[$x];
//   $validarCrontab  = substr($validarCrontab, 0, 1);
//   if ($validarCrontab == "*") {
//       $validarCrontabOK = "Crontab";
//   }
//}



// Funcion para enviar mensajes por Telegram
function enviarMensajeTelegram($message)
{
    $botToken = "7167115609:AAEEihCCCRzkJmsOkFAfvOPYSwl1qr2Ts2E"; //Token del bot Telegram
    $chatId = "-1002428398059"; // Reemplazar con el chat ID del usuario o grupo al que se le quiere enviar el mensaje

    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    $data = array(
        'chat_id' => $chatId,
        'text' => $message
    );

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
}

//Funcion par enviar mensaje por correo
function enviarEmail($subject, $message)
{
    $to = 'soportechile@Hexmet.onmicrosoft.com';
    $headers = 'From: @gmail.com' . "\r\n" .
        'Reply-To: @gmail.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
}

//conseguir fecha actual
date_default_timezone_set('America/Santiago');
$fechaActualVisual = "<i class='fas fa-calendar'></i>" . date("d");
$horaActualVsual = "<i class='fas fa-clock ml-1'></i>" . date("H:i");
$dataTimeVisual = $fechaActualVisual . " " . $horaActualVsual;


//validar Jams
$largoJams = count($jamsActivoLog);

if (strpos($jamsActivoLog, "JAMSRouter start") !== false && strpos($jamsActivoLog, "JAMSCluster run") !== false && $largoJams > 3) {
    $validarJams = 1;
} else if (strpos($jamsActivoLog, "JAMSRun -config config.jams -log JAMS,error") !== false || $largoJams < 4) {
    $validarJams = 2;
}

//validar Jams Sec
$largoJamsSec = count($jamsSec);


if (strpos($jamsSec, "JAMSCluster run") !== false) {
    $validarJamsSec = 1;
} else {
    $validarJamsSec = 2;
}

//validar ntp
if (strpos($Ntp, "/usr/sbin/ntpd -p") !== false) {
    $validacionNtp = 1;
} else {
    $validacionNtp = 2;
}

//validar manual 
if (strpos("-force -start", $sumarizadorLog) !== false) {
    $validarManual = 1;
} else {
    $validarManual = 2;
}

//validar reconcile
$validarReconcile = 1;
foreach ($reconcile as $item) {
    if (stripos($item, 'ERROR') !== false || stripos($item, 'warning') !== false) {
        $validarReconcile = 0;
        break;
    }
}

//validar log sumarizador
$validarLogSummarizer = 1;
foreach ($logSumarizadorDatos as $item) {
    if (stripos($item, 'ERROR') !== false || stripos($item, 'exception') !== false || stripos($item, 'grouping') !== false) {
        $validarLogSummarizer = 0;
        break;
    }
}


//largo Proceso sumarizador
//print_r($procSumCrontab);


$estadoCrontabSumarizador = 1;
$largoSummarizadorCrontab = 0;
foreach ($procSumCrontab as $line) {
    //echo "Log entry: $line<br><br>";

    $firstChar = substr(trim($line), 0, 1);
    if ($firstChar === '#') {
        $estadoCrontabSumarizador = 0;
    } else {
        if ($firstChar == '*') {
            $largoSummarizadorCrontab++;
            $estadoCrontabSumarizador = 1;
        }
    }
}

// El valor de $estadoCrontabSumarizador será 1 si alguna línea está descomentada


?>



<!-- PS AUX , importadores , sumarizador, jams etc -->
<div class="col-md-12">
    <div class='<?php echo $system->pintarDiv($largoJams) ?>'>
        <div class="card-header">
            <h3 class="card-title">Procesos </h3>
            <div class="card-tools">
                <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual ?></span>
            </div>
        </div>

        <div class="card-body">
            <!-- ----------- Row Datos procesos------------->
            <div class="row">





                <!--------------------- Reinicios del JAMS------------------------------->
                <?php

                $lines = explode("\n", trim($reiniciosJAMS));

                // Usar una expresión regular para extraer la fecha y hora de los nombres de archivo
                $pattern = "/JAMS\.(\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2})_UTC\.log/";
                $dates = [];

                foreach ($lines as $line) {
                    if (preg_match($pattern, $line, $matches)) {
                        $dates[] = new DateTime(str_replace('-', ':', $matches[1]));
                    }
                }

                $fiveMinCounter = 0;
                $fiveMinutesInSeconds = 300; // 5 minutos en segundos

                for ($i = 1; $i < count($dates); $i++) {
                    $diffInSeconds = abs($dates[$i]->getTimestamp() - $dates[$i - 1]->getTimestamp());

                    // Comprobar si la diferencia es de aproximadamente 5 minutos
                    if ($diffInSeconds >= (5 * 60) - 10 && $diffInSeconds <= (5 * 60) + 10) {
                        $fiveMinCounter++;
                    }
                }

                $colorAux = "bg-success";
                $valorAux = "Success";





                if (count($reiniciosJAMS) >= 4) {
                    $colorAux = "bg-warning";
                    $valorAux = "Warning";
                    //$subject = 'Problemas con ' . $carpeta;
                    //$message = 'problemas en el Proceso Jams';
                    //enviarEmail($subject, $message);
                    // ---- Insert Alerta
                    //$mensajeAlerta = "Se detectó que el JAMS se ha reiniciado mas de 2 veces.  ";
                    //$alertas->insertAlert($id_faena, "HCXAL001", $mensajeAlerta);
                    //--------------------
                }
                if (count($reiniciosJAMS) >= 5) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger";
                    //$subject = 'Problemas con ' . $carpeta;
                    //$message = 'problemas en el Proceso Jams';
                    //enviarEmail($subject, $message);
                    // ---- Insert Alerta
                    $mensajeAlerta = "Se detectó que el JAMS se ha reiniciado mas de 4 veces.  ";
                    $alertas->insertAlert($id_faena, "HCXJAMS01", $mensajeAlerta);
                    //--------------------
                } else {
                    // ---- Limpieza Alerta
                    $mensajeAlerta = "Se detectó que el JAMS se ha reiniciado mas de 4 veces.  ";
                    $alertas->insertAlert($id_faena, "HCXJAMS01", $mensajeAlerta, 1);
                    //--------------------

                }


                ?>
                <!-- Reinicios  PRimario -->


                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divReiniciosJams')">

                    <div class="small-box small-box-2 <?php echo $colorAux ?>">
                        <div class="inner">
                            <h5>reinicios de JAMS <?php echo $system->validarLog($reiniciosJAMS, 4, "right") ?></h5>
                            <p>reincios: <?php echo count($reiniciosJAMS) - 1  ?></p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-refresh"></i>
                        </div>
                    </div>
                </div>
                <!--------------------------------------------------------------------->








                <!--------------------- JAMS primario ------------------------------->
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success";

                if ($validarJams == 2) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger";
                    //$subject = 'Problemas con ' . $carpeta;
                    //$message = 'problemas en el Proceso Jams';
                    //enviarEmail($subject, $message); HCXJAMS02
                    $mensajeAlerta = "Se debe revisar el proceso del JAMS  ";
                    $alertas->insertAlert($id_faena, "HCXJAMS02", $mensajeAlerta);
                } else {
                    $mensajeAlerta = "Se debe revisar el proceso del JAMS  ";
                    $alertas->insertAlert($id_faena, "HCXJAMS02", $mensajeAlerta, 1);
                }

                ?>
                <!-- JAMS PRimario -->

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divJamsVista')">

                    <div class="small-box small-box-2 <?php echo $colorAux ?>">
                        <div class="inner">
                            <h5>JAMS Primario <?php echo $system->validarLog($jamsActivoLog, 4, "right") ?></h5>
                            <p><?php echo $valorAux ?></p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i>
                        </div>
                    </div>
                </div>
                <!--------------------------------------------------------------------->

                <!--------------------- JAMS Secundario ------------------------------->


                <!-- JAMS Sec -->
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success";
                if ($validarJamsSec == 2) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger";

                    // ---- Insert Alerta
                    $mensajeAlerta = "Se debe revisar el proceso del JAMS En el servidor Backup ";
                    $alertas->insertAlert($id_faena, "HCXJAMS03", $mensajeAlerta);


                    //--------------------
                }

                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divJamsSecVista')" data-toggle="tooltip" title="Este es un tooltip">

                    <div class="small-box small-box-2 <?php echo $colorAux ?>">
                        <div class="inner">

                            <h6>JAMS Sec. <?php echo $system->validarLog($jamsSec, 4, "right") ?></h6>
                            <p><?php
                                echo $valorAux ?></p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i>
                        </div>

                    </div>
                </div>
                <!------------------------------------------------------------------------>

                <!--------------------------- importadores ------------------------------->
                <?php
                $caux = 0;
                foreach ($impo as $x) {
                    $importadores = $impo;
                    if ($caux == 0) {
                    } else {

                        $parts = explode(' ', $x);
                        $parts = array_filter($parts, function ($part) {
                            return trim($part) !== '';
                        });
                        $parts = array_values($parts);
                        $dataTimeEjecucion = $parts[8];
                        $currentTime = new DateTime();



                        if (strpos($dataTimeEjecucion, ':') !== false) {
                            $logTime = DateTime::createFromFormat('H:i', $dataTimeEjecucion);
                            $logTime->setDate($currentTime->format('Y'), $currentTime->format('m'), $currentTime->format('d'));
                        } else {
                            // Verificar si $dataTimeEjecucion es una fecha válida (YYYY-MM-DD)
                            $logTime = DateTime::createFromFormat('Y-m-d H:i', $dataTimeEjecucion . ' ' . $parts[9]);
                            if (!$logTime) {
                                // Caso fecha en otro formato, la hora está en $parts[9]
                                $logTime = DateTime::createFromFormat('M d H:i', $dataTimeEjecucion . ' ' . $parts[9]);
                            }
                        }



                        $interval = $currentTime->diff($logTime);
                        $minutesPassed = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
                        #echo $minutesPassed. "Minutos que han pasado";
                        // Verificar si el intervalo es menor o igual a 10 minutos

                        if ($faenaDatos->alias == "magsa") {
                            if ($minutesPassed <= 70) {
                                $largoAux = 1;

                                //echo 'Dentro de los 10 minutos.';
                            } else {
                                $largoAux = 10;

                                break;
                            }
                        } else {

                            if ($minutesPassed <= 10) {
                                $largoAux = 1;

                                //echo 'Dentro de los 10 minutos.';
                            } else {
                                $largoAux = 10;

                                break;
                            }
                        }
                    }
                    $caux++;
                    #echo  "RMM@sistemaDeMonitoreo : ~ ". $lineaImportador . "<br>";
                }
                $caux = 0;
                ?>


                <?php
                $colorAux = "bg-success";
                $valorAux = "Success: ";


                if ($largoAux >= 5) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger: ";
                    //echo $system->alerta("Error en Importadores","Demaciados procesos Impo");
                    // $system->alertaSonora(120000);
                     $mensajeAlerta = "Se detectó importadores pegados ";
                    $alertas->insertAlert($id_faena, "HCXIMP002", $mensajeAlerta);
                    //--------------------
                } else {
                    if ($largoAux >= 3 && $largoAux <= 4) {
                        $colorAux = "bg-warning";
                        $valorAux = "Warning: ";
                    }

                    // Limpieza
                    $mensajeAlerta = "Se detectó importadores pegados ";
                    $alertas->insertAlert($id_faena, "HCXIMP002", $mensajeAlerta, 1);
                }

                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divImpoVista')">

                    <div class="small-box small-box-2 <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h6>Importadores <?php echo $system->validarLog($impo, 4, "right") ?></h6>
                            <p> <?php echo $valorAux . $largoImpo ?></p>
                        </div>
                        <div class="icon">
                            <i class="fa-solid fa-cubes-stacked" style="font-size:48px"> </i>
                        </div>
                    </div>
                </div>

                <!------------------------------------------------------------------------>

                <!--------------------------- Sql Server ------------------------------->

                <!-- SQLServer -->
                <?php

                $colorAux = "bg-success";
                $valorAux = "Success: ";
                $largoAux = $largoSqlServer;

                if ($largoAux < 0 || $largoAux > 4) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger: ";
                    //$system->alertaSonora(120000); 
                    $alertas->insertAlert($id_faena, "HCXPES011", "En " . $faenaDatos->alias . ". Procesos encolados en la replica a s q l server.");
                } else {
                    if ($largoAux == 3) {
                        $colorAux = "bg-warning";
                        $valorAux = "Warning: ";
                    }
                    $alertas->insertAlert($id_faena, "HCXPES011", "En " . $faenaDatos->alias . ". Procesos encolados en la replica a s q l server.", 1);
                }



                ?>


                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divSqlServerVista')">

                    <div class="small-box small-box-2 <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h5>SQL server <?php echo $system->validarLog($sqlServer, 4, "right") ?></h5>
                            <p><?php echo $valorAux . $largoSqlServer ?></p>
                        </div>
                        <div class="icon">
                            <i class="fas  fa-database " style="font-size:48px"></i>
                        </div>
                    </div>
                </div>



                <!------------------------------------------------------------------------>

                <!--------------------------- Sql Back ------------------------------->


                <!-- Sql_Back -->
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success: ";
                $largoAux = $largoSqlBack;
                if ($largoAux > 1 && $largoAux < 6) {
                    $colorAux = "bg-warning";
                    $valorAux = "Warning: ";
                }
                if ($largoAux < 0 || $largoAux >= 6) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger: ";
                    //  $system->alertaSonora(120000);

                }

                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divSqlBackVista')">

                    <div class="small-box small-box-2  <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h5>sql_back <?php echo $system->validarLog($sql_Back, 4, "right") ?></h5>
                            <p> <?php echo $valorAux . $largoSqlBack ?></p>
                        </div>
                        <div class="icon">
                            <i class="fas  fa-database " style="font-size:48px"></i>
                        </div>
                    </div>
                </div>

                <!------------------------------------------------------------------------>

                <!--------------------------- Sql Sumarizador ------------------------------->


                <?php
                $colorAux = "bg-success";
                $valorAux = "Success: ";
                $largoAux = $largoSummarizador;
                if ($largoAux == 3 || $largoAux == 1) {
                    $colorAux = "bg-warning";
                    $valorAux = "Warning: ";
                }
                if (($largoAux < 1 && $largoSummarizadorCrontab == 0)) {

                    //echo "<div class='col-lg-4 col-6' >Largo aux : ".$largoAux."</div>";
                    $colorAux = "bg-warning";
                    $valorAux = "Warning: ";
                    // ---- Insert Alerta
                    $mensajeAlerta = "Se detectó que no se está realizando el proceso de sumarización.  ";
                    $alertas->insertAlert($id_faena, "HCXSD011", $mensajeAlerta);
                    //--------------------
                }

                if ($largoAux >= 6) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger: ";
                    // ---- Insert Alerta
                    $mensajeAlerta = "Se detectó que el sumarizador está encolado  ";
                    $alertas->insertAlert($id_faena, "HCXSD011", $mensajeAlerta);
                    //--------------------
                }



                if ($validarLogSummarizer == 0) {
                    $colorAux = "bg-danger";
                    $textoAux = "Danger";
                    $valorAux = "Danger: ";
                    // ---- Insert Alerta
                    $mensajeAlerta = "Se detectó un error en el log del sumarizador.  ";
                    $alertas->insertAlert($id_faena, "HCXSD011", $mensajeAlerta);

                    //--------------------
                }

                if ($valorAux == "Success: ") {
                    //limpiar alert sumarizadores 
                    $mensajeAlerta = "Se detectó un error en el log del sumarizador.  ";
                    $alertas->insertAlert($id_faena, "HCXSD011", $mensajeAlerta, 1);
                }

                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divSummVista')">

                    <div class="small-box small-box-2 <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h6>Sumarizador <?php echo $system->validarLog($sumarizadorLog, 6, "right") ?></h6>
                            <p><?php echo $valorAux . $largoSummarizador ?></p>
                        </div>
                        <div class="icon">
                            <i class="fa-sharp fa-regular fa-bars-staggered" style="font-size:48px"></i>
                        </div>
                    </div>
                </div>


                <!------------------------------------------------------------------------>

                <!--------------------------- Sql NTP ------------------------------->

                <!-- NTP -->
                <?php
                $colorAux = "bg-success";
                $valorAux = "Success: ";
                $largoAux = $largoNtp;
                if ($validacionNtp == 2) {
                    $colorAux = "bg-danger";
                    $valorAux = "Danger: ";
                    // ---- Insert Alerta
                    $mensajeAlerta = "Se detectó un error en el NTP.  ";
                    $alertas->insertAlert($id_faena, "HCXAL001", $mensajeAlerta);
                    //--------------------
                } else {
                    $mensajeAlerta = "Se detectó un error en el NTP.  ";
                    $alertas->insertAlert($id_faena, "HCXAL001", $mensajeAlerta, 1);
                    //--------------------
                }


                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divNtpVista')">

                    <div class="small-box small-box-2 <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h6>Ntp <?php echo $system->validarLog($Ntp, 4, "right") ?></h6>
                            <p> <?php echo $valorAux . $largoNtp ?></p>
                        </div>
                        <div class="icon">
                            <i class="fa-regular fa-clock" style="font-size:48px"></i>
                        </div>
                    </div>
                </div>

                <!------------------------------------------------------------------------>

                <!--------------------------- Reconcilie ------------------------------->
                <!-- Reconcile-->
                <?php
                $colorAux = "bg-success";
                $textoAux = "Success";
                $largoAux = $largoReconcile;
                if ($largoReconcile < 2) {
                    $colorAux = "bg-warning";
                    $textoAux = "Warning";
                }
                if ($validarReconcile == 0) {
                    $colorAux = "bg-danger";
                    $textoAux = "Danger";
                    // ---- Insert Alerta
                    $mensajeAlerta = "Se detectó un error en el Reconciliador.  ";
                    $alertas->insertAlert($id_faena, "HCXAL001", $mensajeAlerta);
                    //--------------------
                }

                ?>

                <div class="col-lg-4 col-6" style="cursor: pointer" onclick="verInfo('divReconcileVista')">

                    <div class="small-box small-box-2  <?php echo $colorAux  ?>">
                        <div class="inner">
                            <h5>Reconcile <?php echo $system->validarLog($reconcile, 4, "right") ?></h5>
                            <p><?php echo $textoAux ?></p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i>
                        </div>
                    </div>
                </div>






            </div>
            <!---------------------------------------------- Fin row datos procesos ------------------------------------------------->
            <!----------------------------------------------------------------------------------------------------------------------->





            <!----------------------------------------------------Display vistas------------------------------------------------------------------->

            <div class="row" style="display:none">

                <!-- JAMS servidor activo -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="divReiniciosJams" tittle="JAMS servidor activo." style="overflow:auto;">
                            <div class="col-md-12">

                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($reiniciosJAMS as $x) {
                                        echo "<li>" . trim($x) . "</li>";
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- JAMS servidor activo -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="divJamsVista" tittle="JAMS servidor activo." style="overflow:auto;">
                            <div class="col-md-12">

                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($jamsActivoLog as $x) {
                                        echo "<li>" . trim($x) . "</li>";
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- JAMS servidor secundario -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="divJamsSecVista" tittle="JAMS servidor Backup." style="overflow:auto;">
                            <div class="col-md-12">

                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($jamsSec as $x) {
                                        $ProcesoJammsSec = $jamsSec;
                                        echo "<li>" . trim($x) . "</li>";
                                    }   ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Importadores  div donde se muestra el log -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="divImpoVista" tittle="Procesos de importadores." style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php
                                    $caux = 0;
                                    $sw_impo_pegado = 0;



                                    foreach ($impo as $x) {
                                        $importadores = $impo;
                                        if ($caux == 0) {
                                            echo trim($x) . "<br><br>";
                                        } else {

                                            $parts = explode(' ', $x);
                                            $parts = array_filter($parts, function ($part) {
                                                return trim($part) !== '';
                                            });
                                            $parts = array_values($parts);
                                            $dataTimeEjecucion = $parts[8];
                                            $currentTime = new DateTime();



                                            if (strpos($dataTimeEjecucion, ':') !== false) {
                                                $logTime = DateTime::createFromFormat('H:i', $dataTimeEjecucion);
                                                $logTime->setDate($currentTime->format('Y'), $currentTime->format('m'), $currentTime->format('d'));
                                            } else {
                                                // Verificar si $dataTimeEjecucion es una fecha válida (YYYY-MM-DD)
                                                $logTime = DateTime::createFromFormat('Y-m-d H:i', $dataTimeEjecucion . ' ' . $parts[9]);
                                                if (!$logTime) {
                                                    // Caso fecha en otro formato, la hora está en $parts[9]
                                                    $logTime = DateTime::createFromFormat('M d H:i', $dataTimeEjecucion . ' ' . $parts[9]);
                                                }
                                            }



                                            $interval = $currentTime->diff($logTime);
                                            $minutesPassed = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
                                            $colorTiempoEjecucion = "yellow";




 
                                            if ($minutesPassed >= 10) {
                                                $colorTiempoEjecucion = "red";
                                                
                                                //echo "minutos pasados =" . $minutesPassed;
                                                $mensajeAlerta = "Se detectó un importador que lleva mas de 10 minutos ejecutandose ";
                                                $alertas->insertAlert($id_faena, "HCXIMP001", $mensajeAlerta);
                                                $sw_impo_pegado=1;
                                            }  



 



                                                echo implode(" ", array_slice($parts, 0, 7)) .
                                                " <span style='color:" . $colorTiempoEjecucion . "' class='ml-2'> " . $parts[8] . " </span> " .
                                                implode(" ", array_slice($parts, 9));

                                            # echo "<span style='color:red'class='ml-2'> Hora ejecución  : ".$dataTimeEjecucion."</span>";
                                            echo "<br>";
                                        }
                                        $caux++;
                                        #echo  "RMM@sistemaDeMonitoreo : ~ ". $lineaImportador . "<br>";
                                    }


                                    if ($sw_impo_pegado == 0) {
                                        //echo 'Dentro de los 10 minutos.';
                                        //Limpieza
                                        echo "limpieza - minutos pasados =" . $minutesPassed;
                                        $mensajeAlerta = "Se detectó un importador que lleva mas de 10 minutos ejecutandose ";
                                        $alertas->insertAlert($id_faena, "HCXIMP001", $mensajeAlerta, 1);
                                    }
                                    $caux = 0;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- SqlServer -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="divSqlServerVista" tittle="Procesos SQL Server." style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($sqlServer as $x) {
                                        $sqlServer = $sqlServer;;
                                        if ($caux == 0) {
                                            echo trim($x) . "<br><br>";
                                        } else {

                                            $parts = explode(' ', $x);
                                            $parts = array_filter($parts, function ($part) {
                                                return trim($part) !== '';
                                            });
                                            $parts = array_values($parts);
                                            $dataTimeEjecucion = $parts[8];

                                            echo implode(" ", array_slice($parts, 0, 7)) .
                                                " <span style='color:yellow' class='ml-2'> " . $parts[8] . " </span> " .
                                                implode(" ", array_slice($parts, 9));

                                            # echo "<span style='color:red'class='ml-2'> Hora ejecución  : ".$dataTimeEjecucion."</span>";
                                            echo "<br>";
                                        }
                                        $caux++;
                                    }  ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- sql_back -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="divSqlBackVista" tittle="Procesos sql_back." style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($sql_Back as $x) {
                                        $sql_Back = $sql_Back;;
                                        $sqlServer = $sqlServer;;
                                        if ($caux == 0) {
                                            echo trim($x) . "<br><br>";
                                        } else {

                                            $parts = explode(' ', $x);
                                            $parts = array_filter($parts, function ($part) {
                                                return trim($part) !== '';
                                            });
                                            $parts = array_values($parts);
                                            $dataTimeEjecucion = $parts[8];

                                            echo implode(" ", array_slice($parts, 0, 7)) .
                                                " <span style='color:yellow' class='ml-2'> " . $parts[8] . " </span> " .
                                                implode(" ", array_slice($parts, 9));

                                            # echo "<span style='color:red'class='ml-2'> Hora ejecución  : ".$dataTimeEjecucion."</span>";
                                            echo "<br>";
                                        }
                                        $caux++;
                                    }  ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Summarizador -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="divSummVista" tittle="proceso de Sumarizador " style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($sumarizadorLog as $x) {

                                        echo " <li>" . trim($x) . "</li>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NTP -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="divNtpVista" tittle="NTP" style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($Ntp as $x) {
                                        $Ntp   = $Ntp;
                                        echo " <li>" . trim($x) . "</li>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Reconcile -->
                <div class="row">
                    <div class="col-md-12">
                        <b class="d-block">Reconcile: <?php echo $largoReconcile ?> </b>
                        <button type="button" class="btn btn-outline-info" onclick="verInfo('divReconcileVista')">Ver Info</button>
                        <div class="row" id="divReconcileVista" tittle="Reconcilie" style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($reconcile as $x) {
                                        $reconcile = $reconcile;;
                                        echo " <li> " . trim($x) . "</li>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <!--BackupSec-->
                <div class="row">
                    <div class="col-md-12">
                        <b class="d-block">Backup Secundario</b>
                        <button type="button" class="btn btn-outline-info" onclick="verInfo('divBackupSecVista')">Ver Info</button>
                        <div class="row" id="divBackupSecVista" tittle="Backup Secundario" style="overflow:auto;">
                            <div class="col-md-12">
                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                    <?php foreach ($BackupSec as $x) {
                                        $BackupSec = $BackupSec;;
                                        echo " - " . trim($x) . "<br>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>


    </div>

</div>







<script>
    $("#divJamsVista").fadeOut()
    $("#divImpoVista").fadeOut()
    $("#divSqlServerVista").fadeOut()
    $("#divSqlBackVista").fadeOut()
    $("#divSummVista").fadeOut()
    $("#divNtpVista").fadeOut()
    $("#divReconcileVista").fadeOut()
    $("#divBackupSecVista").fadeOut()
    $("#divJamsSecVista").fadeOut()


    function verInfo(id) {


        // $("#" + id + "").toggle() //cambiar luego a fadeIn
        $("body").append("<span id='btnModalFaena' data-toggle='modal' data-target='#modalLarge'>  </span>");
        $("#btnModalFaena").click();
        $("#btnModalFaena").remove();
        $("#modalLargeBody").html($("#" + id).html());
        $("#modalLargeTittle").html($("#" + id).attr("tittle"));
    }


    function vistaTerminal(contjson, titulo) {
        alert("Función vistaTerminal() llamada");

        var lista = "<ul>";
        contenido = JSON.parse(contjson);
        contenido.forEach(function(item) {
            lista += "<li>" + item + "</li>";
        });
        lista += "</ul>";

        alert(lista)

        $("body").append("<span id='btnModalFaena' data-toggle='modal' data-target='#modalLarge'>  </span>");
        $("#btnModalFaena").click();
        $("#btnModalFaena").remove();

        $("#modalLargeBody").html(lista);
        $("#modalLargeTittle").html(titulo);





    }
</script>