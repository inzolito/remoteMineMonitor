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
//array asociativo minas
$arrayFaenasDatos = array(
    "amant" => array(
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ),
    "amcen" => array(
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ),
    "cndrt" => array(
        "dbPrimaria" => "jmineops_prod",
        "dbSecundaria" => "jmineops_prod"
    ),
    "cndmh" => array(
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ), 
    "crsten" => array(
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ),
    "aas" => array(
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ),
    "mlcc" => array(
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    ),
    "magsa" => array(
        "dbPrimaria" => "jmineops_prod",
        "dbSecundaria" => "jmineops_prod"
    ),
    "cnms" => array(
        "dbPrimaria" => "jmineops_prod",
        "dbSecundaria" => "jmineops_prod"
    ),
    "cnchuq" => array(
        "dbPrimaria" => "jmineops_prod",
        "dbSecundaria" => "jmineops_prod"
    ),
    "capcnn" => array(
        "dbPrimaria" => "jmineops",
        "dbSecundaria" => "jmineops"
    )
);
$servidorlog = 0;
// leer logs
$carpeta = $faenaDatos->alias . "/";
$ruta = "/home/jigsaw/monitoreoRemoto/" . $carpeta;

$schemaSecundario = file($ruta . "schemaInfoSec.log");
$sqlSizePrimario = file($ruta . "tamanoBD.log");
$sqlSizeSecundario = file($ruta . "tamanoBDSec.log");
$shiftsPrimario = file($ruta . "queryResults.log");
$shiftsSecundario = file($ruta . "queryResults_peer.log");
$BdSize = file($ruta . "queryActivity.log");
$top10BD = file($ruta . "topTenTablasPostgresLectura.log");
$tamanoBdPrimarioL = file($ruta . "tamanoTablasPostgresLectura.log");
$tamanoBdSecundarioL = file($ruta . "tamanoTablasPostgresSecLectura.log");
$tamanoBdPrimario = file($ruta . "tamanoTablasPostgres.log");
$tamanoBdSecundario = file($ruta . "tamanoTablasPostgresSec.log");
 

//conseguir fecha actual
$fechaActualVisual = "<i class='fas fa-calendar'></i>" . date("d");
$horaActualVsual= "<i class='fas fa-clock ml-1'></i>" . date("H:i");
$dataTimeVisual = $fechaActualVisual." ".$horaActualVsual;

//largo top 10 tablas mas pesadas
$largoTop10Tablas = count($top10BD);
//largo tablas shifts primario y secundario
$largoTablasShiftAct = count($tamanoBdPrimario);
$largoTablasShiftSec = count($tamanoBdSecundario);


// validar color del div top 10
/*
if($largoTop10Tablas >= 2){
    $icono= "<p class='float-right' style='font-size:14px'><i class='fa-solid text-success fa-circle ml-3'></i> </p>";
    $colorborde="card card-navy";
}else{
    $icono= "<p class='float-right' style='font-size:14px'><i class='fa-solid text-danger fa-circle ml-3'></i> </p>";
    $colorborde="card card-light";
}
*/

//validar color div bd 
if($largoTablasShiftAct >=2 && $largoTablasShiftSec >=2){
    $colorbordeT="card card-navy";
}else{
    $colorbordeT="card card-light";
}


?>
 
        <div class="row">
            <!-- Datos de la base de datos -->
            <div class="col-md-12">
                <div class='<?php echo $colorbordeT?>'>
                    <div class="card-header" data-card-widget="collapse">
                        <h3 class="card-title">Base de datos</h3>
                        <div class="card-tools">
                            <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual?></span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                 
                            </div>
                        </div>
                        <div class="row">
                                <div class="col-md-3"><i class='fa-solid  fa-server'></i> server 1<?php echo $system -> validarLog($tamanoBdPrimario,8)?></div>
                                <div class="col-md-3"><i class='fa-solid  fa-server'></i> server 1<?php echo $system -> validarLog($sqlSizePrimario,8)?></div>
                                <div class="col-md-3"><i class='fa-solid  fa-server'></i> server 2 <?php echo $system -> validarLog($tamanoBdSecundario,8)?></div>
                                <div class="col-md-3"><i class='fa-solid  fa-server'></i> server 2 <?php echo $system -> validarLog($sqlSizeSecundario,8)?></div>
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
                            $countTablasShift = file($ruta . "tamanoTablasPostgresLectura.log");
                            $countTablasShiftSec = file($ruta . "tamanoTablasPostgresSecLectura.log");
                            //$countTablasShift = file($ruta . "tamanoTablasSegundaOp.log");
                            //$countTablasShiftSec = file($ruta . "tamanoTablasSegundaOpSec.log");
                            
                            //Array llenado datos log primario
                            $countTablas = array();
                            $tablaAux = "";
                            $largoCountTablasSift = count($countTablasShift);
                            //Fecha del log de las tamanoTablasPostgres
                            $fechaCountTablasPostgres=$countTablasShift[0];
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
                                        <td >Tamaño BD</td>
                                        <td><?php echo $tamanoBd . "GB"   ?></td>
                                        <td><?php echo $tamanoBdSec . "GB"    ?></td>
                                        <td><span><?php echo abs($tamanoBd - $tamanoBdSec) . " GB"    ?></span></td>
                                    </tr>

                                    <?php
                                    $tablaAux = "";
                                    $largoCountTablasSiftSec = count($countTablasShiftSec);
                                    for ($x = 3; $x < $largoCountTablasSiftSec - 2; $x++) {
                                        $tablaAux = explode("|", $countTablasShiftSec[$x]);
                                        $restaAux=abs($countTablas[$tablaAux[0]]-$tablaAux[1]);

                                        $colorAux = "bg-success";
                                        if ($restaAux > 19 && $restaAux <= 49)  $colorAux = "bg-warning";
                                        if ($restaAux >= 50)  $colorAux = "bg-danger";

                                        echo "<tr>";
                                            echo "<td>".$tablaAux[0]."</td>";
                                            echo "<td>".$countTablas[$tablaAux[0]]."</td>";
                                            echo "<td>".$tablaAux[1]."</td>";
                                            echo "<td><span class='badge ".$colorAux ."''>".$restaAux."</span></td>";
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
                <div class='<?php echo $system->pintarDiv($largoTop10Tablas)?>'>
                    <div class="card-header" data-card-widget="collapse" >
                        <h3 class="card-title">Top 10 tablas con mayor peso  <?php echo $system->validarLog($top10BD,16)?></h3>
                        <div class="card-tools">
                            <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual?></span>
                        </div>
                    </div>

                    <div class="card-body"  >
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

                                    if ($x > 2 && $x < 13) {

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


             
        </div>




 

 