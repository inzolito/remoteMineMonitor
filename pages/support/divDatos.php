<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();
$id_faena = $_REQUEST["id"];

date_default_timezone_set('America/Santiago');

//Falta validar cuando no existe la faena
$faenaDatos = $fenaCl->datos($id_faena);
$estadoCheckFaena = $fenaCl->estado($id_faena);
$checkDatos = $fenaCl->datosCheck($id_faena);

$carpeta = $faenaDatos->alias . "/";
$ruta = "/home/jigsaw/monitoreoRemoto/" . $carpeta;



// ------------------------------------------------

$sumarizadorPrimario = file($ruta . "SummarizerMon.log");
$dailyServidorSecundario = file($ruta . "DailySecMon.log");
$Rlm = file($ruta . "RlmMon.log");
$SizeLog = file($ruta . "SizeLogMon.log");
$SizeLogSec = file($ruta . "SizeLogSecMon.log");
$dailyFecha = file($ruta . "DailyFechaSecMon.log");
$consultaIdle = file($ruta . "consultasIdle.log");
$proceSumarizador = file($ruta . "ProcesosSumarizadorMon.log");
$procSumCrontab = file($ruta . "crontabSummMon.log");
$equiposConectados = file($ruta . "RepcMon.log");
$pingEstacionBase = file($ruta . "pingEstacionBase.log");
$estadoDisco = file($ruta . "estadoServidorMon.log");
$estadoDiscoSecundario = file($ruta . "estadoServidorSecMon.log");
$tamanoArchivos = file($ruta . "SizeLogTotalMon.log");
$tamanoArchivosSec = file($ruta . "SizeLogTotalSecMon.log");
$jamsCluster = file($ruta . "ProcesosJamsMon.log");
$jamsClusterSec = file($ruta . "ProcesosJamsSecMon.log");
$schemaInfoSec = file($ruta . "schemaInfoSec.log");
$schemaInfoAct = file($ruta . "schemaInfo.log");

//nombre del servidor primario activo
$nombreServidorActivo = $estadoDisco[2];

//nombre del servidor secundario
$nombreServidorSecundario = $estadoDiscoSecundario[2];

//sumarizador 
$largoSum = count($sumarizadorPrimario);
$pos = strpos(strtolower($sumarizadorPrimario), " error ");
$validarSumm = 0;
$fechaActual = date("Y-m-d H:i:00");

$fechaFormateada = date("d H:i");

for ($x = $largoSum; $x > 0; $x--) {
    $pos = strpos(strtolower($sumarizadorPrimario[$x]), " error ");
    if ($pos == 0) {
        //$obtenerFecha = explode(" at ", $sumarizadorPrimario[$x + 1]);
        //(count($obenerFecha) > 0) ? $obenerFecha : $obtenerFecha = explode(" at ", $sumarizadorPrimario[$x - 1]);
        //$FechaSumm = substr($obtenerFecha[1], 0, 19); // la del explode (), preguntar con x+1 para encontrar la fecha y x-1 // isset($variable) = preguntar si la variable existe <revision>

        //if ($FechaSumm == 0 || $FechaSumm == "") {
        $validarSumm = 1;
        $x = 1;
    } else {

        /* $diff= $fechaActual-$FechaSumm;
            if($diff<60){
                $validarSumm=1;
                //echo "valido<br>";
                $x=0;
            }else{
                $validarSumm=2;
                //echo "no valido";
                $x=0;
            }*/ //<revision> validar fecha correcta ya que de momento no se logra 

        $validarSumm = 0;
        $x = 0;
    }

    if ($validarSumm == 1) {
        $validarSumm = "OK";
    } else {
        $validarSumm = "Warning";
    }
}


//daily Estado
$largoDaily = count($dailyServidorSecundario);
$posDaily = strpos($dailyServidorSecundario, " rows for workers");
$validarDaily = 0;
for ($x = $largoDaily; $x > 0; $x--) {
    $posDaily = strpos($dailyServidorSecundario[$x], " rows for workers");
    if ($posDaily > 0) {
        if (count($dailyServidorSecundario[$x]) > 0) {
            $validarDaily = 1;
            $x = 0;
        } else {
            $validarDaily = 2;
            $x = 0;
        }
        if ($validarDaily == 1) {
            $validarDaily = "OK";
        } elseif ($validarDaily == 2) {
            $validarDaily = "Mal";
        } else {
            $validarDaily = "Sin datos o datos incorrectos";
        }
    }
}

