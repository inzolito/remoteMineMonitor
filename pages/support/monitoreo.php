
<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");


$system = new systemClass();
$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();
$id_faena = $_REQUEST["id"];

echo "<i class='fas fa-clock'></i>" . date('h:i:s a');

//Falta validar cuando no existe la faena
$faenaDatos = $fenaCl->datos($id_faena);
$estadoCheckFaena = $fenaCl->estado($id_faena);
$checkDatos = $fenaCl->datosCheck($id_faena);
//array asociativo minas
$arrayFaenasDatos = array(
    "amant" => array(
        "cpu" => 1.6,
        "cpuSec" => 1,
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ),
    "cndrt" => array(
        "cpu" => 1.6,
        "cpuSec" => 1,
        "dbPrimaria" => "jmineops_prod",
        "dbSecundaria" => "jmineops_prod"
    ),
    "cndmh" => array(
        "cpu" => 1.6,
        "cpuSec" => 1,
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ),
    "crsten" => array(
        "cpu" => 1.6,
        "cpuSec" => 1,
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ),
    "ass" => array(
        "cpu" => 1.6,
        "cpuSec" => 1,
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ),
    "mlcc" => array(
        "cpu" => 1.6,
        "cpuSec" => 1,
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ),
    "veladero" => array(
        "cpu" => 1.6,
        "cpuSec" => 1,
        "dbPrimaria" => "jmineops_prod",
        "dbSecundaria" => "jmineops_prod"
    )
);

$carpeta = $faenaDatos->alias . "/";
$ruta = "/home/jigsaw/sherrera/prueba/" . $carpeta;




// ------------------------------------------------
$estadoDisco = file($ruta . "estadoServidorMon.log");
$impo = file($ruta . "ProcesoImpoMon.log");
$sqlServer = file($ruta . "ProcesoSqlMon.log");
$estadoDiscoSecundario = file($ruta . "estadoServidorSecMon.log");
$JamsCluster = file($ruta . "JamsClusterMon.log");
$JamsClusterSecundario = file($ruta . "JamsClusterSecMon.log");
$sumarizadorPrimario = file($ruta . "SummarizerMon.log");
$dailyServidorSecundario = file($ruta . "DailySecMon.log");
$sql_Back = file($ruta . "ProcesoSqlBackMon.log");
$proceSumarizador = file($ruta . "ProcesosSumarizadorMon.log");
$loadAveragePrimario = file($ruta . "loadAverageMon.log");
$loadAverageSecundario = file($ruta . "loadAverageSecMon.log");
$RamMemTotalPrimario = file($ruta . "MemTotalMon.log");
$RamMemTotalSecundario = file($ruta . "MemTotalSecMon.log");
$RamMemUsadaPrimario = file($ruta . "MemUsadaMon.log");
$RamMemUsadaSecundario = file($ruta . "MemUsadaSecMon.log");
$cpuGraficoPrimario = file($ruta . "cpuGraficoMon.log");
$cpuGraficoSecundario = file($ruta . "cpuGraficoSecMon.log");
$schemaPrimario = file($ruta . "querySchema.log");
$schemaSecundario = file($ruta . "querySchema_peer.log");

$sqlSizePrimario = file($ruta . "tamanoBD.log");
$sqlSizeSecundario = file($ruta . "tamanoBDSec.log");

$shiftsPrimario = file($ruta . "queryResults.log");
$shiftsSecundario = file($ruta . "queryResults_peer.log");
$Jamms = file($ruta . "ProcesosJamsMon.log");
$Ntp = file($ruta . "NtpMon.log");
$Rlm = file($ruta . "RlmMon.log");
$SizeLog = file($ruta . "SizeLogMon.log");
$SizeLogSec = file($ruta . "SizeLogSecMon.log");
$reconcile = file($ruta . "ReconcileMon.log");
$dailyFecha = file($ruta . "DailyFechaSecMon.log");

// logs de count a las tablas shift
$Loads = file($ruta . "queryLoads.log");
$Dumps = file($ruta . "queryDumps.log");
$States = file($ruta . "queryStates.log");
$Hauls = file($ruta . "queryHauls.log");
$LoadsSec = file($ruta . "queryLoads_peer.log");
$DumpsSec = file($ruta . "queryDumps_peer.log");
$StatesSec = file($ruta . "queryStates_peer.log");
$HaulsSec = file($ruta . "queryHauls_peer.log");

$BackupSec = file($ruta . "BackupSecMon.log");
$BdSize = file($ruta . "queryActivity.log");
$top10BD = file($ruta . "topTenTablasPostgres.log");

$tamanoBdPrimario = file($ruta . "tamanoTablasPostgresLectura.log");
$tamanoBdSecundario = file($ruta . "tamanoTablasPostgresSecLectura.log");

$consultaIdle = file($ruta . "consultasIdle.log");

$TopC = file($ruta . "TopCMon.log");
$TopCSec = file($ruta . "TopCSecMon.log");



$i = 0;

// nom serv siempre sera en la posicion 2
//nombre del servidor primario activo
$nombreServidorActivo = $estadoDisco[2];
//Obtencion de fecha
$fechaHoraAux = $estadoDisco[0];
$fechaHoraAux = explode(" ", $fechaHoraAux);
$horaEstadoServidorActivo = $fechaHoraAux[1];
$horaEstadoServidorActivo = $horaEstadoServidorActivo;
//disco duro primario
$porcentajeDisco = $estadoDisco[4];
$porcentajeDisco = explode("%", $porcentajeDisco);
$porcentajeDisco = preg_replace('/\s+/', '<separate>', $porcentajeDisco);
$porcentajeDisco = explode("<separate>", $porcentajeDisco[0]);
$porc = 0;
$discoDisponible = 0;
$discoUsado = 0;
$discoLibre = 0;
$largoLogDisco = count($porcentajeDisco);

// 1° =porcentaje  , 2°=disponible , 3°usado  , 4° total
for ($x = $largoLogDisco; $x > 0; $x--) {
    //1°
    if ($x == $largoLogDisco) {
        $porc = $porcentajeDisco[4];
    }
    //2°
    if ($x == $largoLogDisco - 1) {
        $discoDisponible = $porcentajeDisco[3];
    }
    //3°
    if ($x == $largoLogDisco - 2) {
        $discoUsado = $porcentajeDisco[2];
    }
    //4°
    if ($x == $largoLogDisco - 3) {
        $discoTotal = $porcentajeDisco[1];
    }
}
//Pasar los T a GB en caso de que sea así
/*
if (strpos($discoTotal, 'T') !== false) {
    $discoTotal = str_replace('T', '', $discoTotal);
    $discoTotal = round($discoTotal * 1024);
  }
  
  echo $discoTotal . 'GB';
*/

