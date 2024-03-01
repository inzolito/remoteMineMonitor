<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");
require_once("../../build/controller/controller-alerta.php");

$system = new systemClass();
$alertas = new alertas();
$faena = new faena();

$alertasSql = $alertas->alerta(0, 0, 1);
$cantNotificaciones=$alertasSql->num_rows;

?>

<a class="nav-link" data-toggle="dropdown" href="#">
    <i class="far fa-bell"></i>
    <?php
    if ($cantNotificaciones>0) echo '<span class="badge badge-warning navbar-badge"> '.$cantNotificaciones.'</span>';
    ?>
      
</a>
<div class="dropdown-menu dropdown-menu-xl dropdown-menu-right " style="left: inherit; right: 0px; ">
    <span class="dropdown-item dropdown-header"> Notifications</span>

    <div class="dropdown-divider"></div>
    <?php
    while ($alertasDatos = $alertasSql->fetch_assoc()) {
        $faenaDatosLiena = $faena->datos($alertasDatos["id_faena"]);


    ?>

       
        <a href="#" class="dropdown-item" style="white-space: inherit;">

            <div class="row">
                <!--<div class="col-md-2">
                    <i class="fa-solid fa-lg fa-triangle-exclamation"></i>
                </div>-->
                <div class="col-md-9">
                    <b><?php echo $faenaDatosLiena->alias ?> : </b>
                    <?php echo $alertasDatos['alerta'] ?>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-block" onclick="verAlerta('<?php echo $alertasDatos['alerta_id'] ?>')">

                        <i class="fa fa-check"></i> </button>
                </div>

            </div>


            <div class="dropdown-divider"></div>

            

        </a>

    <?php
    }
    ?>

    <div class="dropdown-divider"></div>
    <a href="#" class="dropdown-item dropdown-footer"> </a>
</div>