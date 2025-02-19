<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");
require_once("../../build/controller/controller-alerta.php");

$system = new systemClass();
$alertas = new alertas();
$faena = new faena();
 
 $csv_file = "../../data/tickets.csv";
 $rows = file($csv_file);
 $lastRow = $rows[count($rows) - 1];
$lastRowArray=explode(",",$lastRow);

echo $lastRowArray[1];
/*
echo "<pre>";
print_r(str_getcsv($lastRow));  
echo "</pre>";
*/ 



