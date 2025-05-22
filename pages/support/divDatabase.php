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

$schemaInfoSec = file($ruta . "schemaInfoSec.log");
$sqlSizePrimario = file($ruta . "tamanoBDLectura.log");
$sqlSizeSecundario = file($ruta . "tamanoBDSecLectura.log");


$BdSize = file($ruta . "queryActivity.log");
$top10BD = file($ruta . "topTenTablasPostgresLectura.log");
$tamanoBdPrimarioL = file($ruta . "tamanoTablasPostgresLectura.log");
$tamanoBdSecundarioL = file($ruta . "tamanoTablasPostgresSecLectura.log");
$tamanoBdPrimario = file($ruta . "tamanoTablasPostgres.log");
$tamanoBdSecundario = file($ruta . "tamanoTablasPostgresSec.log");
$consultaIdle = file($ruta . "consultasIdle.log");
$primaryKeyMaxActivo = file($ruta . "primaryKeyTablesMon.log");
$rotationTableLog = file($ruta . "rotationTable.log");
$largoSchemaInfo = count($schemaInfoSec);


$rotationTableData = $rotationTableLog[3];

//conseguir fecha actual
$fechaActualVisual = "<i class='fas fa-calendar'></i>" . date("d");
$horaActualVsual = "<i class='fas fa-clock ml-1'></i>" . date("H:i");
$dataTimeVisual = $fechaActualVisual . " " . $horaActualVsual;

//largo top 10 tablas mas pesadas
$largoTop10Tablas = count($top10BD);
//largo tablas shifts primario y secundario
$largoTablasShiftAct = count($tamanoBdPrimario);
$largoTablasShiftSec = count($tamanoBdSecundario);


$validarTiempoLogs = $system->validarLog($tamanoBdPrimarioL, 10) . $system->validarLog($sqlSizePrimario, 10) . $system->validarLog($tamanoBdSecundarioL, 10) . $system->validarLog($sqlSizeSecundario, 10);

//validar color div bd 
if (strpos($validarTiempoLogs, "text-danger")) {
    $colorbordeT = "card card-danger";
} else {
    $colorbordeT = "card card-navy";
}


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

//cambio de color boton IDLE
switch ($validarIdle) {
    case "OK":
        $classBotonIdle = "btn btn-success btn-block";
        break;
    default:
        $classBotonIdle = "btn btn-danger btn-block";
}







?>

