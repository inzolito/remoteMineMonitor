<?php
require_once("../controller/controller-login.php");
require_once("../controller/controller-functions.php");


$system= new systemClass();
$login= new login();
$resultLogin=$login->logearse($_POST["user"],$_POST["password"]);

if($resultLogin)
{
    echo  $system->urlSystem();
}else{
    echo  0;
}
die();