//obtener datos necesarios del daily  
$contador = 0;
$largoDailyFecha = count($dailyFecha);
$fehcaActualSH = date("Y-m-d");
$validarDailyDiario = 0;

if ($carpeta == "magsa/") {
    for ($x = $largoDailyFecha; $x > 0; $x--) {
        if ($contador == 1) {
            $ultimoDaily = $dailyFecha[$x];
            $x = -1;
        }
        $contador = +1;
    }
} else {
    for ($x = $largoDailyFecha; $x > 0; $x--) {
        if ($contador == 1) {
            $ultimoDaily = explode("jigsaw", $dailyFecha[$x]);
            $ultimoDaily = $ultimoDaily[2];
            $x = -1;
        }
        $contador = +1;
    }
}

if ($carpeta == "cndrt/") {
    $fechaDaily = explode("-RT", $ultimoDaily);
    $fechaDaily = explode("prod-", $fechaDaily[0]);
    $fechaDaily = $fechaDaily[1];
} else if ($carpeta == "magsa/") {
    $fechaDaily = explode(".tgz", $ultimoDaily);
    $fechaDaily = explode("prod-", $fechaDaily[0]);
    $fechaDaily = $fechaDaily[1];
    $fechaLoadedDaily = $fechaDaily;
} else if ($carpeta == "cnms/") {
    $fechaDaily = explode("-MS", $ultimoDaily);
    $fechaDaily = explode("prod-", $fechaDaily[0]);
    $fechaDaily = $fechaDaily[1];
} else if ($carpeta == "cnchuq/") {
    $fechaDaily = explode("-CH", $ultimoDaily);
    $fechaDaily = explode("prod-", $fechaDaily[0]);
    $fechaDaily = $fechaDaily[1];
} else {
    $fechaDaily = explode(".tgz", $ultimoDaily);
    $fechaDaily = explode("jmineops-", $fechaDaily[0]);
    $fechaDaily = $fechaDaily[1];
}

if ($fechaDaily == $fehcaActualSH) {
    //    $validarDaily = 1;
    $validarDailyDiario = 1;
} else {
    //    $validarDaily = 2;
    $validarDailyDiario = 2;
}
//if ($validarDaily == 1) {
//    $validarDaily = "OK";
//} elseif ($validarDaily == 2) {
//    $validarDaily = "Mal";
//} else {
//    $validarDaily = "Sin datos o datos incorrectos";
//}
if ($validarDailyDiario == 1) {
    $validarDailyDiario = "OK";
} elseif ($validarDailyDiario == 2) {
    $validarDailyDiario = "Mal";
} else {
    $validarDailyDiario = "Sin datos o datos incorrectos";
}

$fechaLoadedDaily = explode("G", $ultimoDaily);
$fechaLoadedDaily = explode("jmineops", $fechaLoadedDaily[1]);
$fechaLoadedDaily = $fechaLoadedDaily[0];

//count Consulta Idle
$largoIdle = count($consultaIdle);
if ($largoIdle < 2) {
    $cantidadIdle = $largoIdle - 1;
} else {
    $cantidadIdle = $largoIdle - 5;
}

if ($cantidadIdle >= 20) {
    $validarIdle = 1;
} else {
    $validarIdle = 2;
}

if ($validarIdle == 1) {
    $validarIdle = "MAL";
} else {
    $validarIdle = "OK";
}

//validacion Size log
$largoSizeLog = count($SizeLog);
$valorAux = 0;
for ($x = 1; $x < $largoSizeLog; $x++) {
    $validarSizeLog = explode("G", $SizeLog[$x]);
    $validarSizeLog = $validarSizeLog[0];
    if ($validarSizeLog >= 1) {
        $valorAux = 1;
        $x = $x + $largoSizeLog;
    } else {
        $valorAux = 2;
    }
}

//validar RLM
$largoRlm = count($Rlm);
if ($carpeta == "amant/" || $carpeta == "mlcc/") {
    for ($x = 1; $x < $largoRlm - 1; $x++) {
        $validarRlm = explode("M", $Rlm[$x]);
        $validarRlm = $validarRlm[0];
    }
} else {
    for ($x = 1; $x < $largoRlm; $x++) {
        $validarRlm = explode("M", $Rlm[$x]);
        $validarRlm = $validarRlm[0];
    }
}

//largo Proceso sumarizador
$largoSum = count($proceSumarizador);
$largoValidarCrontab = count($procSumCrontab);
$validarAux = 0;

//largo Repc
$largoRepc = count($equiposConectados) - 2;