// ************* Grafico CPU  Primario ************


$LargoCpuGrafico = count($cpuGraficoPrimario);
$y = 1;
$datosGraficoCpu = array();

//echo "<br><br>ultimo dato = ".$cpuGraficoPrimario[$LargoCpuGrafico];
for ($x = $LargoCpuGrafico; $x > $LargoCpuGrafico - 16; $x--) {
    if ($cpuGraficoPrimario[$x] != "" && $cpuGraficoPrimario[$x] != " " && $y % 2 == 0) {
        //echo $cpuGraficoPrimario[$x];
        //echo "<br>";
        array_push($datosGraficoCpu, $cpuGraficoPrimario[$x]);
    }
    $y++;
}


//cambio de color grafico cpu
$graficocpubg = "rgba(60,141,188,0.9)";
$graficocpust = "rgba(60,141,188,1)";
$graficocpult = "rgba(60,141,188,1)";
$graficocpubr = "rgba(60,141,188,0.8)";

if ($arrayFaenasDatos[$faenaDatos->alias]["cpu"] < $datosGraficoCpu[0]) {
    $graficocpust = "rgba(245, 39, 39, 1)";
    $graficocpubg = "rgba(245, 39, 39, 1)";
    $graficocpult = "rgba(88, 1, 1, 1)";
    $graficocpubr = "rgba(195, 11, 11, 1)";
}

// *************************************


// ************* Grafico CPU  Secundario ************

$LargoCpuGrafico2 = count($cpuGraficoSecundario);
$y = 1;
$datosGraficoCpuSec = array();
for ($x = $LargoCpuGrafico2; $x > $LargoCpuGrafico2 - 16; $x--) {
    if ($cpuGraficoSecundario[$x] != "" && $cpuGraficoSecundario[$x] != " " && $y % 2 == 0) {
        //echo $cpuGraficoSecundario[$x];
        //echo "<br>";
        array_push($datosGraficoCpuSec, $cpuGraficoSecundario[$x]);
    }
    $y++;
}

//cambio de color grafico cpu sec
$graficocpubgSec = "rgba(60,141,188,0.9)";
$graficocpustSec = "rgba(60,141,188,1)";
$graficocpultSec = "rgba(60,141,188,1)";
$graficocpubrSec = "rgba(60,141,188,0.8)";

if ($arrayFaenasDatos[$faenaDatos->alias]["cpuSec"] < $datosGraficoCpuSec[0]) {
    $graficocpustSec = "rgba(245, 39, 39, 1)";
    $graficocpubgSec = "rgba(245, 39, 39, 1)";
    $graficocpultSec = "rgba(88, 1, 1, 1)";
    $graficocpubrSec = "rgba(195, 11, 11, 1)";
}

// *************************************

//ip server
$ipServerPrimario = $estadoDisco[5];
//JamsCluster primario
//$JamsCluster=explode(" ",$JamsCluster[1]);
//$nombreJamsCluster=$JamsCluster[4];
$jamsCluster = explode(" ", $estadoDisco[4]);
$jamsCluster = $jamsCluster[37];

//load average primario
//$loadAveragePrimario = $loadAveragePrimario[1];
$loadAveragePrimario = $datosGraficoCpu[0];
//memoria ram primario
$RamMemUsadaPrimario = $RamMemUsadaPrimario[1];
$RamMemUsadaPrimario = explode(" ", $RamMemUsadaPrimario);
$cuenta = count($RamMemUsadaPrimario);
$ramUsadaTotal = 0;
$contMem = 0;
for ($x = 0; $x < $cuenta; $x++) {

    if ($RamMemUsadaPrimario[$x] != "" && $RamMemUsadaPrimario[$x] != " ") {
        switch ($contMem) {
            case 0:
                $ramUsadaTotal = $RamMemUsadaPrimario[$x];
                $contMem++;
                break;
        }
    }
}

$RamMemTotalPrimario = $RamMemTotalPrimario[1];
$RamMemTotalPrimario = explode(" ", $RamMemTotalPrimario);
$cuenta = count($RamMemTotalPrimario);
$ramTotal = 0;
$contMem = 0;
for ($x = 0; $x < $cuenta; $x++) {

    if ($RamMemTotalPrimario[$x] != "" && $RamMemTotalPrimario[$x] != " ") {
        switch ($contMem) {
            case 0:
                $ramTotal = $RamMemTotalPrimario[$x];
                $contMem++;
                break;
        }
    }
}

$porcentajeRam = ((float)$ramUsadaTotal * 100) / $ramTotal;
$porcentajeRam = round($porcentajeRam, 1);

//hora sumarizador
$horaSum = explode(" ", $sumarizadorPrimario[1]);



//sumarizador 
$largoSum = count($sumarizadorPrimario);
$pos = strpos($sumarizadorPrimario, " summaries with interval 300 seconds.");
$validarSumm = 0;
$fechaActual = date("y-m-d H:i:00");

for ($x = $largoSum; $x > 0; $x--) {
    $pos = strpos($sumarizadorPrimario[$x], " summaries with interval 300 seconds.");
    //echo "<br>".$sumarizadorPrimario[$x];
    if ($pos > 0) {
        //echo "<br>";


        $obtenerFecha = explode(" at ", $sumarizadorPrimario[$x + 1]);
        (count($obenerFecha) > 0) ? $obenerFecha : $obtenerFecha = explode(" at ", $sumarizadorPrimario[$x - 1]);
        $FechaSumm = substr($obtenerFecha[1], 0, 19); // la del explode (), preguntar con x+1 para encontrar la fecha y x-1 // isset($variable) = preguntar si la variable existe <revision>

        if ($FechaSumm == 0 || $FechaSumm == "") {
            $validarSumm = 3;
            $x = 0;
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

            $validarSumm = 1;
            $x = 0;
        }
    }
    if ($validarSumm == 1) {
        $validarSumm = "OK";
    } else {
        $validarSumm = "ERROR";
    }
}



//==================server secundario====================

//estado del servidor 2
$estadoServidorSecundario = $estadoDiscoSecundario[1];
$nombreServidorSecundario = $estadoDiscoSecundario[2];
//disco duro Secundario
$porcentajeDiscoSecundario = $estadoDiscoSecundario[4];
$porcentajeDiscoSecundario = explode("%", $porcentajeDiscoSecundario);
$porcentajeDiscoSecundario = preg_replace('/\s+/', '<separate>', $porcentajeDiscoSecundario);
$porcentajeDiscoSecundario = explode("<separate>", $porcentajeDiscoSecundario[0]);
$porcSec = 0;
$discoDisponibleSec = 0;
$discoUsadoSec = 0;
$discoLibreSec = 0;
$largoLogDiscoSec = count($porcentajeDiscoSecundario);


