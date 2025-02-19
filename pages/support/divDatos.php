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

//nombre del servidor primario activo
$nombreServidorActivo = $estadoDisco[2];

//nombre del servidor secundario
$nombreServidorSecundario = $estadoDiscoSecundario[2];

//sumarizador 
$largoSum = count($sumarizadorPrimario);
$pos = strpos(strtolower($sumarizadorPrimario), " error ");
$validarSumm = 0;
//$fechaActual = date("Y-m-d H:i");

///opt/Jigsaw/Tools/Summarizer


$fechaFormateada = date("d H:i");
/*
for ($x = $largoSum; $x > 0; $x--) {
    $pos = strpos(strtolower($sumarizadorPrimario[$x]), " error ");
    if ($pos == 0) {  //si es falso (no hay error)
        $validarSumm = 1;
        $x = 1;
    } else {//caso contrario(se encuentra un error en los log)
        $validarSumm = 0;
        $x = 0;
    }

    if ($validarSumm == 1) {
        $validarSumm = "OK";
    } else {
        $validarSumm = "Warning";
    }
}
*/
//---------------------------------------------------Lógica NUEVA---------------
//variable para enviar a la fubnción pintarDiv y asignarle un color rojo en caso de error en los registros log
 

$errorLogSumarizador=0;
foreach ($sumarizadorPrimario as $linea){
    if (stripos($linea, 'error') !== false || stripos($linea, 'exception') !== false || stripos($linea, 'grouping') !== false) {
        $errorLogSumarizador=1;
        $validarSumm = "warning";
        break;
    }else{
        $validarSumm = "OK";
    }

}

//-------------------------------------------------------------------------------/*


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

/*
//---------------------------------------------------Logica NUEVA---------------

$largoDaily = count($dailyServidorSecundario);
$posDaily = strpos($dailyServidorSecundario, " rows for workers");
$validarDaily = 0;
$validarLogDaily=1;//1 cuando esta OK, 0 cuando no lo esta
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
            $validarLogDaily=1;
        } elseif ($validarDaily == 2) {
            $validarDaily = "Mal";
            $validarLogDaily=0;
        } else {
            $validarDaily = "Sin datos o datos incorrectos";
            $validarLogDaily=0;
        }
    }
}

//-------------------------------------------------------------------------------
*/

//obtener datos necesarios del daily  
$contador = 0;
$largoDailyFecha = count($dailyFecha);
//$fechaActual = new DateTime();