//validar Repc
if ($largoRepc < 1) {
    $validarRepc = "Mal";
} else {
    $validarRepc = "OK";
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
$fechaActualVisual = "<i class='fas fa-calendar'></i>" . date("d");
$horaActualVsual = "<i class='fas fa-clock ml-1'></i>" . date("H:i");
$dataTimeVisual = $fechaActualVisual . " " . $horaActualVsual;

//arreglo ping estacion base
$largoEstacionBase = count($pingEstacionBase);

//validacion de ping estacion base
$validarPing = explode("%", $pingEstacionBase[9]);
$validarPing = explode(",", $validarPing[0]);
$validarPing = $validarPing[2];

if ($validarPing < 50 && $validarPing != "") {
    $validarAuxPing = 1;
} else {
    $validarAuxPing = 2;
}


//validar jams cluster primario
$largoJamscluster = count($jamsCluster);

for ($x = 0; $x < $largoJamscluster; $x++) {
    $clusterPrimario = explode("JAMSCluster status ", $jamsCluster[$x]);
    if (count($clusterPrimario) > 1) {
        $clusterPrimario = explode(" ", $clusterPrimario[1])[0];
        $x = 10000;
    }
    // obtengo el resultado duplicado debido a como se formula      
}

//Jam cluster secundario
$largoJamsclusterSec = count($jamsClusterSec);

for ($x = 0; $x < $largoJamsclusterSec; $x++) {
    $clusterSecundario = explode("JAMSCluster status ", $jamsClusterSec[$x]);
    if (count($clusterSecundario) > 1) {
        $clusterSecundario = explode(" ", $clusterSecundario[1])[0];
        $x = 10000;
    }
}


//validar clusterprimario y sec
if ($carpeta == "cndmh/") {
    $validarClusterPrimario = 1;
} else if ($clusterPrimario == $nombreServidorSecundario) {
    $validarClusterPrimario = 1;
} else {
    $validarClusterPrimario = 2;
}

if ($clusterSecundario == $nombreServidorActivo) {
    $validarClusterSecundario = 1;
} else {
    $validarClusterSecundario = 2;
}


if ($validarClusterPrimario == 1 && $validarClusterSecundario == 1) {
    $ver = "<i class='fa-solid fa-spell-check' style = 'font-size: 1.5em;'></i>";
    $colorCluster = "badge bg-success p-2 btn-block pt-0 pb-0";
    $mensajeCuster = "OK";
} else {
    $ver = "<i class='fa-solid fa-ban' style = 'font-size: 1.5em;'></i>";
    $colorCluster = "badge bg-danger p-2 btn-block pt-0 pb-0";
    $mensajeCuster = "Danger";
}


//cambio de color boton IDLE
switch ($validarIdle) {
    case "OK":
        $botonIdle = "btn btn-success btn-block btn-sm pt-0 pb-0";
        break;
    default:
        $botonIdle = "btn btn-danger btn-block pt-0 pb-0";
}

//Validar ping
switch ($validarAuxPing) {
    case 2:
        $colorValidar = "badge bg-danger p-2 btn-block";
        $mensajeValidacion = "Offline";
        break;
    default:
        $colorValidar = "badge bg-success p-2 btn-block";
        $mensajeValidacion = "Online";
}

//cambio color Rlm
switch (true) {
    case ($validarRlm >= 700 && $validarRlm <= 899):
        $ColorTextoRlm = "badge bg-warning p-2 btn-block";
        $mensajeRlm = "Warning";
        break;
    case ($validarRlm >= 900  || $validarRlm == ""):
        $ColorTextoRlm = "badge bg-danger p-2 btn-block";
        $mensajeRlm = "Danger";
        //echo '<audio autoplay>';
        //echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
        //echo '</audio>';
        break;
    default:
        $ColorTextoRlm = "badge bg-success p-2 btn-block";
        $mensajeRlm = "OK";
}

// validacion Daily diario
switch ($validarDailyDiario) {
    case "OK":
        $colorDailyDiario = "badge bg-success p-2";
        break;
    default:
        $colorDailyDiario = "badge bg-danger p-2";
}

//validar estado sumarizador
switch ($validarSumm) {
    case "Warning":
        $colorEstadoSum = "badge bg-warning p-2";
    default:
        $colorEstadoSum = "badge bg-success p-2";
}

// validar Repc
switch ($validarRepc) {
    case "Mal":
        $mensajeRepc = "No se encontraron datos";
        $botonRepc = "btn btn-block btn-default disabled btn-sm pt-0 pb-0";
        $clickBotonRepc = "";
        break;
    case "OK":
        $mensajeRepc = "La cantidad es: " . $largoRepc;
        $botonRepc = "btn btn-success btn-block btn-sm pt-0 pb-0";
        $clickBotonRepc = "verInfo('divRepcVista')";
        break;
}


//cambiar color div tamaño logs 
$largoTamanoLogsAct = count($tamanoArchivos);
$largoTamanoLogsSec = count($tamanoArchivosSec);
$fechaAct = $tamanoArchivos[0];

if ($largoTamanoLogsAct >= 2 || $largoTamanoLogsSec >= 2) {
    $colorbordeTamanoLogs = "card card-navy";
} else {
    $colorbordeTamanoLogs = "card card-light";
}

// validar  sumarizador estado

if (strpos($sumarizadorPrimario, " JAMS: Shutting down") !== false) {
    $estado = "JAMS: Shutting down";
} else if (stripos($sumarizadorPrimario, "kill") !== false) {
    $estado = "Kill";
    //echo '<audio autoplay>';
    //echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
    //echo '</audio>';
} else if (strpos($sumarizadorPrimario, "summaries with interval") !== false) {
    $estado = "OK";
}

//validar schema info
$largoSchemaInfo = count($schemaInfoSec);
$largoSchemaInfoAct = count($schemaInfoAct);


?>




<div class="row">
    <div class="col-md-6">
        <div class="card card-navy">
            <div class="card-header">
                <h3 class="card-title">Datos</h3>
                <div class="card-tools">
                    <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <table class="table table-bordered">
                        <tbody>
                            <?php
                            if ($carpeta == "aas/" || $carpeta == "crsten/") {
                            } else { ?>
                                <tr>
                                    <td>
                                        <?php
                                        echo $validScriptRlm = $system->validarLog($Rlm, 20);
                                        if (strpos($validScriptRlm, "text-danger") !== false) $ColorTextoRlm = "badge btn-default disabled p-2 btn-block";

                                        ?>
                                        Rlm
                                    </td>
                                    <td>
                                        <?php
                                        $primeraLinea = true;
                                        foreach ($Rlm as $x) {
                                            if ($primeraLinea) {
                                                $primeraLinea = false;
                                                continue;
                                            }

                                            $Rlm = $Rlm;
                                            $largoRlm = count($Rlm);

                                            if ($largoRlm <= 1) {
                                                $Rlm = 1;
                                                if ($Rlm == 1) {
                                                    $Rlm = "No se encontraron datos";
                                                }
                                                echo $Rlm;
                                            } else {
                                                echo "<li>" . trim($x) . "</li>";
                                            }
                                        }

                                        ?>
                                    </td>
                                    <td>
                                        <span class="<?php echo $ColorTextoRlm ?> "> <?php echo $mensajeRlm ?> </span>
                                    </td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td>
                                    <?php
                                    echo $validScriptEb = $system->validarLog($pingEstacionBase, 12);
                                    if (strpos($validScriptEb, "text-danger") !== false) $colorValidar  = "badge btn-default disabled p-2 btn-block";
                                    ?>
                                    Estacion base
                                </td>
                                <td><?php for ($x = 0; $x < $largoEstacionBase; $x++) {
                                        $ipEstacionBase = explode("PING", $pingEstacionBase[$x]);
                                        $ipEstacionBase = explode("(", $ipEstacionBase[1]);
                                        print_r($ipEstacionBase[0]);
                                    } ?>
                                </td>
                                <td> <span class="<?php echo $colorValidar ?>"> <?php echo $mensajeValidacion ?> </span></td>
                            </tr>

                            <tr>
                                <td>
                                    <?php echo $validScriptIdle = $system->validarLog($consultaIdle, 10);
                                    if (strpos($validScriptIdle, "text-danger") !== false) $botonIdle  = "btn btn-block btn-default disabled btn-sm pt-0 pb-0";

                                    ?>
                                    Consultas PostGres Pegadas (IDLE)</td>
                                <td> La cantidad es:
                                    <?php
                                    echo $cantidadIdle
                                    ?>
                                </td>

                                <td>
                                    <button type="button" id="btnIdle" onclick="verInfo('divIdleVista')" class="<?php echo $botonIdle ?>"> <i class='fa-solid fa-eye mr-1'></i></button>
                                    <?php
                                    if ($cantidadIdle >= 20) {
                                        //descomentar una vez este todo listo.
                                        //$message = "Problemas con las consultas IDLE en ".$faenaDatos->alias." cantidad: ".$cantidadIdle;
                                        //echo $system->enviarMensajeTelegram($message);

                                        //$subject = 'Problemas con ' . $carpeta;
                                        //$message = 'Problemas con la cantidad de consultas en IDLE: ' . $cantidadIdle;
                                        //enviarEmail($subject, $message);


                                    ?>
                                        <script>
                                            //agregarAlertaFaena("exceso consultas en idle","Demaciadas consultas en estado IDLE","error"); 
                                        </script>
                                    <?php
                                    } else {
                                    ?>
                                        <script>
                                            //eliminarAlertaFaena("exceso consultas en idle"); 
                                        </script>
                                    <?php
                                    }
                                    ?>
                                </td>
                                <div style="display:none">
                                    <div class="row" id="divIdleVista" tittle="Idle" style="overflow:auto;">
                                        <div class="col-md-12">
                                            <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                                <?php foreach ($consultaIdle as $x) {
                                                    $consultaIdle = $consultaIdle;;
                                                    echo "<li>" . trim($x) . "</li>";
                                                }
                                                echo "================ Comando para eliminar ==================" . "<br>";
                                                $tablaAux = "";
                                                $largoConsultaIdle = count($consultaIdle);
                                                for ($x = 3; $x < $largoConsultaIdle - 2; $x++) {
                                                    $tablaAux = explode("|", $consultaIdle[$x]);
                                                    $consultaBorrarIdle = "SELECT pg_terminate_backend($tablaAux[0]);";
                                                    echo $consultaBorrarIdle . "<br>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </tr>

                            <tr>
                                <td>
                                    <?php echo $validScriptRepc = $system->validarLog($equiposConectados, 20);
                                    if (strpos($validScriptRepc, "text-danger") !== false) $botonRepc  = "btn btn-block btn-default disabled btn-sm pt-0 pb-0";

                                    ?>
                                    Equipos conectados</td>
                                <td>
                                    <?php
                                    echo $mensajeRepc;
                                    ?>
                                </td>
                                <td>
                                    <button type="button" id="btnIdle" onclick="<?php echo $clickBotonRepc ?>" class="<?php echo $botonRepc ?>"> <i class='fas fa-eye fa-solid mr-1 fa-eye'></i></button>
                                </td>
                                <div style="display:none">
                                    <div class="row" id="divRepcVista" tittle="Repc" style="overflow:auto;">
                                        <div class="col-md-12">
                                            <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                                <?php foreach ($equiposConectados as $x) {
                                                    $equiposConectados = $equiposConectados;;
                                                    echo "<li>" . trim($x) . "</li>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </tr>

                            <tr>
                                <td>
                                    <?php echo $validScriptJamsC = $system->validarLog($jamsCluster, 10);
                                    if (strpos($validScriptJamsC, "text-danger") !== false) $colorCluster  = "badge btn-default disabled p-2 btn-block";

                                    ?>

                                    JamsCluster (corriendo)
                                </td>
                                <td>
                                    <?php echo "Desde: " . $nombreServidorActivo . "<i class='fa-solid fa-arrow-right'></i>" . $clusterPrimario ?><br>
                                    <?php echo "Desde: " . $nombreServidorSecundario . "<i class='fa-solid fa-arrow-right'></i>" . $clusterSecundario; ?>
                                </td>
                                <td>
                                    <span class="<?php echo $colorCluster ?> "> <?php echo $mensajeCuster ?> </span>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <?php echo $validarSchemaInfo = $system->validarLog($schemaInfoSec, 8);
                                    if (strpos($validarSchemaInfo, "text-danger") !== false) $color  = "badge btn-default disabled p-2 btn-block";

                                    ?>

                                    Schema Info
                                </td>
                                <td>
                                    <?php

                                    if ($carpeta == "cndmh/") {
                                        for ($x = 0; $x < $largoSchemaInfoAct; $x++) {
                                            $schemaInfo = explode(" |", $schemaInfoAct[$x]);
                                            $schemaInfo = $schemaInfo[5];
                                            $schemaInfo = explode(" ", $schemaInfo);
                                            $schemaInfo = $schemaInfo[1] . " " . $schemaInfo[2];
                                            if ($largoSchemaInfoAct >= 2) {
                                                $mensajeSchema = "OK";
                                                $color = "badge bg-success p-2 btn-block pt-0 pb-0";
                                                print_r($schemaInfo);
                                            } else {
                                                $mensajeSchema = "Warning";
                                                $color = "badge bg-warning p-2 btn-block pt-0 pb-0";
                                                echo "Sin acceso";
                                            }
                                        }
                                    } else {
                                        for ($x = 0; $x < $largoSchemaInfo; $x++) {
                                            $schemaInfo = explode(" |", $schemaInfoSec[$x]);
                                            $schemaInfo = $schemaInfo[5];
                                            $schemaInfo = explode(" ", $schemaInfo);
                                            $schemaInfo = $schemaInfo[1] . " " . $schemaInfo[2];;
                                            if ($largoSchemaInfo >= 2) {
                                                $mensajeSchema = "OK";
                                                $color = "badge bg-success p-2 btn-block pt-0 pb-0";
                                                print_r($schemaInfo);
                                            } else {
                                                $mensajeSchema = "Warning";
                                                $color = "badge bg-warning p-2 btn-block pt-0 pb-0";
                                                echo "Sin acceso";
                                            }
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="<?php echo $color ?> "> <?php echo $mensajeSchema ?> </span>
                                </td>
                            </tr>

                        </tbody>
                    </table>

                </div>

            </div>


        </div>
    </div>

    <div class="col-md-6">
        <div class="row">
            <!-- Sumarizador -->

            <div class="col-md-12">
                <div class='<?php echo $system->pintarDiv($largoSum) ?>'>
                    <div class="card-header">
                        <h3 class="card-title">Sumarizador <?php echo $validScriptSum = $system->validarLog($sumarizadorPrimario, 10);
                                                            if (strpos($validScriptSum, "text-danger") !== false) $colorEstadoSum  = "badge disabled p-2";
                                                            ?></h3>
                        <div class="card-tools">
                            <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual ?></span>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-4">
                                <p class="text-sm ">Corriendo como :
                                    <b class=""><br>
                                        <?php

                                        $corriendoComoAux = "Servicio";
                                        $clasCrontab = "collapse";

                                        // corriendo comos servicio - 
                                        $largoSum = count($proceSumarizador);
                                        $validarSumarizador = array(0, 0, 0);
                                        for ($x = 0; $x < $largoSum; $x++) {
                                            $proceSumarizadorArray = explode("/opt/Jigsaw/Services/JAMSSummarizer start", $proceSumarizador[$x]);
                                            if (count($proceSumarizadorArray) > 1) {
                                                $validarSumarizador[0] = 1;
                                                break;
                                            }
                                        }
                                        // Corriendo como crontab - se pregunta por el proceso  descomentado en cron
                                        $largoSum = count($procSumCrontab);
                                        for ($x = 0; $x < $largoSum; $x++) {
                                            $proceSumarizadorArray = explode("/opt/Jigsaw/Tools/Summarizer", $procSumCrontab[$x]);
                                            if (count($proceSumarizadorArray) > 1) {
                                                $validarCrontab = $procSumCrontab[$x];
                                                $validarCrontab  = substr($validarCrontab, 0, 1);
                                                if ($validarCrontab == "*") {
                                                    $clasCrontab = "";
                                                    $validarSumarizador[1] = 1;
                                                    $corriendoComoAux = "Crontab";
                                                    break;
                                                }
                                            }
                                        }

                                        //Corriendo manual en este momento , excluimos el date -d que es la variable del sumarizador crontab,
                                        //Se pregunta solo si esta ejecutandose de forma manual, puede q el proceso no este comentado en cron
                                        $largoSum = count($proceSumarizador);
                                        $contCantidadSumarizaciones = 0;
                                        $contDateD = 0;
                                        for ($x = 0; $x < $largoSum; $x++) {
                                            $proceSumarizadorArray = explode("/opt/Jigsaw/Tools/Summarizer -force", $proceSumarizador[$x]);
                                            $valorDateD = explode("date -d", $proceSumarizador[$x]);
                                            if (count($proceSumarizadorArray) > 1) {
                                                $contCantidadSumarizaciones++;
                                            }
                                            if (count($valorDateD) > 1) {
                                                $contDateD++;
                                            }
                                        }

                                        if ($contCantidadSumarizaciones == 1 && $contDateD == 0) {
                                            $validarSumarizador[3] = 1;
                                            $corriendoComoAux = "Manual";
                                        }
                                        if ($contCantidadSumarizaciones >= 1 && $contDateD == 1) {
                                            $validarSumarizador[3] = 1;
                                            $validarSumarizador[1] = 1;
                                            $corriendoComoAux = "Crontab";
                                        }


                                        switch (true) {
                                            case $validarSumarizador[0] == 1:
                                                echo "Servicio";
                                                break;
                                            case $validarSumarizador[1] == 1 || $validarSumarizador[1] == 1 && $validarSumarizador[3] == 1:
                                                echo "Crontab";
                                        ?>
                                                <button type="button" class="btn-xs btn-outline-info ml-1" onclick="verInfo('divCrontabVista')"><i class='fa-solid fa-eye m-1'></i></button>
                                                <div style="display:none">
                                                    <div class="row" id="divCrontabVista" tittle="Crontab" style="overflow:auto;">
                                                        <div class="col-md-12">
                                                            <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                                                <?php foreach ($procSumCrontab as $x) {
                                                                    $procSumCrontab = $procSumCrontab;

                                                                    echo "<li>" . trim($x) . "</li>";
                                                                }   ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                        <?php

                                                break;
                                            case   $validarSumarizador[3] == 1:
                                                echo "Manual";
                                                $validarSumm = "Warning";
                                                $colorEstadoSum = "badge bg-warning p-2";
                                                break;
                                            default:
                                                echo "Detenido";
                                                $validarSumm = "Danger";
                                                $colorEstadoSum = "badge bg-danger p-2";
                                        }

                                        ?>

                                    </b>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p class="text-sm  ">Estado:<br>
                                    <span class="<?php echo $colorEstadoSum ?>"> <?php echo $validarSumm ?> </span>
                                    <?php
                                    //$message = "Problemas con el Sumarizador en ".$carpeta;
                                    //$system ->  enviarMensajeTelegram($message);

                                    //$subject = 'Problemas con ' . $carpeta;
                                    //$message = 'Problemas con el Sumarizador';
                                    //enviarEmail($subject, $message);
                                    ?>
                                </p>

                            </div>
                            <div class="col-md-4">

                                <button type="button" class="btn btn-outline-info" onclick="verInfo('divSumarizadorVista')">Ver Info</button>
                                <div style="display:none">
                                    <div class="row" id="divSumarizadorVista" tittle="Sumarizador" style="overflow:auto;">
                                        <div class="col-md-12">
                                            <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                                <?php foreach ($sumarizadorPrimario as $x) {
                                                    $sumarizadorPrimario = $sumarizadorPrimario;
                                                    echo " - " . trim($x) . "<br>";
                                                }   ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Daily -->

                <div class="col-md-12">
                    <div class='<?php echo $system->pintarDiv($largoDaily) ?>'>
                        <div class="card-header">
                            <h3 class="card-title">Daily <?php echo $validScriptDaily = $system->validarLog($dailyServidorSecundario, 10);
                                                            if (strpos($validScriptDaily, "text-danger") !== false) $colorDailyDiario  = "badge disabled p-2";
                                                            ?></h3>
                            <div class="card-tools">
                                <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual ?></span>
                            </div>
                        </div>

                        <div class="card-body">


                            <div class="row">
                                <div class="col-md-4">
                                    <p class="text-sm  ">último daily :
                                        <b class="d-block"><i class="far fa-save"></i><?php echo $ultimoDaily   ?></b>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p class="text-sm  ">Backup Diario:
                                        <b class="d-block">
                                            <td> <span class="<?php echo $colorDailyDiario ?>"> <?php echo $validarDailyDiario ?></span> </td>
                                            <?php
                                            //$message = "Problemas con el Daily en ".$carpeta;
                                            //enviarMensajeTelegram($message);

                                            //$subject = 'Problemas con ' . $carpeta;
                                            //$message = 'Error en el Daily de manera periodica';
                                            //enviarEmail($subject, $message);
                                            if ($validarDailyDiario != "OK") {
                                                $system->alertaSonora(120000);
                                            }

                                            ?>
                                        </b>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-info" onclick="verInfo('divDailyVista')">Ver Info</button>
                                </div>
                            </div>
                            <div style="display:none">
                                <div class="row" id="divDailyVista" tittle="Daily" style="overflow:auto;">
                                    <div class="col-md-12">
                                        <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;">
                                            <?php foreach ($dailyServidorSecundario as $x) {
                                                $dailyServidorSecundario = $dailyServidorSecundario;
                                                echo " - " . trim($x) . "<br>";
                                            }   ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--- archivos mas pesados---->
                <div class="col-md-12">
                    <div class='<?php echo $colorbordeTamanoLogs ?>'>
                        <div class="card-header">
                            <h3 class="card-title">Archivos más Pesados <?php echo $system->validarConexion($tamanoArchivos, $tamanoArchivosSec, 20) ?></h3>
                            <div class="card-tools">
                                <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual ?></span>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <table style="word-wrap: break-word; table-layout: fixed;" class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th style="width: 140px;">Servidor</th>
                                            <th> Tamaño Archivos</th>
                                        </tr>
                                        <tr>
                                            <th>
                                                <?php echo $system->validarLog($tamanoArchivos, 20); ?>
                                                <?php //echo print_r($tamanoArchivos); 
                                                ?>
                                                Primario</th>
                                            <td style="overflow-x: auto;">

                                                <?php
                                                $contLP = 0;
                                                $tamanoArchivos = $system->procesaLog($tamanoArchivos);

                                                $datatimeLogAux = $tamanoArchivos[0];
                                                $tamanoArchivos = $tamanoArchivos[1];
                                                $carpetaTamanoArchivoArray = array();
                                                $tamanoArchivosArray = array();


                                                $tituloAux = "";
                                                if (count($tamanoArchivos) == 0) {
                                                    echo "No se encontraron logs mayores a 1GB";
                                                } else {
                                                    foreach ($tamanoArchivos as $x) {


                                                        //if ($contLP == 0) echo "<ul>";
                                                        $contLP++;
                                                        $tamanoAux = 0;
                                                        $carpetaAux = "";
                                                        $archivoAux = "";
                                                        $logString = explode(" ", str_replace("/opt/Jigsaw/", "", trim($x)));
                                                        $tamanoAux = $logString[0];
                                                        $rutaArrayAux = explode("/", trim($x));
                                                        $carpetaAux = implode("/", array_slice($rutaArrayAux, 1, count($rutaArrayAux) - 2));
                                                        $archivoAux = $rutaArrayAux[count($rutaArrayAux) - 1];
                                                        if ($tituloAux != $carpetaAux) {
                                                            echo "<b>" . $carpetaAux . ":</b><br>";
                                                            $tituloAux = $carpetaAux;
                                                        }
                                                        echo "<li>" . $archivoAux . " = " . $tamanoAux . "</li>";
                                                    }
                                                }
                                                //if ($contLP > 0) echo "</ul>";  
                                                ?>
                                            </td>

                                        </tr>
                                        <tr>
                                            <th>
                                                <?php echo $system->validarLog($tamanoArchivosSec, 20); ?>
                                                Secundario</th>
                                            <td style="overflow-x: auto;">
                                                <?php
                                                $contLP = 0;
                                                $tamanoArchivosSec = $system->procesaLog($tamanoArchivosSec);
                                                $datatimeLogAux = $tamanoArchivosSec[0];
                                                $tamanoArchivosSec = $tamanoArchivosSec[1];
                                                $carpetaTamanoArchivoArray = array();
                                                $tamanoArchivosArray = array();


                                                $tituloAux = "";
                                                if (count($tamanoArchivosSec) == 0) {
                                                    echo "No se encontraron logs mayores a 1GB";
                                                } else {
                                                    foreach ($tamanoArchivosSec as $x) {


                                                        //if ($contLP == 0) echo "<ul>";
                                                        $contLP++;
                                                        $tamanoAux = 0;
                                                        $carpetaAux = "";
                                                        $archivoAux = "";
                                                        $logString = explode(" ", str_replace("/opt/Jigsaw/", "", trim($x)));
                                                        $tamanoAux = $logString[0];
                                                        $rutaArrayAux = explode("/", trim($x));
                                                        $carpetaAux = implode("/", array_slice($rutaArrayAux, 1, count($rutaArrayAux) - 2));
                                                        $archivoAux = $rutaArrayAux[count($rutaArrayAux) - 1];
                                                        if ($tituloAux != $carpetaAux) {
                                                            echo "<b>" . $carpetaAux . ":</b><br>";
                                                            $tituloAux = $carpetaAux;
                                                        }
                                                        echo "<li>" . $archivoAux . " = " . $tamanoAux . "</li>";
                                                    }
                                                }
                                                //if ($contLP > 0) echo "</ul>";  
                                                ?>
                                            </td>
                                        </tr>

                                    </tbody>


                                </table>
                            </div>

                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <script>
        $("#divSumarizadorVista").fadeOut()
        $("#divDailyVista").fadeOut()
        $("#divIdleVista").fadeOut()
        $("#divCrontabVista").fadeOut()
        $("#divRepcVista").fadeOut();
    </script>
    <style>
        td,
        th {
            white-space: nowrap;
            font-size: 14px;
            padding: 10px;
        }

        .fa-eye {
            font-size: 0.8em;
            /* Elige el tamaño que desees */
        }
    </style>