// 1° =porcentaje  , 2°=disponible , 3°usado  , 4° total
for ($x = $largoLogDiscoSec; $x > 0; $x--) {
    //1°
    if ($x == $largoLogDiscoSec) {
        $porcSec = $porcentajeDiscoSecundario[4];
    }
    //2°
    if ($x == $largoLogDiscoSec - 1) {
        $discoDisponibleSec = $porcentajeDiscoSecundario[3];
    }
    //3°
    if ($x == $largoLogDiscoSec - 2) {
        $discoUsadoSec = $porcentajeDiscoSecundario[2];
    }
    //4°
    if ($x == $largoLogDiscoSec - 3) {
        $discoTotalSec = $porcentajeDiscoSecundario[1];
    }
}


//ip server secudario
$ipServerSec = $estadoDiscoSecundario[5];
//JamsCluster secundario
$jamsClusterSec = explode(" ", $estadoDiscoSecundario[4]);
$jamsClusterSec = $jamsClusterSec[37];
//load average secundario
$loadAverageSecundario = $datosGraficoCpuSec[0];
//mamoria ram secundario
$RamMemUsadaSecundario = $RamMemUsadaSecundario[1];
$RamMemUsadaSecundario = explode(" ", $RamMemUsadaSecundario);
$cuenta = count($RamMemUsadaSecundario);
$ramUsadaTotalSec = 0;
$contMemSec = 0;
for ($x = 0; $x < $cuenta; $x++) {

    if ($RamMemUsadaSecundario[$x] != "" && $RamMemUsadaSecundario[$x] != " ") {
        switch ($contMemSec) {
            case 0:
                $ramUsadaTotalSec = $RamMemUsadaSecundario[$x];
                $contMemSec++;
                break;
        }
    }
}

$RamMemTotalSecundario = $RamMemTotalSecundario[1];
$RamMemTotalSecundario = explode(" ", $RamMemTotalSecundario);
$cuenta = count($RamMemTotalSecundario);
$ramTotalSec = 0;
$contMemSec = 0;
for ($x = 0; $x < $cuenta; $x++) {

    if ($RamMemTotalSecundario[$x] != "" && $RamMemTotalSecundario[$x] != " ") {
        switch ($contMemSec) {
            case 0:
                $ramTotalSec = $RamMemTotalSecundario[$x];
                $contMemSec++;
                break;
        }
    }
}

$porcentajeRamSec = ((float)$ramUsadaTotalSec * 100) / $ramTotalSec;
$porcentajeRamSec = round($porcentajeRamSec, 1);