<div class="row">
    <!-- Datos de la base de datos -->


    <div class="col-md-12">
        <div class='<?php echo $colorbordeT ?>'>
            <div class="card-header" data-card-widget="collapse">
                <h3 class="card-title">Base de datos servidores.</h3>
                <div class="card-tools">
                    <span class="badge" style='font-size: 1.0em'><?php echo $dataTimeVisual ?></span>
                </div>
            </div>

            <div class="card-body">

                <?php
                // Calculos base de datos
                //tamaño Bd

                $tamanoBd = 0;
                $largoBd = count($sqlSizePrimario);

                for ($x = $largoBd; $x > 0; $x--) {
                    if (isset($sqlSizePrimario[$x])) {

                        $arrayAux = explode("|", $sqlSizePrimario[$x]);
                        $nombreDBPrimRow = preg_replace('/\s+/', "", $arrayAux[0]);
                        if ($nombreDBPrimRow == "jmineops" || $nombreDBPrimRow == "jmineops_prod") {
                            $tamanoBd = str_replace("GB", "", $arrayAux[1]);
                            $x = -1;
                        }
                    }
                }

                //echo "tamano bd = ".$tamanoBd;


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
                $fechaCountTablasPostgres = $countTablasShift[0];
                //llenado array 
                for ($x = 0; $x < $largoCountTablasSift - 2; $x++) {
                    $tablaAux = explode("|", $countTablasShift[$x]);
                    $countTablas[$tablaAux[0]] = $tablaAux[1];
                }
                //Validacion final =
                if ($tamanoBd == 0) $tamanoBd = "<i class='fa-solid fa-check'></i>";




                ?>



                <?php
                // Calculos generales datos database resumen.


                //  validador del log de schema info
                $validarSchemaInfo = $system->validarLog($schemaInfoSec, 6);

                if (strpos($validarSchemaInfo, "text-danger") !== false) $color  = "badge btn-default disabled p-2 btn-block";


                $pattern = '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/';
                $columnas = explode('|', $schemaInfoSec[3]);
                $fechaSchemaInfo = ($columnas[count($columnas) - 1]);

                //  echo $fechaSchemaInfo;

                $currentDateTime = new DateTime();
                $schemaDateTime = new DateTime($fechaSchemaInfo);
                $schemaDateTime->setTimezone(new DateTimeZone('America/New_York')); // Cambia 'America/New_York' por tu zona horaria

                $schemaDateTime = date_create($fechaSchemaInfo, new DateTimeZone('UTC'));
                date_timezone_set($schemaDateTime, new DateTimeZone('America/Santiago'));
                $schemaInfoDatePrint = date_format($schemaDateTime, 'y/m/d H:i');

                $color = "btn btn-success btn-block";

                $interval = $currentDateTime->diff($schemaDateTime);
                $minutesPassed = $interval->i + $interval->h * 60 + $interval->d * 24 * 60;

                if ($minutesPassed >= 0 && $minutesPassed <= 10) {
                    $mensajeSchema = " <b>bkp hace </b> <br>" . $minutesPassed . " Min.";
                } else {

                    $mensajeSchema = "bkp hace  " . $minutesPassed . " Min.";
                    $color = "btn btn-danger btn-block";

                    // ---- Insert Alerta
                    $mensajeAlerta = "La replica al servidor secundario no se completa desde mas de   " . $minutesPassed . " Minutos ";
                    $alertas->insertAlert($id_faena, "HCXSCHI001", $mensajeAlerta);
                    //--------------------
                }


                ?>



                <div class="row">
                    <div class="col-md-12">
                        <div class="info-box mb-12 border border-success">
                            <span class="info-box-icon" style="width: auto !important;">
                                <?php echo $system->validarLog($rotationTableLog, 10) ?>
                                <i class="far fa-clocks " style="font-size: 10px;"></i></span>
                            <div class="info-box-content">

                                <span class="info-box-text">Tabla Rotations</span>
                                <span class="info-box-number"><?php echo $rotationTableData  ?></span>
                            </div>

                        </div>
                    </div>




                </div>




                <div class="row">
                    <div class="col-md-5">
                        <div class="info-box mb-3 border border-success">
                            <span class="info-box-icon" style="width: auto !important;">
                                <?php echo $system->validarLog($tamanoBdPrimarioL, 10) ?>
                                <i class="far fa-database"></i></span>
                            <div class="info-box-content">

                                <span class="info-box-text">Serv. Activo</span>
                                <span class="info-box-number"><?php echo $tamanoBd . " GB"  ?></span>
                            </div>

                        </div>
                    </div>


                    <div class="col-md-7">
                        <div class="info-box mb-3 border border-success">
                            <span class="info-box-icon" style="width: auto !important;">
                                <?php echo $system->validarLog($tamanoBdPrimarioL, 10) ?>
                                <i class="fa fa-database"></i>
                            </span>
                            <div class="info-box-content">
                                <div class="row">
                                    <!-- Columna para Backup -->
                                    <div class="col-6">
                                        <span class="info-box-text">Serv. Backup</span>
                                        <span class="info-box-number"><?php echo $tamanoBdSec . " GB" ?></span>
                                    </div>
                                    <!-- Columna para Schema Info -->
                                    <div class="col-6">
                                        <span class="info-box-text">Schema Info</span>
                                        <span class="info-box-number"><?php echo $schemaInfoDatePrint ?></span>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>

                </div>



                <?php

                $lineaMaxId = $primaryKeyMaxActivo[3];
                $lineaMaxIdArray = explode("|", $lineaMaxId);
                $maxIdTablasInteger = $lineaMaxIdArray[4];
                $maxIdTablas = $lineaMaxIdArray[0] . " : " . number_format($lineaMaxIdArray[4], 0, ",", ".");
                $valorDesbordamientoTablas = 2147483647;
                $faltaParaElDesbordamiento = $valorDesbordamientoTablas - $lineaMaxIdArray[4];

                $bcpk = "border-color: #28a745 !important;";
                $classPK = " ";

                if ($faltaParaElDesbordamiento <= 15000000) {
                    $bcpk = "border-color: #dc3545 !important;";
                    $classPK = "bg-warning";
                    $bcpk = "";
                }
                if ($faltaParaElDesbordamiento <= 10000000) {
                    $bcpk = "border-color: #dc3545 !important;";
                    $classPK = "bg-danger";
                    $bcpk = "";
                }
                ?>




                <div class="row mt-1">
                    <!-- schema Max id primary key-->
                    <div class="col col-12">
                        <table class="table table-bordered <?php echo $classPK ?>">
                            <thead>
                                <th class="<?php echo $classPK  ?>" style="<?php echo $bcpk ?>">Tabla con mayor ID</th>
                                <th class="<?php echo $classPK  ?>" style="<?php echo $bcpk ?>">PK</th>
                                <th class="<?php echo $classPK  ?>" style="<?php echo $bcpk ?>">Max. Val. Permitido</th>
                                <th class="<?php echo $classPK  ?>" style="<?php echo $bcpk ?>">Falta para el limite</th>
                            </thead>

                            <tbody>
                                <?php
                                echo "
                                <td class='" . $classPK . "'  style='" . $bcpk . "' >" . $lineaMaxIdArray[0] . "</td>
                                <td class='" . $classPK . "'  style='" . $bcpk . "' >" . number_format($lineaMaxIdArray[4], 0, ",", ".") . "</td>
                                <td class='" . $classPK . "'  style='" . $bcpk . "' >" . number_format($valorDesbordamientoTablas, 0, ",", ".") . "</td>
                                <td class='" . $classPK . "'  style='" . $bcpk . "' >" . number_format($faltaParaElDesbordamiento, 0, ",", ".") . "</td>
                                ";
                                ?>
                            </tbody>
                        </table>



                        <div class="row">


                            <!-- schema info -->
                            <div class="col col-3  ">
                                <?php

                                ?>

                                <span class="<?php echo $color ?> ">
                                    <?php echo $system->validarLog($schemaInfoSec, 10) ?>
                                    <?php echo $mensajeSchema ?> </span>

                            </div>




                            <!-- Consultas en idle -->

                            <div class="col col-3  ">

                                <?php
                                if (strpos($validScriptIdle, "text-danger") !== false) $classBotonIdle  = "btn btn-block btn-default disabled p-2 btn-block pt-0 pb-0 ";
                                ?>
                                <button type="button" id="btnIdle" onclick="verInfo('divIdleVista')" class="<?php echo $classBotonIdle ?>">
                                    <?php echo $system->validarLog($consultaIdle, 10) ?>
                                    <?php
                                    echo "<b>Idle sql:</b> <br> " . $cantidadIdle . " ";
                                    ?>

                                </button>
                            </div>


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

                            <!-- Diff tablas -->
                            <!-- Poner punto online offline -->


                            <div class="col col-3  ">
                                <button type="button" id="btnDiffTablas" class="<?php echo $system->buttonClass("default") ?>">
                                    <?php echo $system->validarLog($sqlSizePrimario, 10) ?>
                                    <b>Diff Table</b> <br> <span id="max_diff_tablas"></span>

                                </button>
                            </div>


                            <?php

                            $tablaMasPesadaView = "";
                            $tablaMasPesadaArray = explode("|", $top10BD[3]);
                            $tablaMasPesadaView = "<b>" . $tablaMasPesadaArray[1] . " </b> <br> " . $tablaMasPesadaArray[2];

                            ?>

                            <!-- Tabla mas pesada -->
                            <div class="col col-3    ">


                                <button type="button" id="btnTablaMasPesada" class="<?php echo $system->buttonClass("success") ?>">
                                    <?php echo $system->validarLog($tamanoBdPrimarioL, 10) ?>
                                    <span id="tabla_mas_pesada">
                                        <?php echo $tablaMasPesadaView ?>

                                    </span>

                                </button>

                            </div>
                            <!--
                        <span class="<?php //echo $color 
                                        ?> ">
                            <b><?php //echo $system->validarLog($schemaInfoSec, 10) 
                                ?>Max ID</b><br>
                       <?php //echo $maxIdTablas 
                        ?> </span>


                                    -->
                        </div>



                    </div>





                </div>

                <!-- Ver mas -->


            </div>




            <div class="card-footer card-comments ">
                <button type="button" class="btn btn-block btn-light w-100" onclick="verDetalleDataBase(400)">Ver detalle </button>
                <div class="card-comment" id="card_tablas_detalle">
                    <hr><br>
                    <div class="comment-text text-dark">
                        <span class="username">
                            Cantidad de registros en ambas Bases de datos.
                            <span class="text-muted float-right"><?php echo $dataTimeVisual ?></span>
                        </span>



                        <div class="row">
                            <div class="col-md-3"><i class='fa-solid  fa-server'></i> Log Size 1<?php echo $system->validarLog($tamanoBdPrimarioL, 10) ?></div>
                            <div class="col-md-3"><i class='fa-solid  fa-server'></i> Log tables 1<?php echo $system->validarLog($sqlSizePrimario, 10) ?></div>
                            <div class="col-md-3"><i class='fa-solid  fa-server'></i> Log Size 2 <?php echo $system->validarLog($tamanoBdSecundarioL, 10) ?></div>
                            <div class="col-md-3"><i class='fa-solid  fa-server'></i> Log tables 2 <?php echo $system->validarLog($sqlSizeSecundario, 10) ?></div>
                        </div>
                        <div class="row">


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
                                        <td><?php if ($tamanoBd == 0) {
                                                echo $tamanoBd = "<i class='fa-solid fa-check'></i>";
                                            } else {
                                                echo $tamanoBd . "GB";
                                            } ?></td>
                                        <td><?php if ($tamanoBdSec == 0) {
                                                echo $tamanoBdSec = "<i class='fa-solid fa-check'></i>";
                                            } else {
                                                echo $tamanoBdSec . "GB";
                                            } ?></td>
                                        <td><span><?php if ($tamanoBdSec == 0) {
                                                        echo $tamanoBdSec = "<i class='fa-solid fa-check'></i>";
                                                    } else {
                                                        echo abs($tamanoBd - $tamanoBdSec) . " GB";
                                                    }   ?></span></td>
                                    </tr>

                                    <?php
                                    $tablaAux = "";
                                    $largoCountTablasSiftSec = count($countTablasShiftSec);
                                    $max_diff_tablas = 0;
                                    for ($x = 3; $x < $largoCountTablasSiftSec - 2; $x++) {
                                        $tablaAux = explode("|", $countTablasShiftSec[$x]);
                                        #$restaAux = abs($countTablas[$tablaAux[0]] - $tablaAux[1]);
                                        if (isset($countTablas[$tablaAux[0]]) && isset($tablaAux[1])) {
                                            $restaAux = abs($countTablas[$tablaAux[0]] - $tablaAux[1]);
                                        } else {
                                            $restaAux = 0;  // O cualquier valor por defecto que tenga sentido
                                        }
                                        
                                        $colorAux = "bg-success";
                                        if ($restaAux > 19 && $restaAux <= 49)  $colorAux = "bg-warning";
                                        if ($restaAux >= 50)  $colorAux = "bg-danger";

                                        echo "<tr>";
                                        echo "<td>" . $tablaAux[0] . "</td>";
                                        echo "<td>" . $countTablas[$tablaAux[0]] . "</td>";
                                        echo "<td>" . $tablaAux[1] . "</td>";
                                        echo "<td><span class='badge " . $colorAux . "''>" . $restaAux . "</span></td>";
                                        echo "</tr>";

                                        if ($max_diff_tablas <= $restaAux) $max_diff_tablas = $restaAux;
                                    }
                                    $estadoMaxDiffTable = "danger";
                                    if ($max_diff_tablas <= 20) $estadoMaxDiffTable = "success";

                                    ?>
                                    <script>
                                        var maxDiffTablas = <?php echo $max_diff_tablas ?>

                                        if (maxDiffTablas <= 20) {
                                            $("#btnDiffTablas").removeClass("btn-default")
                                            $("#btnDiffTablas").addClass("btn-success")
                                        } else {
                                            $("#btnDiffTablas").removeClass("btn-default")
                                            $("#btnDiffTablas").addClass("btn-danger")
                                        }
                                        $("#max_diff_tablas").html(maxDiffTablas)
                                    </script>


                                </tbody>
                            </table>

                        </div>







                    </div>

                </div>

                <div class="card-comment" id="card_top_ten_tablas_detalle">

                    <div class="comment-text text-dark">
                        <span class="username">
                            <?php echo $system->validarLog($top10BD, 16) ?> Top 10 tablas mas pesadas
                            <span class="text-muted float-right"><?php echo $dataTimeVisual ?></span>
                        </span>
                        <table class="table table-bordered" id="tableTopTenTablasView">

                            <?php
                            $largoTop10 = count($top10BD);
                            $tabla_mas_pesada = "";
                            for ($x = 0; $x < $largoTop10; $x++) {
                                $punteroTopBD = explode("|", $top10BD[$x]);
                                if ($x == 3) {
                                    $tabla_mas_pesada = $punteroTopBD[2];
                                }
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



                <div class="card-comment" id="card_primary_key_tablas_detalle">

                    <div class="comment-text text-dark">
                        <span class="username">
                            <?php echo $system->validarLog($primaryKeyMaxActivo, 16) ?> Top 10 tablas mas pesadas
                            <span class="text-muted float-right"><?php echo $dataTimeVisual ?></span>
                        </span>
                        <table class="table table-bordered" id="tableTopTenTablasView">

                            <?php
                            $largoPrimaryKey = count($primaryKeyMaxActivo);


                            for ($x = 0; $x < $largoPrimaryKey; $x++) {
                                $punteroPK = explode("|", $primaryKeyMaxActivo[$x]);
                                # number_format($lineaMaxIdArray[4], 0, ",", ".");
                                if ($x == 1) {
                                    echo "<thead><tr>";
                                    echo "<th>" . $punteroPK[0] . "</th>";
                                    echo "<th>" . $punteroPK[3] . "</th>";
                                    echo "<th>" . $punteroPK[4] . "</th>";
                                    echo "</tr></thead>";
                                }
                                if ($x > 2 && $x < 13) {

                                    echo "<tbody><tr>";
                                    echo "<td>" . $punteroPK[0] . "</td>";
                                    echo "<td>" . (is_numeric($punteroPK[3]) ? number_format($punteroPK[3], 0, ",", ".") : "0") . "</td>";
                                    echo "<td>" . (is_numeric($punteroPK[4]) ? number_format($punteroPK[4], 0, ",", ".") : "0") . "</td>";
                                    
                                    echo "</tr></tbody>";
                                }
                            }

                            ?>

                        </table>
                    </div>

                </div>





                <script>
                    function verDetalleDataBase(time = 0) {
                        $("#card_tablas_detalle").toggle(time);
                        $("#card_top_ten_tablas_detalle").toggle(time)
                        $("#card_primary_key_tablas_detalle").toggle(time)

                    }
                    verDetalleDataBase()
                </script>
            </div>






        </div>
    </div>
</div>