for ($x = $largoDailyFecha; $x > 0; $x--) {
    if ($contador == 1) {
        $ultimoDaily = explode("jigsaw", $dailyFecha[$x]);
        $ultimoDaily = $ultimoDaily[2];
        $x = -1;
    }
    $contador = +1;
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

// mejora fecha ultimo daily
$datatimeUltimoDailyArray = explode(" ", $ultimoDaily);
$cantCeldasUltimoDailyArray = count($datatimeUltimoDailyArray) - 1;
$fechaUltimoAray = explode("-", $datatimeUltimoDailyArray[$cantCeldasUltimoDailyArray]);
$diaUltimoDaily = substr($fechaUltimoAray[3], 0, 2);

$datatimeUltimoDaily = $fechaUltimoAray[1] . "-" . $fechaUltimoAray[2] . "-" . $diaUltimoDaily . " " . $datatimeUltimoDailyArray[$cantCeldasUltimoDailyArray - 1] . ":00";
//echo $ultimoDaily."<br>";
//echo $datatimeUltimoDaily."<br>";
$fechaActual = new DateTime();
$fechaUltimoDailyObj = DateTime::createFromFormat('Y-m-d H:i:s', $datatimeUltimoDaily);




if ($fechaUltimoDailyObj !== false) {
    // Calcular la diferencia entre las fechas
    $intervalo = $fechaActual->diff($fechaUltimoDailyObj);

    // Calcular la diferencia total en horas
    $diffHoras = ($intervalo->days * 24) + $intervalo->h + ($intervalo->i / 60);

    // Mostrar la diferencia en horas y minutos
    $stringDatatimeCreacionDaily = sprintf("Creado hace %.2f horas", $diffHoras);

    // Verificar si han pasado más de 25 horas
    if ($diffHoras > 25) {
        $validarDailyDiario = 0;
    } else {
        $validarDailyDiario = 1;
    }
} else {
    $validarDailyDiario = 0;
}






$fechaLoadedDaily = explode("G", $ultimoDaily);
$fechaLoadedDaily = explode("jmineops", $fechaLoadedDaily[1]);
$fechaLoadedDaily = $fechaLoadedDaily[0];



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
//print_r($procSumCrontab);
$largoSum = count($proceSumarizador);
$largoValidarCrontab = count($procSumCrontab);
$lineaCrontabSumarizador = explode("*", $procSumCrontab[1])[0];
$estadoCrontabSumarizador = 0;
if (preg_match("/#/", $lineaCrontabSumarizador)) {
    $estadoCrontabSumarizador = 0;
} else {
    $estadoCrontabSumarizador = 1;
}
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
//print_r($validarPing);
//echo $validarPing[count($validarPing) - 1];

$validarPing = intval(preg_replace("/\s/", "", $validarPing[count($validarPing) - 1]));

//echo $validarPing;
if ($validarPing <= 50) {
    $validarAuxPing = 1;
    //  echo "pasa";
} else {
    $validarAuxPing = 0;
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
    $colorCluster = "badge bg-warning p-2 btn-block pt-0 pb-0";
    $mensajeCuster = "Warning";
}



//Validar ping
switch ($validarAuxPing) {
    case 1:
        $colorValidar = "badge bg-success p-2 btn-block";
        $mensajeValidacion = "Online";
        //echo $alertas->alerta($id_faena,0,1,-1,0);
        break;
    default:
        $colorValidar = "badge bg-danger p-2 btn-block";
        $mensajeValidacion = "Offline";

        // ---- Insert Alerta
        $mensajeAlerta = "No hay ping a la estacion base.  ";
        $alertas->insertAlert($id_faena, "HCXAL001", $mensajeAlerta);
        //--------------------
}
//echo $validarRlm;
//cambio color Rlm


// Cambiar esto a consultar la version en la base de datos

switch (true) {
    case ($faenaDatos->alias == "amant" || $faenaDatos->alias == "amzal" || $faenaDatos->alias == "amcen"):
        $ColorTextoRlm = "badge bg-success p-2 btn-block";
        $mensajeRlm = "Version sin RLM";


        break;

    case ($validarRlm >= 700 && $validarRlm <= 899):
        $ColorTextoRlm = "badge bg-warning p-2 btn-block";
        $mensajeRlm = "Warning";
        break;
    case ($validarRlm >= 900):
        $ColorTextoRlm = "badge bg-danger p-2 btn-block";
        $mensajeRlm = "Danger";
        $alertas->insertAlert($id_faena, "HCXLR011", "Tamaño excedido en el rlm.log ");

        break;

    default:
        $ColorTextoRlm = "badge bg-success p-2 btn-block";
        $mensajeRlm = "OK";
}

// validacion Daily diario
switch ($validarDailyDiario) {
    case 1:
        $colorDailyDiario = "badge bg-success p-2";
        break;
    default:
        $colorDailyDiario = "badge bg-danger p-2";
        // ---- Insert Alerta
        $mensajeAlerta = "Se detectó que el daily no se realiza hace mas de 25 horas.  ";
        $alertas->insertAlert($id_faena, "HCXAL001", $mensajeAlerta);
        //--------------------
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

        // ---- Insert Alerta
        $mensajeAlerta = "No hay equipos conectados.";
        $alertas->insertAlert($id_faena, "HCXAL001", $mensajeAlerta);
        //--------------------


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
                                    <?php echo $validScriptRepc = $system->validarLog($equiposConectados, 20);
                                    if (strpos($validScriptRepc, "text-danger") !== false ||  $largoRepc < 1) $botonRepc  = "btn btn-block btn-danger  btn-sm pt-0 pb-0";

                                    ?>

                                    Equipos conectados </td>
                                <td>
                                    <?php
                                    echo $mensajeRepc;
                                    ?>
                                </td>
                                <td>
                                    <button type="button" id="btnRepc" onclick="<?php echo $clickBotonRepc ?>" class="<?php echo $botonRepc ?>"> <i class='fas fa-eye fa-solid mr-1 fa-eye'></i></button>
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
                                    <?php echo  $nombreServidorActivo . "<i class='fa-solid fa-arrow-right'></i>" . $clusterPrimario ?><br>
                                    <?php echo   $nombreServidorSecundario . "<i class='fa-solid fa-arrow-right'></i>" . $clusterSecundario; ?>
                                </td>
                                <td>
                                    <span class="<?php echo $colorCluster ?> "> <?php echo $mensajeCuster ?> </span>
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



            <!------------------------------------------------------------- Sumarizador -------------------------------------------------------->
            <div class="col-md-12">
                <div class='<?php echo $system->pintarDiv($largoSum,$errorLogSumarizador) ?>'>
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
                                            $proceSumarizadorArray = explode("/JAMSSummarizer start", $proceSumarizador[$x]);
                                            if (count($proceSumarizadorArray) > 1) {
                                                $validarSumarizador[0] = 1;
                                                break;
                                            }
                                        }


                                        // Corriendo como crontab - se pregunta por el proceso  descomentado en cron


                                        $estadoCrontabSumarizador = 0;

                                        foreach ($procSumCrontab as $linea) {
                                            $lineaLimpia = trim($linea);

                                            if (!empty($lineaLimpia) && $lineaLimpia[0] !== '#') {
                                                $clasCrontab = "";
                                                $validarSumarizador[1] = 1;
                                                $corriendoComoAux = "Crontab";
                                                break;
                                            }
                                        }
                                        // El valor de $estadoCrontabSumarizador será 1 si alguna línea está descomentada


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





                                        $largoSum = count($proceSumarizador);
                                        for ($x = 0; $x < $largoSum; $x++) {
                                            $proceSumarizadorArray = explode("/JAMSSummarizer start", $proceSumarizador[$x]);

                                            if (count($proceSumarizadorArray) > 1) {
                                                if ($corriendoComoAux == "") {
                                                    $corriendoComoAux = "Servicio";
                                                } else {
                                                    $corriendoComoAux .= " y Servicio";
                                                }
                                            }
                                        }

                                        if ($corriendoComoAux == "") $corriendoComoAux = "Detenido";
                                        echo "$corriendoComoAux";







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
                                            <td> <span class="<?php echo $colorDailyDiario ?>"> <?php echo $stringDatatimeCreacionDaily ?></span> </td>

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
                            <h3 class="card-title">Archivos más Pesados <?php echo $system->validarLog($tamanoArchivos, 20) ?>
                            </h3>
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
                                                        #$logString = explode(" ", str_replace("/opt/Jigsaw/", "", trim($x)));
                                                        $logString = explode(" ", preg_replace('/\s+/', ' ', str_replace("/opt/Jigsaw/", "", trim($x))));

                                                        $tamanoAux = $logString[4];
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
                                                        $logString = explode(" ", preg_replace('/\s+/', ' ', str_replace("/opt/Jigsaw/", "", trim($x))));

                                                        $tamanoAux = $logString[4];
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