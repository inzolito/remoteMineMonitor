<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");


$system = new systemClass();
$system->validarSesion();
$conn = $system->conectaDB();
$faenaCl = new faena();

$firstday = date('Y-m-d', strtotime("this week"));
$lastday = date("Y-m-d", strtotime($firstday . "+ 6 days"));

//print_r($_SESSION);
?>

<?php
if ($_SESSION["permiso"] == "Administrador" || $_SESSION["permiso"] == "Soporte") {
?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-shovel"></i>Monitoreo especial </h3>
        </div>



        <div class="card-body">
            <div class="row ">
                <!--
                <div class="col-md-4 d-flex justify-content-center">
                    <a class="btn btn-app bg-info" onclick='monitoreoCAS("Amsa")'>

                        <i class="fas fa-hard-hat"></i> Amsa
                    </a>
                </div>
                <div class="col-md-4 d-flex justify-content-center">
                    <a class="btn btn-app bg-info" onclick='monitoreoCAS("Codelco")'>

                        <i class="fas fa-hard-hat"></i> Codelco
                    </a>
                </div>

-->
                <div class="col-md-3 d-flex justify-content-center">
                    <a class="btn btn-app bg-info" href="https://10.40.90.99:13000" target="_blank">

                        <img src="dist/img/system/grafana.webp" style="width: 25px;  filter: brightness(0) saturate(100%) invert(100%);">
                        <br>Grafana OAS Centinela
                    </a>
                </div>

                <div class="col-md-3 d-flex justify-content-center">
                    <a class="btn btn-app bg-info" href="https://10.40.90.99:23000" target="_blank">

                        <img src="dist/img/system/grafana.webp" style="width: 25px;  filter: brightness(0) saturate(100%) invert(100%);">
                        <br>Grafana OAS Antucoya
                    </a>
                </div>


                <div class="col-md-3 d-flex justify-content-center">

                    <a class="btn btn-app bg-info" onclick='monitoreoOAS()'>

                        <i class="fas fa-hard-hat"></i> Monitoreo OAS
                    </a>
                </div>

                <div class="col-md-3 d-flex justify-content-center">
                    <a class="btn btn-app bg-info bg-warning" onclick="carga_modulo_container('monitoreo_fms/monitoreo_fms.php','Monitoreo FMS')">

                        <i class="fas fa-brands fa-connectdevelop"></i> develop - Msalas
                    </a>

                </div>
                <!--<i class="fa-brands fa-connectdevelop"></i>
                <div class="col-md-4">

                         <a class="btn btn-app bg-info" onclick='cargaMaster()'>

                         <i class="fas fa-globe"></i> Master
                        </a>
 

                </div>
-->
            </div>
        </div>

    </div>

<?php

}

?>



<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-shovel"></i>Faenas </h3>
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
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    <th> Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php

                $sql_query = "select distinct f.id id, f.faena faena, f.estado estado, f.alias alias from faenas f join permisos_faenas p on(f.id=p.id_faena) where   f.id in( select DISTINCT fs.id_faena from conexiones c left join servidores s on (c.id_servidor=s.id) left join faenas_servidores fs on (fs.id_servidor=s.id) where c.estado=1 ) ";
                if ($_SESSION["permiso"] == "Administrador" || $_SESSION["permiso"] == "Soporte") {
                } else {
                    //$sql_query = "select f.id id, f.faena faena, f.estado estado, f.alias alias from faenas f join permisos_faenas p on(f.id=p.id_faena) where estado=1 and id_permiso='" . $_SESSION["id_permiso"] . "' order by faena asc";
                    $sql_query = "select distinct f.id id, f.faena faena, f.estado estado, f.alias alias from faenas f join permisos_faenas p on(f.id=p.id_faena) where id_permiso='" . $_SESSION["id_permiso"] . "'  and  f.id in( select DISTINCT fs.id_faena from conexiones c left join servidores s on (c.id_servidor=s.id) left join faenas_servidores fs on (fs.id_servidor=s.id) where c.estado=1 ) ";
                }

                $faenasSql = $conn->query($sql_query);

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
                    $statusFaena = $system->iconStatusConexionFaena($faenaDatos["alias"], 1, 1);

                    $claseBtnMonitoreo = 'bg-success';
                    if ($statusFaena == 0) {
                        $iconoOnline = " <i class='fas fa-wifi text-danger mr-2' ></i>";
                        $claseBtnMonitoreo = 'btn-default disabled';
                    } else {
                        $iconoOnline = " <i class='fas fa-wifi text-success mr-2' ></i>";
                    }

                ?>
                    <tr>
                        <td><?php echo $faenaDatos["id"]; ?></td>
                        <td>
                            <div id="divIconoListaFaenasStatus_<?php echo $faenaDatos["id"] ?>"> </div>
                            <?php echo  $iconoOnline . $faenaDatos["faena"] . " (" . $faenaDatos["alias"] . ")"; ?>
                        </td>
                        <td><?php echo "-" ?> </td>
                        <td><?php echo "-" ?></td>
                        <td><?php echo "-" ?></td>
                        <td>

                            <button type='button' onclick='cargaMonitoreo2(<?php echo $faenaDatos["id"] ?> ,"<?php echo $faenaDatos["alias"] ?>")' style='min-width:95px;' class='btn btn-sm d-inline-block <?php echo $claseBtnMonitoreo ?> -info mb-1'>
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
                        // idDiv, sizeIcon = 0) codigo Inhabilitado -evaluar
                        //statusListaFaena(<?php //echo $faenaDatos["id"] 
                                            ?>, "divIconoListaFaenasStatus", 0)
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