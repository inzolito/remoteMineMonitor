<?php
require_once("../controller/controller-alerta.php");
require_once("../controller/controller-functions.php");

$system = new systemClass();
 
if (isset($_POST["idf"])) {

    $idFaena = $_POST["idf"];
}
$accion = $_POST["accion"];


if ($accion == "crearAlerta") {
    $alertas = new alertas();
    $mensajeAlerta = $_POST["alerta"];
    $idAlertaSistema = $_POST["idas"];

   // $alertas->insertAlert($idFaena,$idAlertaSistema, $mensajeAlerta);
}

if ($accion == "alertaVista") {
    $alertas = new alertas();
    $alertas->alertaVista($idAlertaSistema,$idFaena);
    echo 1;
}
if ($accion == "alertaSolucionada") {
 
    $alertas = new alertas();
    $idAlerta = $_POST["ida"];
    $alertas->alertaSolucionada($idAlerta);
    echo 1;
}