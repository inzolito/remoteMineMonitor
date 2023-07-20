<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();



$primerDiaTurno = $_POST['pdt'];
$ultimoDiaTurno = $_POST['udt'];

$proxDiaTurno = $primerDiaTurno;
$hoy=date("Y-m-d");
$styleTachado="";

//--------------------------------------------------------------
/*
$todayNumber = date("N");
$miercoles = strtotime("Wednesday this week");
$fecha_miercoles = date("Y-m-d", $miercoles);

//si hoy es miercoles o un dia posterior de la semana, quiere decir que este turno terminara la semana siguiente
if ($todayNumber >= 3) {
    $primerDiaTurno = $fecha_miercoles;
    $ultimoDiaTurno = date("Y-m-d", strtotime($fecha_miercoles . "+ 6 days"));
}
//si estamos entre lunes y martes quiere decir que el turno comenzó la semana anterior
if ($todayNumber < 3) {
    $ultimoDiaTurno = $fecha_miercoles;
    $primerDiaTurno = date("Y-m-d", strtotime($fecha_miercoles . "- 6 days"));
}
*/
?>
 
 <div class="row">

 <div class="col-md-12">

    <?php echo "El turno comenzó el dia <b>" . $system->formatoFecha($primerDiaTurno, "lectura") . "</b> y finaliza el día <b>" . $system->formatoFecha($ultimoDiaTurno, "lectura") . "</b>" ?>
 


</div>
 