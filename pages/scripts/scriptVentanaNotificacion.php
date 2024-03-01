<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");
require_once("../../build/controller/controller-alerta.php");

$system = new systemClass();
$alertas = new alertas();
$faena = new faena();

$ida = $_REQUEST["ida"];

$alertasSql = $alertas->alerta(0, 0, 0, -1, $ida);
$alertasDatos = $alertasSql->fetch_assoc()


?>

<div class="row">

    <div class="col-md-4 text-center">
        <i class="fa-solid fa-triangle-exclamation " style="font-size: 70px; color:#ffc107"></i>
    </div>
    <div class="col-md-8">

        <h5><?php echo $alertasDatos["alerta"] ?></h5>
        <p>

            <?php print_r($alertasDatos["alerta_sistema"])  ?>
        </p>

    </div>

</div>