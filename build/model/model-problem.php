<?php
require_once("../controller/controller-login.php");
require_once("../controller/controller-functions.php");
require_once("../controller/controller-problem.php");


$system= new systemClass();

$accion=$_POST['accion'];

if($accion == 'crearProblema'){
    $titulo=$_POST['tituloProblema'];
    $descripcion=$_POST['descripcionProblema'];
    $area=$_POST['area'];
    
    $problema= new problemas();
    $problema->insertProblem($titulo,$descripcion, $area);
    echo 1;
}


if($accion == 'editarProblema'){
    $titulo=$_POST['tituloProblema'];
    $descripcion=$_POST['descripcionProblema'];
    $area=$_POST['area'];
    $idp=$_POST['idp'];
    
    $problema= new problemas();
    $problema->updateProblem($titulo,$descripcion, $area,$idp);
    echo 1;
}

if($accion == 'agregarSolucion'){
    $descripcion=$_POST['descripcionSolucion'];
    $idp=$_POST["idp"];
    
    $problema= new problemas();
    $problema->insertsolucion($descripcion, $idp);
    echo 1;
}