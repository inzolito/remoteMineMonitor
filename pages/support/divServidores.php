<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$faenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();

$idFaenaS = $_REQUEST["idf"];

date_default_timezone_set('America/Santiago');

//Falta validar cuando no existe la faena
$faenaDatosS = $faenaCl->datos($idFaenaS);
$estadoCheckFaenaS = $faenaCl->estado($idFaenaS);
$checkDatosS = $faenaCl->datosCheck($idFaenaS);

//array asociativo minas
$arrayFaenasDatos = array(
    "amant" => array(
        "cpuLimite" => 4.6,
        "cpuMedio" => 3,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2.6
    ),
    "cndrt" => array(
        "cpuLimite" => 4.6,
        "cpuMedio" => 3,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2.6
    ),
    "cndmh" => array(
        "cpuLimite" => 4.6,
        "cpuMedio" => 3,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2.6
    ),
    "crsten" => array(
        "cpuLimite" => 4.6,
        "cpuMedio" => 3,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2.6
    ),
    "aas" => array(
        "cpuLimite" => 4.6,
        "cpuMedio" => 3,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2.6
    ),
    "mlcc" => array(
        "cpuLimite" => 4.6,
        "cpuMedio" => 3,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2.6
    ),
    "magsa" => array(
        "cpuLimite" => 4,6,
        "cpuMedio" => 2,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2,6
    ),

    "amcen" => array(
        "cpuLimite" => 4.6,
        "cpuMedio" => 3,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2.6
    ),
    "cnchuq" => array(
        "cpuLimite" => 4.6,
        "cpuMedio" => 3,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2.6
    ),
    "cnms" => array(
        "cpuLimite" => 4.6,
        "cpuMedio" => 3,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2.6
    ),
    "capcnn" => array(
        "cpuLimite" => 4.6,
        "cpuMedio" => 3,
        "cpuLimiteSec" => 4,
        "cpuMedioSec" => 2.6
    )
);

// leer logs
$carpetaS = $faenaDatosS->alias . "/";
$rutaS = "/home/jigsaw/monitoreoRemoto/" . $carpetaS;

$pingServerAct = file($rutaS . "pingServerAct.log");
$pingServerSec = file($rutaS . "pingServerSecundario.log");
$pingServerActLectura = file($rutaS . "pingServerActLectura.log");
$pingServerSecLectura = file($rutaS . "pingServerSecundarioLectura.log");
$estadoDisco = file($rutaS . "estadoServidorMon.log");
$estadoDiscoSecundario = file($rutaS . "estadoServidorSecMon.log");
$JamsCluster = file($rutaS . "JamsClusterMon.log");
$JamsClusterSecundario = file($rutaS . "JamsClusterSecMon.log");
$loadAveragePrimario = file($rutaS . "loadAverageMon.log");
$loadAverageSecundario = file($rutaS . "loadAverageSecMon.log");
$RamMemTotalPrimario = file($rutaS . "MemTotalMon.log");
$RamMemTotalSecundario = file($rutaS . "MemTotalSecMon.log");
$RamMemUsadaPrimario = file($rutaS . "MemUsadaMon.log");
$RamMemUsadaSecundario = file($rutaS . "MemUsadaSecMon.log");
$cpuGraficoPrimario = file($rutaS . "cpuGraficoMon.log");
$cpuGraficoSecundario = file($rutaS . "cpuGraficoSecMon.log");
$TopC = file($rutaS . "TopCMon.log");
$TopCSec = file($rutaS . "TopCSecMon.log");/**/
$versionRuby = file($rutaS."versionRubyMon.log");


$i = 0;

// nom serv siempre sera en la posicion 2
//nombre del servidor primario activo
$nombreServidorActivo = $estadoDisco[2];
//estado servidor activo
$estadoServidorActivo = $estadoDisco[1];

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
$graficocpubg = "rgba(15,204,4)";
$graficocpust = "rgba(15,204,4)";
$graficocpult = "rgba(15,204,4)";
$graficocpubr = "rgba(15,204,4)";



$cpuMedioAux = $arrayFaenasDatos[$faenaDatosS->alias]["cpuMedio"];
$cpuLimiteAux = $arrayFaenasDatos[$faenaDatosS->alias]["cpuLimite"];

