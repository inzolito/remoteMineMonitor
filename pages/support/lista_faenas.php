<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$system->validarSesion();
$conn = $system->conectaDB();
$faenaCl = new faena();

$firstday = date('Y-m-d', strtotime("this week"));
$lastday = date("Y-m-d", strtotime($firstday . "+ 6 days"));


$requestUrl = $_SERVER['REQUEST_URI'];

// Define las rutas y las acciones correspondientes
$routes = array(
    '/' => 'home',
    '/faenas' => 'faenas',
    '/faenas/{alias}' => 'Antucoya'
);

foreach ($routes as $route => $action) {
    // Escapa los caracteres especiales para evitar problemas con expresiones regulares
    $routePattern = preg_quote($route, '/');
}
// Verifica si la URL solicitada está en las rutas definidas
if (array_key_exists($requestUrl, $routes)) {
    // Obtén el nombre de la acción correspondiente a la URL
    $action = $routes[$requestUrl];

    // Ejecuta la acción correspondiente
    switch ($action) {
        case 'home':
            // Lógica para la página de inicio
            echo 'Página de inicio';
            break;
        case 'faenas':
            // Lógica para la página de about
            echo 'Acerca de nosotros';
            break;
        case 'Antucoya':
            // Lógica para la página de contacto
            echo 'Página de contacto';
            break;
        default:
            // Acción no encontrada
            echo '404 Not Found';
            break;
    }
} else {
    // Mostrar una página de error o redirigir a una página predeterminada
    echo '404 Not Found';
}




?>

 