//daily Estado
$largoDaily = count($dailyServidorSecundario);
$posDaily = strpos($dailyServidorSecundario, " rows for workers");
$validarDaily = 0;
for ($x = $largoDaily; $x > 0; $x--) {
    //echo"<br>";
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

for ($x = $largoDailyFecha; $x > 0; $x--) {
    //echo"<br>";
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
} else {
    $fechaDaily = explode(".tgz", $ultimoDaily);
    $fechaDaily = explode("jmineops-", $fechaDaily[0]);
    $fechaDaily = $fechaDaily[1];
}

if ($fechaDaily == $fehcaActualSH) {
    //$validarDaily = 1;
    $validarDailyDiario = 1;
} else {
    //$validarDaily = 2;
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

//fecha Schema
$largoSchema = count($schemaSecundario);
$fechaSchema = explode("|", $schemaSecundario[2]);
$fechaSchema = $fechaSchema[5];
//print_r($fechaSchema);


//count de lineas Impo
$largoImpo = count($impo) - 2;
//count sqlServer
$largoSqlServer = count($sqlServer) - 2;
//count sql back
$largoSqlBack = count($sql_Back) - 2;
//count Summ
$largoSummarizador = count($proceSumarizador) - 2;
//count Ntp
$largoNtp = count($Ntp) - 2;
//count reconcile
$largoReconcile = count($reconcile) - 5;
//resultado resta loads
$resultadoLoads = abs($Loads[2] - $LoadsSec[2]);
//resultado resta Dumps
$resultadoDumps = abs($Dumps[2] - $DumpsSec[2]);
//resultado resta states
$resultadoStates = abs($States[2] - $StatesSec[2]);
//resultado resta hauls
$resultadoHauls = abs($Hauls[2] - $HaulsSec[2]);
//count Consulta Idle
$largoIdle = count($consultaIdle);
if ($largoIdle < 2) {
    $cantidadIdle = $largoIdle - 1;
} else {
    $cantidadIdle = $largoIdle - 5;
}

if ($cantidadIdle >= 15) {
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
    if ($validarSizeLog >= 3) {
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


// Funcion para enviar mensajes por Telegram
function enviarMensajeTelegram($message){
    $botToken = "6098434713:AAEuvoUJKnUwzW_Wx2h4e2LnYCAkoW1iB-I"; //Token del bot Telegram
    $chatId = "-1001966529185"; // Reemplazar con el chat ID del usuario o grupo al que se le quiere enviar el mensaje

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
function enviarEmail($subject,$message) {
    $to = '@hexagon.com'; 
    $headers = 'From: @gmail.com' . "\r\n" .
            'Reply-To: @gmail.com' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);

}




?>


<script>
    titulo(" Monitoreo <?php echo $faenaDatos->faena ?> <button class='btn btn-primary' onclick='cargaMonitoreo(<?php echo $id_faena ?>)'><i class='fas fa-sync-alt'></i></button>", "monitoreo");
</script>



<div class="row">
    <div class="col-md-8">

        <div class="row">
            <!-- Servidor 1 x 2  .... Server primario-->
            <div class="col-md-6">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Servidor primario <i class='fas fa-clock'></i> <?php echo $horaEstadoServidorActivo   ?></h3>
                        <div class="card-tools">
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p class="text-sm  ">Nom. Server
                                    <b class="d-block"> <?php echo $nombreServidorActivo   ?></b>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p class="text-sm  ">JAMSCluster
                                    <b class="d-block"><i class="fa-solid fa-check"></i> <?php echo $nombreServidorSecundario   ?> </b>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p class="text-sm  ">IP
                                    <b class="d-block"><?php
                                     
                                    ?> </b>
                                </p>
                            </div>


                        </div>


                        <div class="row">
                            <div class="col-md-3">
                                <?php if ($porc <= 50) { ?>
                                    <input type="text" value=<?php echo $porc  ?> class="GraficoVerde" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } elseif ($porc > 50 && $porc <= 70) { ?>
                                    <input type="text" value=<?php echo $porc  ?> class="GraficoAmarillo" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } elseif ($porc > 70) { ?>
                                    <input type="text" value=<?php echo $porc  ?> class="GraficoRojo" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } ?>
                                <div class="text-center">Disco Duro</div>
                                <table class="table table-bordered">
                                    <tbody>
                                        <div class="progress-group mt-2 px-2">
                                            HD<span class="float-right"><b><?php echo $discoUsado   ?></b>/<?php echo $discoTotal   ?>
                                            </span>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $porc  ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    </tbody>
                                </table>

                            </div>
                            <div class="col-md-3">
                                <?php if ($porcentajeRam <= 50) { ?>
                                    <input type="text" value=<?php echo $porcentajeRam  ?> class="GraficoVerde" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } elseif ($porcentajeRam > 50 && $porcentajeRam <= 70) { ?>
                                    <input type="text" value=<?php echo $porcentajeRam  ?> class="GraficoAmarillo" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } elseif ($porcentajeRam > 70) { ?>
                                    <input type="text" value=<?php echo $porcentajeRam  ?> class="GraficoRojo" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } ?>
                                <div class="text-center">Memoria Ram</div>
                                <table class="table table-bordered">
                                    <tbody>
                                        <div class="progress-group mt-2 px-2">
                                            Ram<span class="float-right"><b><?php echo round($ramUsadaTotal / 1024, 1)   ?></b>/<?php echo round($ramTotal / 1024, 1)   ?>
                                            </span>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $porcentajeRam  ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <canvas id="cpuServer1"></canvas>
                                <div class="text-center">CPU - Load Average: <?php echo $loadAveragePrimario   ?> <button type="button" id="btnTopC" onclick="verInfo('divTopCVista')" class="btn btn-outline-light"> <i class='fa-solid fa-eye'></i></button></div>
                            </div>


                            <div class="row" id="divTopCVista" tittle="TopC" style="overflow:auto;">
                                <div class="col-md-12">
                                    <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                        <table class="table">
                                            <tbody>

                                                <?php
                                                $i = 0;
                                                foreach ($TopC as $x) {
                                                    if ($i > 6) {

                                                        echo "<tr>";
                                                        // $TopC = $TopC;
                                                        $topctd = trim(preg_replace('/\s+/', ' ', $x));
                                                        $topctd = explode(" ", $topctd);

                                                        foreach ($topctd as $y) {
                                                            echo " <td>  " . $y . "</td>";
                                                        }

                                                        echo "</tr>";
                                                    } else {
                                                        //<pendiente>
                                                        // primeera linea separar por comas y mostrar en sus propias td
                                                        // segunda linea en adelante separar por : y la celda [0] mostrar en td, la celda 1 volver a cortar por  ,  y mostrarlas en td
                                                        echo $x . "<br>";
                                                    }
                                                    $i++;
                                                }

                                                ?>
                                                <tr>
                                                    <td></td>
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

            <!-- Servidor 1 x 2  .... Server Secundario-->
            <div class="col-md-6">
                <div class="card card-olive">
                    <div class="card-header">
                        <h3 class="card-title">Servidor secundario</h3>
                        <div class="card-tools">
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <p class="text-sm  ">Nom. Server
                                    <b class="d-block"><?php echo $nombreServidorSecundario   ?> </b>
                                </p>
                            </div>
                            <div class="col-md-3">
                                <p class="text-sm  ">JAMSCluster
                                    <b class="d-block"><i class="fa-solid fa-check"></i> <?php echo $nombreServidorActivo   ?> </b>
                                </p>
                            </div>
                            <div class="col-md-3">
                                <p class="text-sm  ">IP
                                    <b class="d-block"><?php echo $ipServerSec   ?> </b>
                                </p>
                            </div>
                            <div class="col-md-3">
                                <p class="text-sm  ">status
                                    <b class="d-block"><?php echo $estadoServidorSecundario   ?> </b>
                                </p>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <?php if ($porcSec <= 50) { ?>
                                    <input type="text" value=<?php echo $porcSec  ?> class="GraficoVerde" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } elseif ($porcSec > 50 && $porcSec <= 70) { ?>
                                    <input type="text" value=<?php echo $porcSec  ?> class="GraficoAmarillo" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } elseif ($porcSec > 70) { ?>
                                    <input type="text" value=<?php echo $porcSec  ?> class="GraficoRojo" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } ?>

                                <div class="text-center">Disco Duro</div>
                                <table class="table table-bordered">
                                    <tbody>
                                        <div class="progress-group mt-2 px-2">
                                            HD<span class="float-right"><b><?php echo $discoUsadoSec ?></b>/<?php echo $discoTotalSec ?>
                                            </span>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $porcSec  ?>%">
                                                </div>
                                            </div>
                                        </div>

                                    </tbody>
                                </table>
                            </div>

                            <div class="col-md-3">


                                <?php if ($porcentajeRamSec <= 50) { ?>
                                    <input type="text" value=<?php echo $porcentajeRamSec  ?> class="GraficoVerde" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } elseif ($porcentajeRamSec > 50 && $porcentajeRamSec <= 70) { ?>
                                    <input type="text" value=<?php echo $porcentajeRamSec  ?> class="GraficoAmarillo" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } elseif ($porcentajeRamSec > 70) { ?>
                                    <input type="text" value=<?php echo $porcentajeRamSec  ?> class="GraficoRojo" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                <?php } ?>


                                <div class="text-center">Memoria Ram</div>
                                <table class="table table-bordered">
                                    <tbody>
                                        <div class="progress-group mt-2 px-2">
                                            Ram<span class="float-right"><b><?php echo round($ramUsadaTotalSec / 1024, 1)   ?></b>/<?php echo round($ramTotalSec / 1024, 1)   ?>
                                            </span>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $porcentajeRamSec  ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    </tbody>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <canvas id="cpuServer2"></canvas>
                                <div class="text-center">CPU - Load Average: <?php echo $loadAverageSecundario   ?> <button type="button" id="btnTopCSec" onclick="verInfo('divTopCSecVista')" class="btn btn-outline-light"> <i class='fa-solid fa-eye'></i></button></div>
                            </div>

                            <div class="row" id="divTopCSecVista" tittle="TopC Secundario" style="overflow:auto;">
                                <div class="col-md-12">
                                    <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                        <table class="table">
                                            <tbody>

                                                <?php
                                                $i = 0;
                                                foreach ($TopCSec as $x) {
                                                    if ($i > 6) {

                                                        echo "<tr>";
                                                        // $TopCSec = $TopCSec;
                                                        $topcsectd = trim(preg_replace('/\s+/', ' ', $x));
                                                        $topcsectd = explode(" ", $topcsectd);

                                                        foreach ($topcsectd as $y) {
                                                            echo " <td>  " . $y . "</td>";
                                                        }

                                                        echo "</tr>";
                                                    } else {
                                                        //<pendiente>
                                                        // primeera linea separar por comas y mostrar en sus propias td
                                                        // segunda linea en adelante separar por : y la celda [0] mostrar en td, la celda 1 volver a cortar por  ,  y mostrarlas en td
                                                        echo $x . "<br>";
                                                    }
                                                    $i++;
                                                }

                                                ?>
                                                <tr>
                                                    <td></td>
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
        </div>

        <!-- Datos Varios-->
        <div class="row">
            <div class="col-md-6">
                <div class="card card-navy">
                    <div class="card-header">
                        <h3 class="card-title">Datos</h3>
                        <div class="card-tools">
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <table class="table table-bordered">
                                <tbody>
                                    <?php if ($carpeta == "ass/" || $carpeta == "crsten/") {
                                    } else { ?>
                                        <tr>
                                            <td>Rlm</td>
                                            <td> <?php foreach ($Rlm as $x) {
                                                        $Rlm = $Rlm;
                                                        $largoRlm = count($Rlm);
                                                        if ($largoRlm <= 1) {
                                                            $Rlm = 1;
                                                            if ($Rlm == 1) {
                                                                $Rlm = "No se encontraron datos";
                                                            }
                                                            echo $Rlm;
                                                        } else {
                                                            echo " - " . trim($x) . "<br>";
                                                        }
                                                    }   ?></td>
                                            <td style="min-width: 120px;">
                                                <?php if ($validarRlm >= 700 && $validarRlm <= 800) { ?>
                                                    <span class="badge bg-warning p-2"> Revisar </span>
                                                <?php } elseif ($validarRlm >= 900) { ?>
                                                    <span class="badge bg-danger p-2"> MAL </span>
                                                <?php } else { ?>
                                                    <span class="badge bg-success p-2"> OK </span>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <tr>
                                        <td>Peso Logs</td>
                                        <td><?php foreach ($SizeLog as $x) {
                                                $SizeLog = $SizeLog;
                                                $largoSize = count($SizeLog);
                                                if ($largoSize <= 1) {
                                                    $SizeLog = 1;
                                                    if ($SizeLog == 1) {
                                                        $SizeLog = "No se encontraron logs mayores a 1GB";
                                                    }
                                                    echo $SizeLog;
                                                } else {
                                                    echo " - " . trim($x) . "<br>";
                                                }
                                            }   ?></td>

                                        <?php if ($validarSizeLog >= 5 && $validarSizeLog <= 9) { ?>
                                            <td> <span class="badge bg-warning p-2"> Revisar </span> </td>
                                        <?php } elseif ($validarSizeLog >= 10) { ?>
                                            <td> <span class="badge bg-danger p-2"> MAL </span> </td>
                                        <?php } else { ?>
                                            <td> <span class="badge bg-success p-2"> OK </span> </td>
                                        <?php } ?>


                                    </tr>
                                    <tr>
                                        <td>Estacion base</td>
                                        <td></td>

                                        <td><span class="badge bg-success p-2"> OK </span> </td>
                                    </tr>
                                    <tr>
                                        <td>Consultas PostGres Pegadas (IDLE)</td>
                                        <td> La cantidad es:
                                            <?php
                                            echo $cantidadIdle
                                            ?>
                                        </td>

                                        <td>
                                            <?php if ($validarIdle == "OK") { ?>
                                                <button type="button" id="btnIdle" onclick="verInfo('divIdleVista')" class="btn btn-success btn-block"> <i class='fa-solid fa-eye mr-1'></i> <?php echo $validarIdle ?></button>
                                            <?php } else { ?>
                                                <button type="button" id="btnIdle" onclick="verInfo('divIdleVista')" class="btn btn-danger btn-block"> <i class='fa-solid fa-eye mr-1  '></i> <?php echo $validarIdle ?></button>
                                            <?php 


                                        } ?>
                                        </td>
                                        <div class="row" id="divIdleVista" tittle="Idle" style="overflow:auto;">
                                            <div class="col-md-12">
                                                <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                                    <?php foreach ($consultaIdle as $x) {
                                                        $consultaIdle = $consultaIdle;;
                                                        echo " - " . trim($x) . "<br>";
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
                        <div class="card card-navy">
                            <div class="card-header">
                                <h3 class="card-title">Sumarizador</h3>
                                <div class="card-tools">
                                </div>
                            </div>

                            <div class="card-body">


                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="text-sm  ">Fecha sumarizando
                                            <b class="d-block"><?php echo $FechaSumm   ?> </b>
                                        </p>
                                        <p class="text-sm  ">Estado
                                            <?php if ($validarSumm == "OK") { ?>
                                                <td> <span class="badge bg-success p-2"> <?php echo $validarSumm ?></span> </td>
                                            <?php } else { ?>
                                                <td> <span class="badge bg-danger p-2"> <?php echo $validarSumm;

                                                ?> </span> </td>
                                            <?php } ?>
                                        </p>

                                    </div>

                                    <div class="col-md-4">
                                        <p class="text-sm  ">Corriendo como :
                                            <b class="d-block"><?php for($x=0; $x < $largoSum; $x++){
                                                                        $corriendo=explode("config.jams",$proceSumarizador[$x]);
                                                                        $corteCorriendo=explode("As",$corriendo[1]);
                                                                        echo $corteCorriendo[1];
                                                                        } ?>
                                            </b>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-sm  ">Corriendo Desde :
                                            <b class="d-block"><i class="fa-regular fa-clock"></i> 01-01-2023</b>
                                        </p>
                                    </div>

                                </div>

                                <button type="button" class="btn btn-outline-info" onclick="verInfo('divSumarizadorVista')">Ver Info</button>

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

                    <!-- Daily -->

                    <div class="col-md-12">
                        <div class="card card-navy">
                            <div class="card-header">
                                <h3 class="card-title">Daily</h3>
                                <div class="card-tools">
                                </div>
                            </div>

                            <div class="card-body">


                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="text-sm  ">Fecha loaded
                                            <b class="d-block"><?php echo $fechaLoadedDaily  ?> </b>
                                        </p>
                                    </div>

                                    <div class="col-md-4">
                                        <p class="text-sm  ">último daily :
                                            <b class="d-block"><i class="fa-regular fa-clock"></i><?php echo $ultimoDaily   ?></b>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-sm  ">Estado Daily :
                                            <b class="d-block">
                                                <?php if ($validarDaily == "OK") { ?>
                                                    <td> <span class="badge bg-success p-2"> <?php echo $validarDaily ?></span> </td>
                                                <?php } else { ?>
                                                    <td> <span class="badge bg-danger p-2"> <?php echo $validarDaily ?> </span> </td>
                                                <?php 
                                                    
                                                } ?>
                                            </b>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-sm  ">Backup Diario:
                                            <b class="d-block">
                                                <?php if ($validarDailyDiario == "OK") { ?>
                                                    <td> <span class="badge bg-success p-2"> <?php echo $validarDailyDiario ?></span> </td>
                                                <?php } else { ?>
                                                    <td> <span class="badge bg-danger p-2"> <?php echo $validarDailyDiario ?> </span> </td>
                                                <?php 

                                                } ?>
                                            </b>
                                        </p>
                                    </div>

                                </div>
                                <button type="button" class="btn btn-outline-info" onclick="verInfo('divDailyVista')">Ver Info</button>

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
            </div>
        </div>






    </div>
    <div class="col-md-4">

        <div class="row">
            <!-- Datos de la base de datos -->
            <div class="col-md-12">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Base de datos</h3>
                        <div class="card-tools">
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">

                            </div>
                        </div>
                        <div class="row">
                            <?php


                            //tamaño Bd
                            $tamanoBd = 0;
                            $largoBd = count($sqlSizePrimario);
                            for ($x = $largoBd; $x > 0; $x--) {
                                $arrayAux = explode("|", $sqlSizePrimario[$x]);
                                if (preg_replace('/\s+/', "", $arrayAux[0]) == $arrayFaenasDatos[$faenaDatos->alias]["dbPrimaria"]) {
                                    $tamanoBd = str_replace("GB", "", $arrayAux[1]);
                                    $x = -1;
                                }
                            }


                            //Tamaño Bd sec
                            $tamanoBdSec = 0;
                            $largoBdSec = count($sqlSizeSecundario);
                            for ($x = $largoBdSec; $x > 0; $x--) {
                                $arrayAux = explode("|", $sqlSizeSecundario[$x]);
                                if (preg_replace('/\s+/', "", $arrayAux[0]) == $arrayFaenasDatos[$faenaDatos->alias]["dbSecundaria"]) {
                                    $tamanoBdSec = str_replace("GB", "", $arrayAux[1]);
                                    $x = -1;
                                }
                            }
                            //Carga logs
                            $countTablasShift = file($ruta . "tamanoTablasPostgres.log");
                            $countTablasShiftSec = file($ruta . "tamanoTablasPostgresSec.log");
                            //Array llenado datos log primario
                            $countTablas = array();
                            $tablaAux = "";
                            $largoCountTablasSift = count($countTablasShift);
                            //Fecha del log de las tamanoTablasPostgres
                            $fechaCountTablasPostgres = $countTablasShift[0];
                            //llenado array 
                            for ($x = 0; $x < $largoCountTablasSift - 2; $x++) {
                                $tablaAux = explode("|", $countTablasShift[$x]);
                                $countTablas[$tablaAux[0]] = $tablaAux[1];
                            }


                            ?>

                            <table class="table table-bordered">

                                <thead>
                                    <tr>
                                        <th>Tablas</th>
                                        <th>Server 1</th>
                                        <th>Server 2</th>
                                        <th>Diff</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td>Tamaño BD</td>
                                        <td><?php echo $tamanoBd . "GB"   ?></td>
                                        <td><?php echo $tamanoBdSec . "GB"    ?></td>
                                        <td><span><?php echo abs($tamanoBd - $tamanoBdSec)    ?></span></td>
                                    </tr>

                                    <?php
                                    $tablaAux = "";
                                    $largoCountTablasSiftSec = count($countTablasShiftSec);
                                    for ($x = 3; $x < $largoCountTablasSiftSec - 2; $x++) {
                                        $tablaAux = explode("|", $countTablasShiftSec[$x]);
                                        $restaAux = abs($countTablas[$tablaAux[0]] - $tablaAux[1]);

                                        $colorAux = "bg-success";
                                        if ($restaAux > 19 && $restaAux <= 49)  $colorAux = "bg-warning";
                                        if ($restaAux >= 50)  $colorAux = "bg-danger";

                                        echo "<tr>";
                                        echo "<td>" . $tablaAux[0] . "</td>";
                                        echo "<td>" . $countTablas[$tablaAux[0]] . "</td>";
                                        echo "<td>" . $tablaAux[1] . "</td>";
                                        echo "<td><span class='badge " . $colorAux . "''>" . $restaAux . "</span></td>";
                                        echo "</tr>";
                                    }
                                    ?>



                                </tbody>
                            </table>

                        </div>

                    </div>


                </div>

            </div>

            <!--Top 10 tablas más pesadas-->
            <?php
            $horaLog = $top10BD[0];
            ?>
            <div class="col-md-12">
                <div class="card card-navy">
                    <div class="card-header">
                        <h3 class="card-title"> <i class='fas fa-clock'></i> <?php echo $system->formatoFecha($horaLog, "hs") . " Hrs"  ?> | Top 10 tablas con mayor peso</h3>
                        <div class="card-tools">
                        </div>
                    </div>

                    <div class="card-body">
                        <table class="table table-bordered">

                            <?php
                            $largoTop10 = count($top10BD);
                            for ($x = 0; $x < $largoTop10; $x++) {
                                $punteroTopBD = explode("|", $top10BD[$x]);
                                if ($x == 1) {
                                    echo "<thead><tr>";
                                    echo "<th>" . $punteroTopBD[0] . "</th>";
                                    echo "<th>" . $punteroTopBD[1] . "</th>";
                                    echo "<th>" . $punteroTopBD[2] . "</th>";
                                    echo "</tr></thead>";
                                }

                                if ($x > 2 && $x < $largoTop10 - 2) {

                                    echo "<tbody><tr>";
                                    echo "<td>" . $punteroTopBD[0] . "</td>";
                                    echo "<td>" . $punteroTopBD[1] . "</td>";
                                    echo "<td>" . $punteroTopBD[2] . "</td>";
                                    echo "</tr></tbody>";
                                }
                            }

                            ?>

                        </table>
                    </div>


                </div>

            </div>


            <!-- PS AUX , importadores , sumarizador, jams etc -->
            <div class="col-md-12">
                <div class="card card-navy">
                    <div class="card-header">
                        <h3 class="card-title">Procesos</h3>
                        <div class="card-tools">
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- ----------- Row Datos procesos------------->
                        <div class="row">
                            <?php
                            $colorAux = "bg-success";
                            $valorAux="Success";
                            ?>
                            <!-- JAMS -->
                            <div class="col-lg-3 col-6">

                                <div class="small-box <?php echo $colorAux ?>">
                                    <div class="inner">
                                        <h5>JAMS</h5>
                                        <p><?php echo $valorAux?></p>
                                    </div>
                                    <div class="icon">
                                        <i class="ion ion-stats-bars"></i>
                                    </div>
                                </div>
                            </div>


                            <?php
                            $colorAux = "bg-success";
                            $largoAux = $largoImpo;
                            if ($largoAux > 1 && $largoAux <= 5)  $colorAux = "bg-warning";
                            if ($largoAux >= 6) $colorAux = "bg-danger";
                            ?>
                            <!-- Importadores -->
                            <div class="col-lg-3 col-6">

                                <div class="small-box <?php echo $colorAux  ?>">
                                    <div class="inner">
                                        <h5>Importadores</h5>
                                        <p>Success : <?php echo $largoImpo ?></p>
                                    </div>
                                    <div class="icon">
                                        <i class="ion ion-stats-bars"></i>
                                    </div>
                                </div>
                            </div>


                            <!-- SQLServer -->
                            <div class="col-lg-3 col-6">

                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h5>SQL server</h5>
                                        <p>Success</p>
                                    </div>
                                    <div class="icon">
                                        <i class="ion ion-stats-bars"></i>
                                    </div>
                                </div>
                            </div>




                            <!-- Sql_Back -->
                            <div class="col-lg-3 col-6">

                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h5>sql_back</h5>
                                        <p>Success</p>
                                    </div>
                                    <div class="icon">
                                        <i class="ion ion-stats-bars"></i>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="row">    
                        <?php
                            $colorAux = "bg-success";
                            $largoAux = $largoSummarizador;
                            if ($largoAux > 3 && $largoAux < 3)  $colorAux = "bg-warning";
                            if ($largoAux >= 6 || $largoAux < 2) $colorAux = "bg-danger" ;  
                                
;
                            ?>
                            <!-- Sql_Back -->
                            <div class="col-lg-3 col-6">

                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h5>Sumarizador</h5>
                                        <p>Success</p>
                                    </div>
                                    <div class="icon">
                                        <i class="ion ion-stats-bars"></i>
                                    </div>
                                </div>
                            </div>


                            <?php
                            $colorAux = "bg-success";
                            $largoAux = $largoNtp;
                            if ($largoAux > 1 && $largoAux <= 5)  $colorAux = "bg-warning";
                            if ($largoAux >= 6)  $colorAux = "bg-danger";
                            ?>
                            <!-- Sql_Back -->
                            <div class="col-lg-3 col-6">

                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h5>Ntp</h5>
                                        <p>Success</p>
                                    </div>
                                    <div class="icon">
                                        <i class="ion ion-stats-bars"></i>
                                    </div>
                                </div>
                            </div>


                            <?php
                            $colorAux = "bg-success";
                            $largoAux = $largoReconcile;
                            if ($largoAux > 1 && $largoAux <= 5)  $colorAux = "bg-warning";
                            if ($largoAux >= 6)  $colorAux = "bg-danger";
                            ?>
                            <!-- Sql_Back -->
                            <div class="col-lg-3 col-6">

                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h5>Reconcile</h5>
                                        <p>Success</p>
                                    </div>
                                    <div class="icon">
                                        <i class="ion ion-stats-bars"></i>
                                    </div>
                                </div>
                            </div>

                            <?php
                            $colorAux = "bg-success";
                            ?>
                            <!-- Sql_Back -->
                            <div class="col-lg-3 col-6">

                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h5>Backup Secundario</h5>
                                        <p>Success</p>
                                    </div>
                                    <div class="icon">
                                        <i class="ion ion-stats-bars"></i>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- Fin row datos procesos -->


                        <div class="row">
                            <div class="col-md-12">
                                <b class="d-block">JAMS </b><button type="button" class="btn btn-outline-info" onclick="verInfo('divJamsVista')">Ver Info</button>

                                <div class="row" id="divJamsVista" tittle="JAMS corriendo." style="overflow:auto;">
                                    <div class="col-md-12">

                                        <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                            <?php foreach ($Jamms as $x) {
                                                $ProcesoJamms = $Jamms;
                                                echo " - " . trim($x) . "<br>";
                                            }   ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Importadores -->

                        <div class="row">
                            <div class="col-md-12">
                                <?php
                                $colorAux = "bg-success";
                                if ($largoImpo > 1 && $largoImpo <= 5)  $colorAux = "bg-warning";
                                if ($largoImpo >= 6)  $colorAux = "bg-danger";
                                ?>
                                <b class="d-block">Importadores: <?php echo "<span class='badge " . $colorAux . "''>" . $largoImpo . "</span>"  ?> </b>
                                <button type="button" class="btn btn-outline-info" onclick="verInfo('divImpoVista')">Ver Info</button>

                                <div class="row" id="divImpoVista" tittle="Importadores." style="overflow:auto;">
                                    <div class="col-md-12">
                                        <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                            <?php foreach ($impo as $x) {
                                                $importadores = $impo;
                                                echo " - " . trim($x) . "<br>";
                                            }

                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- SqlServer -->

                        <div class="row">
                            <div class="col-md-12">
                                <?php
                                $colorAux = "bg-success";
                                if ($largoSqlServer > 1 && $largoSqlServer <= 5)  $colorAux = "bg-warning";
                                if ($largoSqlServer >= 6)  $colorAux = "bg-danger";
                                ?>
                                <b class="d-block">SqlServer: <?php echo "<span class='badge " . $colorAux . "''>" . $largoSqlServer . "</span>" ?> </b>
                                <button type="button" class="btn btn-outline-info" onclick="verInfo('divSqlServerVista')">Ver Info</button>
                                <div class="row" id="divSqlServerVista" tittle="Procesos SQL Server." style="overflow:auto;">
                                    <div class="col-md-12">
                                        <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                            <?php foreach ($sqlServer as $x) {
                                                $sqlServer = $sqlServer;;
                                                echo " - " . trim($x) . "<br>";
                                            }  ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- sql_back -->

                        <div class="row">
                            <div class="col-md-12">
                                <?php
                                $colorAux = "bg-success";
                                if ($largoSqlBack > 1 && $largoSqlBack <= 5)  $colorAux = "bg-warning";
                                if ($largoSqlBack >= 6)  $colorAux = "bg-danger";
                                ?>
                                <b class="d-block">sql_back: <?php echo "<span class='badge " . $colorAux . "''>" . $largoSqlBack . "</span>" ?> </b>
                                <button type="button" class="btn btn-outline-info" onclick="verInfo('divSqlBackVista')">Ver Info</button>
                                <div class="row" id="divSqlBackVista" tittle="Procesos sql_back." style="overflow:auto;">
                                    <div class="col-md-12">
                                        <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                            <?php foreach ($sql_Back as $x) {
                                                $sql_Back = $sql_Back;;
                                                echo " - " . trim($x) . "<br>";
                                            }  ?>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>



                        <!-- Summarizador -->
                        <div class="row">
                            <div class="col-md-12">
                                <b class="d-block">Summarizador: <?php echo $largoSummarizador ?> </b>
                                <button type="button" class="btn btn-outline-info" onclick="verInfo('divSummVista')">Ver Info</button>
                                <div class="row" id="divSummVista" tittle="Summarizador" style="overflow:auto;">
                                    <div class="col-md-12">
                                        <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                            <?php foreach ($proceSumarizador as $x) {
                                                $proceSumarizador   = $proceSumarizador;;
                                                echo " - " . trim($x) . "<br>";
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
                                <?php
                                $colorAux = "bg-success";
                                if ($largoNtp > 2 && $largoNtp <= 5)  $colorAux = "bg-warning";
                                if ($largoNtp >= 6)  $colorAux = "bg-danger";
                                ?>
                                <b class="d-block">Ntp: <?php echo "<span class='badge " . $colorAux . "''>" . $largoNtp . "</span>" ?> </b>
                                <button type="button" class="btn btn-outline-info" onclick="verInfo('divNtpVista')">Ver Info</button>

                                <div class="row" id="divNtpVista" tittle="NTP" style="overflow:auto;">
                                    <div class="col-md-12">
                                        <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                            <?php foreach ($Ntp as $x) {
                                                $Ntp   = $Ntp;;
                                                echo " - " . trim($x) . "<br>";
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
                                                echo " - " . trim($x) . "<br>";
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


    </div>

</div>









<script>
    $("#divSumarizadorVista").fadeOut()
    $("#divDailyVista").fadeOut()
    $("#divJamsVista").fadeOut()
    $("#divImpoVista").fadeOut()
    $("#divSqlServerVista").fadeOut()
    $("#divSqlBackVista").fadeOut()
    $("#divSummVista").fadeOut()
    $("#divNtpVista").fadeOut()
    $("#divReconcileVista").fadeOut()
    $("#divBackupSecVista").fadeOut()
    $("#divIdleVista").fadeOut()
    $("#divTopCVista").fadeOut()
    $("#divTopCSecVista").fadeOut();


    function verInfo(id) {
        // $("#" + id + "").toggle() //cambiar luego a fadeIn
        $("body").append("<span id='btnModalFaena' data-toggle='modal' data-target='#modalLarge'>  </span>");
        $("#btnModalFaena").click();
        $("#btnModalFaena").remove();
        $("#modalLargeBody").html($("#" + id).html());
        $("#modalLargeTittle").html($("#" + id).attr("tittle"));
    }
</script>




<script>
    // ---------------- GRAFICOS ---------------
    $('.GraficoAzul').knob({
        readOnly: true,
        rotation: 'anticlockwise',
        thickness: '.3',
        width: 90,
        height: 90,
        fgColor: '#3c8dbc'
    });
    $('.GraficoVerde').knob({
        readOnly: true,
        rotation: 'anticlockwise',
        thickness: '.3',
        width: 90,
        height: 90,
        fgColor: '#09DA06'
    });
    $('.GraficoRojo').knob({
        readOnly: true,
        rotation: 'anticlockwise',
        thickness: '.3',
        width: 90,
        height: 90,
        fgColor: 'red'
    });
    $('.GraficoAmarillo').knob({
        readOnly: true,
        rotation: 'anticlockwise',
        thickness: '.3',
        width: 90,
        height: 90,
        fgColor: '#ffc107'
    });


    var ctx = document.getElementById("cpuServer1").getContext("2d");
    var ctx2 = document.getElementById("cpuServer2").getContext("2d");

    var dataS1 = {
        labels: ["7''", "6''", "5''", "4''", "3''", "2''", "1''"],
        datasets: [{
            label: "valor",
            backgroundColor: "<?php echo $graficocpubg ?>",
            borderColor: "<?php echo $graficocpubr ?>",
            pointRadius: false,
            pointColor: "#3b8bba",
            pointStrokeColor: "<?php echo $graficocpust ?>",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "<?php echo $graficocpult ?>",
            data: [<?php echo $datosGraficoCpu[6] ?>,
                <?php echo $datosGraficoCpu[5] ?>,
                <?php echo $datosGraficoCpu[4] ?>,
                <?php echo $datosGraficoCpu[3] ?>,
                <?php echo $datosGraficoCpu[2] ?>,
                <?php echo $datosGraficoCpu[1] ?>,
                <?php echo $datosGraficoCpu[0] ?>
            ]
        }]
    };
    var dataS2 = {
        labels: ["7''", "6''", "5''", "4''", "3''", "2''", "1''"],
        datasets: [{
            label: "valor",
            backgroundColor: "<?php echo $graficocpubgSec ?>",
            borderColor: "<?php echo $graficocpubrSec ?>",
            pointRadius: false,
            pointColor: "#3b8bba",
            pointStrokeColor: "<?php echo $graficocpustSec ?>",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "<?php echo $graficocpultSec ?>",
            data: [<?php echo $datosGraficoCpuSec[6] ?>,
                <?php echo $datosGraficoCpuSec[5] ?>,
                <?php echo $datosGraficoCpuSec[4] ?>,
                <?php echo $datosGraficoCpuSec[3] ?>,
                <?php echo $datosGraficoCpuSec[2] ?>,
                <?php echo $datosGraficoCpuSec[1] ?>,
                <?php echo $datosGraficoCpuSec[0] ?>
            ]
        }]
    };
    var myChart = new Chart(ctx, {
        type: "line",
        data: dataS1,
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
                    }
                }]
            },
            legend: {
                display: false
            }
        }
    });

    var myChart2 = new Chart(ctx2, {
        type: "line",
        data: dataS2,
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
                    }
                }]
            },
            legend: {
                display: false
            }
        }
    });
</script>


<script>

</script>
<script>
    /*
    var isLoading = false;
    function autom() {
        if (!isLoading) {
            isLoading = true;
            cargaMonitoreo(<?php echo $id_faena ?>)
            setTimeout(function() {
                isLoading = false;
            }, 5000);
        }
    }

    $(document).ready(function() {
        setInterval(autom, 5000);
    });
    */
</script>