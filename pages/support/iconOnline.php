<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$system->validarSesion();
$conn = $system->conectaDB();
$faenaCl = new faena();
$id_faena = $_GET['idf'];

$faenaDatos = $faenaCl->datos($id_faena);
$carpeta = $faenaDatos->alias . "/";
$alias = $carpeta;

$ruta = "/home/jigsaw/monitoreoRemoto/" . $carpeta;

/*
$log= file($ruta . "ProcesosJamsMon.log");

$fechaActual = date("Y-m-d H:i:s");
echo $fechaActual . " -- " . $log[0];
 
$fechaActual = new DateTime($fechaActual);


$fechaLog = new DateTime($log[0]);
$diferencia = ($fechaActual->getTimestamp() - $fechaLog->getTimestamp()) / 60;
  
$iconoOnline = " <i class='fas fa-wifi text-success mr-2' ></i>";
 
if ($diferencia>1000)  $iconoOnline = "<i class='fas fa-wifi text-danger mr-2'></i>";
 echo $diferencia;
 echo   $iconoOnline  ;  
 */
$log = file($ruta . "ProcesosJamsMon.log");
if (file_exists($ruta . "ProcesosJamsMon.log")) {

    $fechaActual = new DateTime();
    //echo "Fecha actual: " . $fechaActual->format("Y-m-d H:i:s") . "<br>";
    //echo "Fecha del archivo: " . $log[0] . "<br>";

    $fechaLog = DateTime::createFromFormat("Y-m-d H:i:s", trim($log[0]));
    //echo "Fecha del archivo (objeto DateTime): " . $fechaLog->format("Y-m-d H:i:s") . "<br>";

    $diferencia = $fechaActual->diff($fechaLog)->i;
    //echo "Diferencia en minutos: " . $diferencia . "<br>";

    $iconoOnline = "<i class='fas fa-wifi text-success mr-2'></i>";

    if ($diferencia > 6) {
        $iconoOnline = "<i class='fas fa-wifi text-danger mr-2'></i>";
    }
} else {

    $iconoOnline = "<i class='fas fa-wifi text-danger mr-2'></i>";
}
echo  $iconoOnline;
