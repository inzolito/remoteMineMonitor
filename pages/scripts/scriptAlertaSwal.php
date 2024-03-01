<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");
require_once("../../build/controller/controller-alerta.php");



$system = new systemClass();
$alertas = new alertas();
$faena = new faena();


 
$id_faena=$_POST["idf"];
$faenaDatos = $faena->datos($id_faena);
$alertasSql = $alertas->alerta($id_faena,0,1,0);
$mensajeAlertaReproducir="<ul>";
$mensajeAlertaReproducirVoz="En ".$faenaDatos->faena;
 $valid=0;

while ($alertaDatos= $alertasSql->fetch_assoc()) {

    $mensajeAlertaReproducir.=" <li>".$alertaDatos["alerta"]."</li>";
    $mensajeAlertaReproducirVoz.=" ".$alertaDatos["alerta"];
    $valid++;
}
$mensajeAlertaReproducir.="</ul>";


$datos =array(
    "texto"=>$mensajeAlertaReproducir,
    "voz"=>$mensajeAlertaReproducirVoz,
    "idf"=>$id_faena,
    "valid"=>$valid
    
);

echo json_encode($datos);
die;
?>

 <script>
 
 </script>
 