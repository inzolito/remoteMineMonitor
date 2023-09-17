<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

date_default_timezone_set('America/Santiago');
$system = new systemClass();
$faenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();






//----------------------------------------------------------------------
// Leer el archivo JSON
$monitoreoWindows = file_get_contents('../../data/monitoreoWindows.json');

//print_r($monitoreoWindows);

//Recorrer las minas para mostrar los servidores windows
$monitoreoWindows = json_decode($monitoreoWindows, true);
$arrayTipoServidor = array("DataBase", "Jview");
$c = 0;

?>

<style>
    /* Estilo personalizado para el cuerpo */
    body {
        font-size: 70%;
        /* Ajusta el tamaño de fuente al 80% del tamaño por defecto */
    }
</style>


<div class="row">
    <?php

    foreach ($monitoreoWindows["faenas"] as $faena) {

    ?>




        <div class="col-md-3">

            <div class="card card-widget shadow widget-user ">

                <div class="widget-user-header bg-info">
                    <h1 class="widget-user-username"><b><?php echo $faena["faena"] ?> </b></h1>
                    <h6 class="widget-user-desc"><?php echo $system->datatimeCargaDiv() ?></h6>
                </div>
                <div class="widget-user-image">
                    <img class="img-circle elevation-2" src="dist/img/system/logohxg.jpg" alt="User Avatar">
                </div>
                <!-- CAS,  Estado servidor y servicios -->
                <div class="card-body" style="padding-top:50px;">
                    <!-- Estado CAS -->
                    <div class="row">
                        <div class="col-md-12">

                            <?php
                            // leer log
                            $rutaLogs = $system->rutaDataSet() . $faena["alias"] . "/";
                            $logMPData = file($rutaLogs . "mpdata.log");

                            //echo count($logMPData);
                            // print_r($logMPData);

                            $nombreServidorCas = "No hay datos";
                            if (count($logMPData) > 1) {
                                //Disco duro

                                $discosArrayMPData = explode(" ", $logMPData[5]);
                                $nombreServidorCas = $discosArrayMPData[2];
                                $porcentajeDiscoUsadoMPData = round(explode(":", $discosArrayMPData[5])[1], 1);
                                $tamanoDiscoMPData = round(explode(":", $discosArrayMPData[9])[1], 1);

                                //Valores servicios
                                ($metricsArray = explode(" ", $logMPData[4]));
                                $metricsError = explode(":", $metricsArray[8])[1];
                                $metriscDB = explode(":", $metricsArray[11])[1];
                                $metricsApi = explode(":", $metricsArray[14])[1];
                                $metricsDataServer = explode(":", $metricsArray[17])[1];
                                //print_r($logMPData);


                                // echo $porcentajeDiscoUsadoMPData;

                                //CPU
                                $cpuArray = explode(" ", $logMPData[6]);
                                $promedioCpuMPData = $cpuArray[9];
                                $promedioCpuMPData = explode(":", $cpuArray[9])[1];
                                $upTimeCpuMPData = explode(":", $cpuArray[16])[1] . " " . $cpuArray[17] . " " . $cpuArray[18] . " " . $cpuArray[18];

                                //Unidades CAS 
                                $unidadesCASArray = explode("CAS", $logMPData[9]);
                                $unidadesCASArray = explode("\ ", $unidadesCASArray[2]);
                                //print_r($unidadesCASArray);

                                //Unidades CAS10
                                $unidadesCAS10Array = explode("CAS 10", $logMPData[10]);
                                $unidadesCAS10Array = explode("\ ", $unidadesCAS10Array[2]);



                                $unidadesConectadascas10MPData = $unidadesCAS10Array[10];
                                //DataBase
                                $databasedesCASArray = explode(" ", $logMPData[11]);
                                $tamanoDatabaseCAS = round(explode(":", $databasedesCASArray[13])[1], 1);

                                $databaseesCAS10Array = explode(" ", $logMPData[12]);
                                $tamanoDatabaseCAS10 = round(explode(":", $databaseesCAS10Array[15])[1], 1);

                                //Clases mpdata
                                $claseCpuMPData = "GraficoVerde";
                                $claseCpuMPData = ($promedioCpuMPData >= 70) ? "GraficoAmarillo" : $claseCpuMPData;
                                $claseCpuMPData = ($promedioCpuMPData >= 90) ? "GraficoRojo" : $claseCpuMPData;

                                $claseDiscoMPData = "GraficoVerde";
                                $claseDiscoMPData = ($porcentajeDiscoUsadoMPData >= 70) ? "GraficoAmarillo" : $claseDiscoMPData;
                                $claseDiscoMPData = ($porcentajeDiscoUsadoMPData >= 90) ? "GraficoRojo" : $claseDiscoMPData;
                            }
                            ?>
                            <div class="row">



                                <div class="col-md-3"></div>
                                <div class="col-md-6">

                                    <div class="description-block">
                                        <h5 class="description-header"><?php echo $system->validarLog($logMPData, 5) ?><?php echo "CAS MPData" ?></h5>
                                        <span class="description-text"><?php echo $nombreServidorCas ?></span>
                                    </div>
                                </div>
                                <div class="col-md-3"></div>




                            </div>



                            <!-- Equipos conectados -->
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="font-weight-bold text-primary text-center">Equipos conectados</h5>
                                </div>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-sm-4 col-6">
                                            <div class="description-block border-right">
                                                <span class="description-text"><?php echo $unidadesCAS10Array[1] ?></span><br>
                                                <span class="description-percentage " style="font-size: 20px;"><i class="fa-solid fa-truck-pickup"></i> </span>
                                                <h5 class="description-header" style="font-size: 20px;"><?php echo intval(preg_replace('/\D/', '', $unidadesCAS10Array[2])) ?></h5>
                                                <span class="description-text">CAS 10</span>
                                            </div>

                                        </div>

                                        <div class="col-sm-4 col-6">
                                            <div class="description-block border-right">
                                                <span class="description-text"><?php echo $unidadesCAS10Array[3] ?></span><br>
                                                <span class="description-percentage " style="font-size: 20px;"><i class="fa-solid fa-truck-pickup"></i> </span>
                                                <h5 class="description-header" style="font-size: 20px;"><?php echo intval(preg_replace('/\D/', '', $unidadesCAS10Array[4]))  ?></h5>
                                                <span class="description-text">CAS 10</span>
                                            </div>

                                        </div>

                                        <div class="col-sm-4 col-6">
                                            <div class="description-block border-right">
                                                <span class="description-text"><?php echo $unidadesCASArray[1] ?></span><br>
                                                <span class="description-percentage" style="font-size: 20px;"><i class="fa-solid fa-truck-pickup"></i> </span>
                                                <h5 class="description-header" style="font-size: 20px;"><?php echo intval(preg_replace('/\D/', '', $unidadesCASArray[2])) ?></h5>
                                                <span class="description-text">CAS </span>
                                            </div>

                                        </div>


                                    </div>
                                </div>
                            </div>




                            <div class="row">
                                <div class="col-3">
                                    <div class="description-block">
                                        <input type="text" value=<?php echo $promedioCpuMPData  ?> class="<?php echo $claseCpuMPData  ?>" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                        <h5 class="description-header">Promedio RAM 15 Min.</h5>

                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="description-block">
                                        <input type="text" value=<?php echo $porcentajeDiscoUsadoMPData  ?> class="<?php echo $claseDiscoMPData  ?>" data-width="150" data-height="150" data-fgcolor="#3c8dbc" data-readonly="true">
                                        <h5 class="description-header">Hard Disk. </h5>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="description-block text-center">
                                        <div style="position: relative;" class=" text-info">
                                            <i class="fa-solid fa-database" style="font-size: 72px;"></i>
                                            <h3 style=" font-size:16px;background-color: white;border-radius:12px;padding:4px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0; font-weight: bold;"><?php echo round($tamanoDatabaseCAS10) . " GB" ?></h3>
                                        </div>
                                        <h5 class="description-header">DataBase CAS10 </h5>
                                    </div>

                                </div>

                                <div class="col-md-3">
                                    <div class="description-block text-center">
                                        <div style="position: relative;" class=" text-info">
                                            <i class="fa-solid fa-database" style="font-size: 72px;"></i>
                                            <h3 style=" font-size:16px;background-color: white;border-radius:12px;padding:4px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0; font-weight: bold;"><?php echo round($tamanoDatabaseCAS) . " GB" ?></h3>
                                        </div>
                                        <h5 class="description-header">DataBase CAS </h5>
                                    </div>
                                </div>


                            </div>




                            <div class="row">
                                <div class="col-12">
                                    <div class="description-block">
                                        
                                        <table class="table table-bordered ">
                                            <thead>
                                                <tr>
                                                    <th colspan="4">Servicios</th>
                                                </tr>
                                            </thead>

                                            <tbody>


                                                <tr>
                                                    <td class="text-left"> Metrics Error <i class="fa-solid fa-chevron-right"></i>
                                                        <span class="badge bg-success"><?php echo $metricsError  ?></span>
                                                    </td>
                                                    
                                                 
                                                    <td class="text-left"> API <i class="fa-solid fa-chevron-right"></i>
                                                      <span class="badge bg-success"><?php echo $metricsApi ?></span>
                                                    </td>
                                                 
                                                    <td class="text-left"> DataServer <i class="fa-solid fa-chevron-right"></i>
                                                    <span class="badge bg-success"><?php echo $metricsDataServer  ?></span></td>
                                                 
                                                    <td class="text-left"> DataBase <i class="fa-solid fa-chevron-right"></i>
                                                    <span class="badge bg-success"><?php echo $metriscDB  ?></span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>


                            </div>


                        </div>
                    </div>

                    <!-- Estado servidores -->
                    <div class="row">
                        <?php
                        for ($p = 0; $p <= 1; $p++) {
                            // leer logs
                            $rutaLogs = $system->rutaDataSet() . $faena["alias"] . "/";
                            $LogStatus = file($rutaLogs . "status" . $arrayTipoServidor[$p] . "WinMon.log");
                            $LogStatusValidar = array_slice($LogStatus, 2); // Excluir las dos primeras líneas
                            $LogStatusValidar[0] = $system->formatoFecha($LogStatusValidar[0], 7);
                            // print_r($LogStatus)."<br>";
                            // print_r($LogStatusValidar)."<br>";

                            $datatimeLog = $system->formatoFecha($LogStatus[2], 7);
                            //$datatimeLog = $LogStatus[2];
                            //Rescatando datos
                            $nombreServidor = $LogStatus[4];
                            $CPUServidor = $LogStatus[6];

                            // memoria ram
                            $memoriaArray = preg_replace('/\s+/', ' ', trim($LogStatus[8]));
                            $memoriaArray = explode(" ", $memoriaArray);
                            $memoriaArray = array_values(array_filter($memoriaArray, 'trim'));

                            $memoriaTotalServidor = $memoriaArray[1];
                            $memoriaLibreServidor = $memoriaArray[0];

                            $memoriaTotalServidor = preg_replace('/\D/', '', $memoriaTotalServidor); // Eliminar todos los caracteres no numéricos
                            $memoriaLibreServidor = preg_replace('/\D/', '', $memoriaLibreServidor); // Eliminar todos los caracteres no numéricos


                            // Convertir a gigabytes
                            $memoriaLibreServidor = $memoriaLibreServidor / 1048576;
                            $memoriaTotalServidor = $memoriaTotalServidor / 1048576;
                            $memoriaUsadaServidor = $memoriaTotalServidor - $memoriaLibreServidor;

                            $memoriaTotalServidor = round($memoriaTotalServidor);


                            $porcentajeMemoriaServidor = round($memoriaUsadaServidor * 100 / $memoriaTotalServidor);

                            //Clases graficos.
                            $claseRam = "GraficoVerde";
                            $claseRam = ($porcentajeMemoriaServidor >= 70) ? "GraficoAmarillo" : $claseRam;
                            $claseRam = ($porcentajeMemoriaServidor >= 90) ? "GraficoRojo" : $claseRam;

                            $claseCpu = "GraficoVerde";
                            $claseCpu = ($CPUServidor >= 70) ? "GraficoAmarillo" : $claseCpu;
                            $claseCpu = ($CPUServidor >= 90) ? "GraficoRojo" : $claseCpu;


                            //almacenamiento
                            $discosArray = array();
                            for ($i = 9; $i < count($LogStatus); $i++) {
                                $almacenamientosServidorArray = preg_replace('/\s+/', ' ', trim($LogStatus[$i]));
                                $almacenamientosServidorArray = explode(" ", $almacenamientosServidorArray);
                                $almacenamientosServidorArray = array_values(array_filter($almacenamientosServidorArray, 'trim'));


                                $almacenamientosServidorArray[2] = round((preg_replace('/\D/', '', $almacenamientosServidorArray[2])) / 1073741824, 1) . "<br>";
                                $almacenamientosServidorArray[3] = (preg_replace('/\D/', '', $almacenamientosServidorArray[3])) / 1073741824 . "<br>";
                                $discosArray[$almacenamientosServidorArray[0]] = $almacenamientosServidorArray;
                            }
                        ?>

                            <!-- Estado servidor -->


                            <div class="col-sm-6">
                                <!-- Card Nueva -->
                                <div class="card ">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <?php echo $system->validarLog($LogStatusValidar, 12) ?>
                                            Servidor <b> <?php echo ($arrayTipoServidor[$p] == "Jview") ? "Jview / ME" : $arrayTipoServidor[$p]; ?></b>

                                        </h3>


                                    </div>
                                    <!-- body nuevo -->
                                    <div class="card-body">
                                        <div class="col-md-12 description-text font-weight-bold text-center">
                                            <?php echo $nombreServidor ?>
                                        </div>
                                        <div class="col-sm-12">

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="description-block">
                                                        <!-- prueba de graficos a ver como se ve -->
                                                        <span class="description-percentage text-success text-xl    ">

                                                            <input type="text" value=<?php echo $porcentajeMemoriaServidor  ?> class="<?php echo $claseRam ?>" data-width="110" data-height="110" data-fgcolor="#3c8dbc" data-readonly="true">
                                                            <?php //echo $porcentajeMemoriaServidor  
                                                            ?></span>
                                                        <h5 class="description-header">RAM</h5>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="description-block ">
                                                        <span class="description-percentage text-success text-xl">
                                                            <?php //echo $CPUServidor 
                                                            ?> </span>
                                                        <input type="text" value=<?php echo $CPUServidor  ?> class="<?php echo $claseCpu ?>" data-width="110" data-height="110" data-fgcolor="#3c8dbc" data-readonly="true">
                                                        <h5 class="description-header">CPU</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <br>
                                            <?php
                                            foreach ($discosArray as $hd) {
                                                $hdLibre = $hd[2];
                                                $hdTotal = $hd[3];

                                                if ($hdLibre > 0) {
                                                    // Calcular el espacio usado del disco duro en gigabytes
                                                    $hdUsado = round(($hdTotal - $hdLibre), 1);

                                                    // Calcular el porcentaje utilizado
                                                    $porcentajeUsado = round(($hdUsado / ($hdTotal)) * 100);
                                                    $claseDiscoDuro = "success";
                                                    $claseDiscoDuro = ($porcentajeUsado > 70 && $porcentajeUsado < 80) ? "warning" : "";
                                                    if ($porcentajeUsado >= 80) $claseDiscoDuro = "danger";

                                                    if ($hdTotal >= 1024) {
                                                        $hdTotal = round($hdTotal / 1024, 1) . " T";
                                                    } else {
                                                        $hdTotal = round($hdTotal) . " GB";
                                                    }

                                                    if ($hdLibre >= 1024) {
                                                        $hdLibre = round($hdLibre / 1024, 1) . " T";
                                                    } else {
                                                        $hdLibre = round($hdLibre) . " GB";
                                                    }

                                                    if ($hdUsado >= 1024) {
                                                        $hdUsado = round($hdUsado / 1024, 1) . " T";
                                                    } else {
                                                        $hdUsado = round($hdUsado) . " GB";
                                                    }



                                            ?>


                                                    <div class="progress-group">
                                                        <?php echo $hd[0] ?>
                                                        <span class="float-right"><?php echo "<b>$hdUsado</b>/$hdTotal"; ?> </span>
                                                        <div class="progress progress-lg progress-bar-lg" style="width: 100%;">
                                                            <div class="progress-bar bg-<?php echo $claseDiscoDuro ?>" style="width: <?php echo $porcentajeUsado ?>%">
                                                                <b><?php echo  $porcentajeUsado . "%"; ?></b>
                                                            </div>
                                                        </div>
                                                    </div>

                                            <?php

                                                }
                                            }
                                            ?>


                                        </div>
                                    </div>

                                    <div class="col-sm-12 ">



                                        <ul class="nav flex-column">
                                            <?php
                                            // leer logs

                                            $LogServices = file($rutaLogs . "services" . $arrayTipoServidor[$p] . "WinMon.log");
                                            //datos estaticos
                                            $nombreServicioArray = array(

                                                'W3SVC' => 'IIS',
                                                'mssqlserver' => 'SQLServer',
                                                'SQLServerAgent' => 'SQLServerAgent',
                                                'ReportServer' => 'ReportServer',
                                                'md.WS.Service' => 'SharperLigth',
                                                'MineEnterprise' => 'MineEnterprise',
                                                'MineEnterpriseWebUI' => 'MineEnterprise',

                                                'HxGN' => 'HxGN CasApi'
                                            );
                                            $iconoServicioArray = array(
                                                'default' => '<i class="fa-brands fa-staylinked"></i>',
                                                'md.WS.Service' => '<i class="fa-solid fa-bezier-curve"></i>',
                                                'HxGN' => '<i class="fa-solid fa-video"></i>',
                                                'MineEnterprise' => '<i class="fa-solid fa-database"></i>',

                                                'W3SVC' => '<i class="fa-solid fa-globe"></i>',
                                                'mssqlserver' => '<i class="fa-solid fa-database"></i>',
                                                'SQLServerAgent' => '<i class="fa-solid fa-database"></i>',
                                                'ReportServer' => '<i class="fa-regular fa-file-lines"></i>'
                                            );




                                            for ($y = 0; $y <= count($LogServices); $y++) {

                                                $datoLinea = explode(" ", $LogServices[$y]);
                                                if ($datoLinea[0] == "NOMBRE_SERVICIO:" || $datoLinea[0] == "NOMBRE_DE_SERVICIO:") {
                                                    $classEstado = "success";
                                                    $lineaEstado = explode(" ", preg_replace('/\s+/', ' ', trim($LogServices[$y + 2])));
                                                    if ($lineaEstado[3] != "RUNNING") $classEstado = "danger";
                                                    $nombreServicioLimpio = preg_replace('/\s+/', ' ', trim($datoLinea[1]));
                                            ?>
                                                    <li class="nav-item">
                                                        <a href="#" class="nav-link">
                                                            <?php
                                                            if ($nombreServicioArray[$nombreServicioLimpio]) {
                                                                echo  $iconoServicioArray[$nombreServicioLimpio] . " " . $nombreServicioArray[$nombreServicioLimpio];
                                                            } else {
                                                                echo  $iconoServicioArray["default"] . " " . $nombreServicioLimpio;
                                                            }

                                                            ?> <span class="float-right badge bg-<?php echo $classEstado ?>"><?php echo $lineaEstado[3]  ?></span>
                                                        </a>
                                                    </li>
                                            <?php
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </div>


                                </div>

                            </div>



                        <?php } ?>

                    </div>
                </div> <!-- End card body -->


            </div>

        </div>





    <?php

    }

    ?>
</div>


<style>
    input.GraficoVerde+div>div:nth-child(2):after {
        content: "%";
        font-size: 25px;
    }
</style>


<script>
    // ---------------- GRAFICOS ---------------
    $('.GraficoAzul').knob({
        readOnly: true,
        rotation: 'anticlockwise',
        thickness: '.3',
        width: 60,
        height: 60,
        fgColor: '#3c8dbc'

    });
    $('.GraficoVerde').knob({

        readOnly: true,
        rotation: 'anticlockwise',
        thickness: '.3',
        width: 60,
        height: 60,
        displayInput: true,
        fgColor: '#28a745',
        
        draw: function() {
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
        width: 60,
        height: 60,
        fgColor: 'red',
        draw: function() {
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
        width: 60,
        height: 60,
        fgColor: '#ffc107',
        draw: function() {
            // Obtiene el valor del knob
            var value = $(this.i).val();

            // Agrega el signo de porcentaje
            $(this.i).val(value + '%');
        }
    });
</script>