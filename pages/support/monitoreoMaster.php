<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$faenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();


$sql_query = "select distinct f.id id, f.faena faena, f.estado estado, f.alias alias from faenas f join permisos_faenas p on(f.id=p.id_faena) where   f.id in( select DISTINCT fs.id_faena from conexiones c left join servidores s on (c.id_servidor=s.id) left join faenas_servidores fs on (fs.id_servidor=s.id) where c.estado=1)  order by f.faena asc";
if ($_SESSION["permiso"] == "Administrador" || $_SESSION["permiso"] == "Soporte") {
} else {
    //$sql_query = "select f.id id, f.faena faena, f.estado estado, f.alias alias from faenas f join permisos_faenas p on(f.id=p.id_faena) where estado=1 and id_permiso='" . $_SESSION["id_permiso"] . "' order by faena asc";
    $sql_query = "select distinct f.id id, f.faena faena, f.estado estado, f.alias alias from faenas f join permisos_faenas p on(f.id=p.id_faena) where id_permiso='" . $_SESSION["id_permiso"] . "'  and  f.id in( select DISTINCT fs.id_faena from conexiones c left join servidores s on (c.id_servidor=s.id) left join faenas_servidores fs on (fs.id_servidor=s.id) where c.estado=1 ) by f.faena asc ";
}

$faenasSql = $conn->query($sql_query);

while ($faenaDatos = $faenasSql->fetch_assoc()) {
    $faenaDatosArray[] = [
        "id" => $faenaDatos["id"],
        "alias" => $faenaDatos["alias"]
    ];
}
$faenasSql = $conn->query($sql_query);
?>

<div id="contenedor1" style="width: 1px; height: 1px; overflow: hidden; display: none;"></div>



<div class="row"style="height: 100px !important;">
    <div class="col-md-12" >
         
               <h1> <center><span   id="aliasActual">Regular</span></center></h1>
             
    </div>
</div>

<div class="card">

    <div class="card-body">

        <div class="row">

            <div class="col-md-12">



                <table class="table table-striped projects">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Cliente</th>
                            <th>Status</th>
                            <th>Procesos</th>
                            <th>Importadores </th>
                            <th>Database </th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        while ($faenaDatos = $faenasSql->fetch_assoc()) {
                            $faenaCheckDatos = $faenaCl->datosCheck($faenaDatos["id"]);
                            $fechaCheck = "-";
                            $estaSemanaCheck = "-";
                            $aprobado = "-";
                            if (isset($faenaCheckDatos->fecha)) $fechaCheck = $faenaCheckDatos->fecha;
                            if ($fechaCheck != "-") $fechaCheck = $system->formatoFecha($fechaCheck, "vista");


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
                                <td> <i class="fa-solid fa-heart-pulse"></i> </td>
                                <td> <i class="fa-brands fa-stack-overflow"></i> </td>
                                <td><i class="fa-solid fa-cubes-stacked"></i></td>
                                <td> <i class="fa-solid fa-database"></i> </td>

                                <td>

                                    <button type='button' onclick='cargaMonitoreo2(<?php echo $faenaDatos["id"] ?> ,"<?php echo $faenaDatos["alias"] ?>")' style='min-width:95px;' class='btn btn-sm d-inline-block <?php echo $claseBtnMonitoreo ?> -info mb-1'>
                                        <i class="fa-solid fa-tv"></i><br>Abrir
                                    </button>


                                </td>
                            </tr>

                        <?php
                        }
                        ?>
                    </tbody>
                </table>


            </div>

        </div>

    </div>

</div>





<script>
    const faenaDatos = <?php echo json_encode($faenaDatosArray); ?>;
    let currentFaenaIndex = 0;

    function cargaMonitoreo2(id, div) {
        $("#" + div).load("pages/support/monitoreoTwo.php?id=" + id);


    }

    function iniciarCargaCiclica() {
        if (faenaDatos.length === 0) {
            console.error("No hay faenas para cargar.");
            return;
        }

        function cargarFaenaActual() {
            $("#aliasActual").fadeOut(50)
            const {
                id,
                alias
            } = faenaDatos[currentFaenaIndex];
            cargaMonitoreo2(id, "contenedor1");
            $("#aliasActual").html("Cargando: " + alias + " <i class='fa-duotone fa-solid fa-gears fa-bounce'></i>");
            $("#aliasActual").fadeIn(200)

        }

        cargarFaenaActual();

        setInterval(() => {
            currentFaenaIndex = (currentFaenaIndex + 1) % faenaDatos.length;
            cargarFaenaActual();
        }, 4000);
    }

    iniciarCargaCiclica();
</script>