switch (true) {
    case ($datosGraficoCpu[0] > $cpuMedioAux && $datosGraficoCpu[0] < $cpuLimiteAux):

        $graficocpust = "rgba(255, 195, 0, 1)";
        $graficocpubg = "rgba(255, 195, 0, 1)";
        $graficocpult = "rgba(255, 195, 0, 1)";
        $graficocpubr = "rgba(255, 195, 0, 1)";
       // echo "<script> agregarAlertaFaena('CPU servidor primario', 'load alto', 'error'); </script>";
        break;

    case ($datosGraficoCpu[0] >= $cpuLimiteAux):

        $graficocpust = "rgba(245, 39, 39, 1)";
        $graficocpubg = "rgba(245, 39, 39, 1)";
        $graficocpult = "rgba(88, 1, 1, 1)";
        $graficocpubr = "rgba(195, 11, 11, 1)";
        //echo "<script> agregarAlertaFaena('CPU servidor primario', 'load  primario muy alto', 'error'); </script>";
        //echo '<audio autoplay>';
        //echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
        //echo '</audio>';
        break;

    default:
        //echo "<script> eliminarAlertaFaena('CPU servidor primario'); </script>";

        break;
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
$graficocpubgSec = "rgba(15,204,4)";
$graficocpustSec = "rgba(15,204,4)";
$graficocpultSec = "rgba(15,204,4)";
$graficocpubrSec = "rgba(15,204,4)";
 

$cpuMedioAux = $arrayFaenasDatos[$faenaDatosS->alias]["cpuMedioSec"];
$cpuLimiteAux = $arrayFaenasDatos[$faenaDatosS->alias]["cpuLimiteSec"];

switch (true) {
    case ($datosGraficoCpuSec[0] > $cpuMedioAux && $datosGraficoCpuSec[0] < $cpuLimiteAux):
        $graficocpustSec = "rgba(255, 195, 0, 1)";
        $graficocpubgSec = "rgba(255, 195, 0, 1)";
        $graficocpultSec = "rgba(255, 195, 0, 1)";
        $graficocpubrSec = "rgba(255, 195, 0, 1)";
        //echo "<script> agregarAlertaFaena('CPU servidor Secundario', ' Load sec muy alto', 'error'); </script>";
        break;

    case ($datosGraficoCpuSec[0] >= $cpuLimiteAux):
        $graficocpustSec = "rgba(245, 39, 39, 1)";
        $graficocpubgSec = "rgba(245, 39, 39, 1)";
        $graficocpultSec = "rgba(88, 1, 1, 1)";
        $graficocpubrSec = "rgba(195, 11, 11, 1)";
        //echo "<script> agregarAlertaFaena('CPU servidor Secundario', 'load  sec muuuuuuuuuuuuy alto', 'error'); </script>";
        //echo '<audio autoplay>';
        //echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
        //echo '</audio>';
        break;
    default:
        //echo "<script> eliminarAlertaFaena('CPU servidor Secundario'); </script>";

        break;
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

//conseguir fecha actual
$fechaActualVisual = "<i class='fas fa-calendar'></i>" . date("d");
$horaActualVsual = "<i class='fas fa-clock ml-1'></i>" . date("H:i");
$dataTimeVisual = $fechaActualVisual . " " . $horaActualVsual;

//color grafico dona
$porcentajes = array(
    "discoS1" => $porc,
    "discoS2" => $porcSec,
    "ramS1" => $porcentajeRam,
    "ramS2" => $porcentajeRamSec
);

$graficoDonutColor = array();

// Asignar valores para discos
foreach (array("discoS1", "discoS2") as $key) {
    if ($porcentajes[$key] <= 75) {
        $graficoDonutColor[$key] = "GraficoVerde";
    } elseif ($porcentajes[$key] <= 85) {
        $graficoDonutColor[$key] = "GraficoAmarillo";
    } else {
        $graficoDonutColor[$key] = "GraficoRojo";
        //echo '<audio autoplay>';
        //echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
        //echo '</audio>';
    }
}

// Asignar valores para RAM
foreach (array("ramS1", "ramS2") as $key) {
    if ($porcentajes[$key] <= 70) {
        $graficoDonutColor[$key] = "GraficoVerde";
    } elseif ($porcentajes[$key] <= 80) {
        $graficoDonutColor[$key] = "GraficoAmarillo";
    } else {
        $graficoDonutColor[$key] = "GraficoRojo";
        //echo '<audio autoplay>';
        //echo '<source src="pages/support/sonido/ping_missing.mp3" type="audio/mp3">';
        //echo '</audio>';
    }
}

//validar ping server activo
$largoPing = count($pingServerAct);
/*
$validarPing = explode("%", $pingServerAct[9]);
$validarPing = explode(",",$validarPing[0]);
$validarPing = $validarPing[2];
    if ($validarPing <= 40 && $validarPing !="") {
        $icono= "<p class='float-right' style='font-size:14px'><i class='fa-solid text-success fa-circle ml-3'></i> </p>";
        $colorborde="card card-navy";
    }else{
        $icono = "<p class='float-right' style='font-size:14px'><i class='fa-solid text-danger fa-circle ml-3'></i> </p>";
        $colorborde="card card-light";
    }

*/


//validar ping server secundario
$largoPingSec = count($pingServerSec);
/*
$validarPingSec = explode("%", $pingServerSec[9]);
$validarPingSec = explode(",",$validarPingSec[0]);
$validarPingSec = $validarPingSec[2];
  if ($validarPingSec <= 40 && $validarPingSec !="") {
      $iconoSec= "<p class='float-right' style='font-size:14px'><i class='fa-solid text-success fa-circle ml-3'></i> </p>";
      $colorbordeSec="card card-navy"; 
  }else{
      $iconoSec = "<p class='float-right' style='font-size:14px'><i class='fa-solid text-danger fa-circle ml-3'></i> </p>";
      $colorbordeSec="card card-light";
  }
*/


// hay que cambiar esta logica // lo comente de momento ya que al momento de cambiar la funcion oculta los DIV

$claseIcono=$system -> iconStatusConexionFaena($faenaDatosS->alias);

if(strpos($claseIcono, "class='fas fa-wifi text-success mr-2'") !== false){
    echo "<script> iconoOnline(1) </script>";
}else{
    echo "<script> iconoOnline(0) </script>";
}

$largoEstadoDisco=count($estadoDisco);
$largoEstadoDiscoSec=count($estadoDiscoSecundario);

$claseDiv = "card card-navy";
$puntoAuxPrim=$system->validarLog($estadoDisco,20);
$puntoAux= $system->validarLog($estadoDiscoSecundario,20);
if(strpos($puntoAux,"success") !== false){
    if(strpos($estadoServidorSecundario,"stopped") !== false){
        $claseDiv = "card card-gray";
        
    }else{
    }
}else{
    $claseDiv = "card card-light";
}

$claseDivPrim = "card card-navy";
if(strpos($puntoAuxPrim,"success") !== false){

}else{
    $claseDivPrim = "card card-light";
}


?>


<div class="row">
    <!-- Servidor 1 x 2  .... Server primario-->
    <div class="col-md-6">
        <div class='<?php echo $claseDivPrim ?>'>
            <div class="card-header">
                <h3 class="card-title">Servidor primario <?php echo $system->validarLog($estadoDisco,20) ?> </h3>
                <div class="card-tools">
                    <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual ?></span>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-left">Disco Duro </div>
                        <input type="text" value=<?php echo $porc  ?> class="<?php echo $graficoDonutColor["discoS1"] ?>" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">

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
                        <div class="text-left">Memoria Ram</div>
                        <input type="text" value=<?php echo $porcentajeRam  ?> class="<?php echo $graficoDonutColor["ramS1"] ?>" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                        <table class="table table-bordered">
                            <tbody>
                                <div class="progress-group mt-2 px-2">
                                    Ram<span class="float-right"><b><?php echo round($ramUsadaTotal / 1024, 1)   ?></b>/<?php echo round($ramTotal / 1024, 1)."G"   ?>
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
                        
                        <div class="row"> 
                            <div class="col-md-2" style="padding-top:5px !important;"><?php echo $system->validarLog($TopC,6,"right")?></div>
                            <div class="col-md-10">
                                CPU - Load Average: <?php echo $loadAveragePrimario  ?> 
                                <button type="button" id="btnTopC" onclick="verInfo('divTopCVista')" class="btn btn-outline-light btn-sm pt-0 pb-0 ml-1"> <i class='fas fa-eye fa-solid mr-1 fa-eye'></i></button>
                            </div>
                        </div>
                        

                        <div style="display:none">
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
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-4">
                        <p class="text-sm  ">Nom. Server
                            <b class="d-block"> <?php echo $nombreServidorActivo   ?></b>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="text-sm  ">IP
                            <b class="d-block"><?php echo $ipServerPrimario   ?> </b>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="text-sm  ">Status
                            <b class="d-block"><?php echo $estadoServidorActivo  ?> </b>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Servidor 1 x 2  .... Server Secundario-->
    <div class="col-md-6">
        <div class='<?php echo $claseDiv ?>'>
            <div class="card-header">
                <h3 class="card-title">Servidor secundario <?php echo $system->validarLog($estadoDiscoSecundario,20) ?></h3>
                <div class="card-tools">
                    <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual ?></span>
                </div>
            </div>

            <div class="card-body">

                <div class="row">
                    <div class="col-md-3">
                        <div class="text-left">Disco Duro</div>
                        <input type="text" value=<?php echo $porcSec  ?> class="<?php echo $graficoDonutColor["discoS2"] ?>" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
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
                        <div class="text-left">Memoria Ram</div>
                        <input type="text" value=<?php echo $porcentajeRamSec  ?> class="<?php echo $graficoDonutColor["ramS2"] ?>" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                        <table class="table table-bordered">
                            <tbody>
                                <div class="progress-group mt-2 px-2">
                                    Ram<span class="float-right"><b><?php echo round($ramUsadaTotalSec / 1024, 1)   ?></b>/<?php echo round($ramTotalSec / 1024, 1)."G"  ?>
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
                        <div class="row"> 
                            <div class="col-md-2" style="padding-top:5px !important;"><?php echo $system-> validarLog($TopCSec,6,"right")?></div>
                            <div class="col-md-10">
                                CPU - Load Average: <?php echo $loadAverageSecundario ?> 
                                <button type="button" id="btnTopC" onclick="verInfo('divTopCSecVista')" class="btn btn-outline-light btn-sm pt-0 pb-0 ml-1"> <i class='fas fa-eye fa-solid mr-1 fa-eye'></i></button>
                            </div>
                        </div>
                        


                        <div style="display:none">
                            <div class="row" id="divTopCSecVista" tittle="TopCSec" style="overflow:auto;">
                                <div class="col-md-12">
                                    <div class="container-fluid bg-dark text-white p-3" style="border-radius: 5px;overflow:auto;">
                                        <table class="table">
                                            <tbody>

                                                <?php
                                                $i = 0;
                                                foreach ($TopCSec as $x) {
                                                    if ($i > 6) {

                                                        echo "<tr>";
                                                        // $TopC = $TopC;
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
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-4">
                        <p class="text-sm  ">Nom. Server
                            <b class="d-block"><?php echo $nombreServidorSecundario   ?> </b>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="text-sm  ">IP
                            <b class="d-block"><?php echo $ipServerSec   ?> </b>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="text-sm  ">status
                            <b class="d-block"><?php echo $estadoServidorSecundario   ?> </b>
                        </p>
                    </div>
                </div>

            </div>


        </div>

    </div>
</div>





<style>
    input.GraficoVerde+div>div:nth-child(2):after {
        content: "%";
        font-size: 25px;
    }
</style>


<script>
    $(document).ready(function() {
        $("#divTopCVista").fadeOut()
        $("#divTopCSecVista").fadeOut()
       // mensajeAlertasFaena()
    });

    function verInfo(id) {
        // $("#" + id + "").toggle() //cambiar luego a fadeIn
        $("body").append("<span id='btnModalFaena' data-toggle='modal' data-target='#modalLarge'>  </span>");
        $("#btnModalFaena").click();
        $("#btnModalFaena").remove();
        $("#modalLargeBody").html($("#" + id).html());
        $("#modalLargeTittle").html($("#" + id).attr("tittle"));
    }

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
        displayInput: true,
        fgColor: '#09DA06',
        draw: function () {
        // Obtiene el valor del knob
        var value = $(this.i).val();

        // Agrega el signo de porcentaje
        $(this.i).val(value + '%');
        }
    });
    $('.GraficoRojo').knob({
        readOnly: true,
        rotation: 'anticlockwise',
        thickness: '.3',
        width: 90,
        height: 90,
        fgColor: 'red',
        draw: function () {
        // Obtiene el valor del knob
        var value = $(this.i).val();

        // Agrega el signo de porcentaje
        $(this.i).val(value + '%');
    }
    });
    $('.GraficoAmarillo').knob({
        readOnly: true,
        rotation: 'anticlockwise',
        thickness: '.3',
        width: 90,
        height: 90,
        fgColor: '#ffc107',
        draw: function () {
        // Obtiene el valor del knob
        var value = $(this.i).val();

        // Agrega el signo de porcentaje
        $(this.i).val(value + '%');
    }
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
        animation: {
            duration: 0 // Desactiva la animación
        },
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
                        max: 5,
                        min: 0
                    }
                }]
            },
            legend: {
                display: false
            },
            animation: {
                duration: 0 // Desactiva la animación
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
                    },
                    ticks: {
                        max: 5,
                        min: 0
                    }
                }]
            },
            legend: {
                display: false
            },

            animation: {
                duration: 0 // Desactiva la animación
            }
        }
    });
</script>