<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-shovel"></i>Faenas</h3>
    </div>

    <div class="card-body p-0">
        <div class="row mt-4 mb-2">
            <div class="col-md-2"></div>
            <div class="col-md-8" id="divSemanaTurno">
                <div class="col-md-2"></div>

            </div>
        </div>

        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>Faena</th>
                    <th></th>
                    <th> </th>
                    <th> </th>
                    <th> Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php

                $faenasSql = $conn->query("select * from faenas where estado=1 order by faena asc");

                while ($faenaDatos = $faenasSql->fetch_assoc()) {
                    $faenaCheckDatos = $faenaCl->datosCheck($faenaDatos["id"]);
                    $fechaCheck = "-";
                    $estaSemanaCheck = "-";
                    $aprobado = "-";
                    if (isset($faenaCheckDatos->fecha)) $fechaCheck = $faenaCheckDatos->fecha;
                    if ($fechaCheck != "-") $fechaCheck = $system->formatoFecha($fechaCheck, "vista");

                    // $datosCheckFaenaEstaSemana=$faenaCl->datosCheck($faenaDatos["id"],$firstday,$lastday);
                    //959661-05

                    /*
                    if (isset($faenaCheckDatos->fecha)) {
                        if ($faenaCheckDatos->fecha > $lastday) {
                        } else {
                            if ($faenaCheckDatos->estado == "finalizado") {
                                $estaSemanaCheck = '<p class="text-success"><i class="fa-solid fa-check"></i> Finalizado</p>';
                            } else {
                                $estaSemanaCheck = '<p class="text-warning"><i class="fa-solid fa-clock"></i> En proceso</p>';
                            }



                            switch ($faenaCheckDatos->aprobado) {
                                case "0":
                                    $aprobado = '<p class="text-danger"><i class="fa-solid fa-x"></i> Rechazado </p>';
                                    break;
                                case "1":
                                    $aprobado = '<p class="text-success"><i class="fa-solid fa-check"></i> Aprobado</p>';
                                    break;
                                case "2":
                                    $aprobado = '<p class="text-warning"><i class="fa-solid fa-clock"></i> Pendiente </p>';
                                    break;
                                case "3":
                                    $aprobado = '-';

                                    break;
                                default:
                                    $aprobado = '-';
                            }
                        }
                    }
                    */

                    //---------------validar conexion--------------------------
                    $carpeta = $faenaDatos["alias"] . "/";
                    $ruta = "/home/jigsaw/monitoreoRemoto/" . $carpeta;
                    $Ntp = file($ruta . "NtpMon.log");

                    $horaActual = date("H:i");
                    $diaActual = date("d");
                    $largoNtp = count($Ntp);
                    $contador = 0;
                    for ($x = 0; $x < $largoNtp; $x++) {
                        $horaNtp = $Ntp[$x];
                        $contador++;
                        if ($contador == 1) {
                            $hNtp = date('H:i', strtotime($horaNtp));
                            $dNtp = date("d", strtotime($horaNtp));
                        } else {
                            $contador = 0;
                            break;
                        }
                    }
                    $horaResta = date("H:i", strtotime($horaActual) - 6000);
                    //----------------------------------


                    //---------------validar conexion--------------------------
                    /*
                    $largoPing = count($pingServerAct);
                    $validarPing = explode("%", $pingServerAct[8]);
                    $validarPing = explode(",", $validarPing[0]);
                    $validarPing = $validarPing[2];

                    $iconoOnline = " <i class='fas fa-wifi text-success mr-2' ></i>";
                    $claseBtnMonitoreo = 'bg-success';
                  
                      if ($validarPing == 0 && $validarPing !="") {
                          
                      }else{
                        $iconoOnline = "<i class='fas fa-wifi text-danger mr-2'></i>";
                        $claseBtnMonitoreo = 'btn-default';
                      }*/
                    //----------------------------------


                    $iconoOnline = " <i class='fas fa-wifi text-success mr-2' ></i>";
                    $claseBtnMonitoreo = 'bg-success';
                    if ($dNtp < $diaActual || $hNtp < $horaResta || $contador > 0) {
                        $iconoOnline = "<i class='fas fa-wifi text-danger mr-2'></i>";
                        $claseBtnMonitoreo = 'btn-default disabled';
                    }

                ?>
                    <tr>
                        <td><?php echo $faenaDatos["id"] ?></td>
                        <td>
                            <div id="divIconoListaFaenasStatus_<?php echo $faenaDatos["id"] ?>"> </div> 
                            <?php echo  $faenaDatos["faena"] . " (" . $faenaDatos["alias"] . ")"; ?>
                        </td>
                        <td><?php echo "-" ?> </td>
                        <td><?php echo "-" ?></td>
                        <td><?php echo "-" ?></td>
                        <td>

                            <button type='button' onclick='cargaMonitoreo2(<?php echo $faenaDatos["id"] ?> ,"<?php echo $faenaDatos["faena"] ?>")' style='min-width:95px;' class='btn btn-sm d-inline-block <?php echo $claseBtnMonitoreo ?> -info mb-1'>
                                <i class='fa-regular fa-play'></i> Monitoreo
                            </button>
                            <!--
                                        <button 
                                            type='button' 
                                            onclick ='cargaFaena(" <?php //$faenaDatos["id"] 
                                                                    ?> ")' 
                                            id='btn-" . $faenaDatos["id"] . "' 
                                            style='min-width:95px;'
                                            class='btn btn-sm  d-inline-block bg-gradient-info mb-1'>
                                            <i class='fa-solid fa-pen-to-square'></i> check
                                        </button> -->


                        </td>
                    </tr>
                    <script>
                        // idDiv, sizeIcon = 0) 
                        statusListaFaena(<?php echo $faenaDatos["id"] ?>,"divIconoListaFaenasStatus",0)
                    </script>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>

</div>



<script>
    function cargaSemanaTurno() {

        var currentDate = new Date();
        var day = currentDate.getDay();

        if (day >= 3 && day <= 7) {
            var finicio = new Date();
            finicio.setFullYear(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() - (day - 3));
            var ffinal = new Date();
            ffinal.setFullYear(finicio.getFullYear(), finicio.getMonth(), finicio.getDate() + 6);
        } else {
            var finicio = new Date();
            finicio.setFullYear(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() - (day + 4));
            var ffinal = new Date();
            ffinal.setFullYear(finicio.getFullYear(), finicio.getMonth(), finicio.getDate() + 6);
        }

        finicio = finicio.toISOString().substring(0, 10);
        ffinal = ffinal.toISOString().substring(0, 10);


        $("#divSemanaTurno").load("pages/summary/semanaTurnoLF.php", {
            pdt: finicio,
            udt: ffinal
        })
    }




    $(document).ready(function() {
        cargaSemanaTurno()


    });
</script>