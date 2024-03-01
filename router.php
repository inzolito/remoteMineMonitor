<?php
require_once("../soporte/build/controller/controller-functions.php");
require_once("../soporte/build/controller/controller-faena.php");

$system = new systemClass();
$system->validarSesion();
$conn = $system->conectaDB();
$faenaCl = new faena();

// Obtener el segmento de la URL
$urlSegment = isset($_GET['url']) ? $_GET['url'] : 'home';
$alias=$_POST["alias"];
$routes = [];
 

//$sql_query = "select * from faenas  where estado=1 order by faena asc";
$sql_query = "select f.id id, f.faena faena, f.estado estado, f.alias alias from faenas f join permisos_faenas p on(f.id=p.id_faena) where estado=1 and id_permiso='" . $_SESSION["id_permiso"] . "' order by faena asc";
//echo $sql_query;
$faenasSql = $conn->query($sql_query);
while ($faenaDatos = $faenasSql->fetch_assoc()) 
{
    $routes[$faenaDatos["alias"]]="pages/support/monitoreoTwo.php?id=".$faenaDatos["id"];
}

//print_r($routes);

// Rutas disponibles
/*
$routes = [
    'amcen' => 'pages/support/monitoreoTwo.php?id=3',
    'amant' => 'pages/support/monitoreoTwo.php?id=1',
    'mlcc' => 'pages/support/monitoreoTwo.php?id=2',
];
*/
// Verificar si la ruta existe, de lo contrario, mostrar 404
if (array_key_exists($alias, $routes)) {
    // Cargar la página correspondiente
    echo  $routes[$alias];
} else {
    echo 0;
}
