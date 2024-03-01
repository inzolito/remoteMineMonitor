<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$faenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();
$id_faena = $_GET["id"];
//$alias = $_GET["al"];




//Falta validar cuando no existe la faena
$faenaDatos = $faenaCl->datos($id_faena);
$estadoCheckFaena = $faenaCl->estado($id_faena);
$checkDatos = $faenaCl->datosCheck($id_faena);
date_default_timezone_set('America/Santiago');

$carpeta = $faenaDatos->alias . "/";
$alias = $carpeta;
$ruta = $system->rutaDataSet(). $carpeta;

$Ntp = file($ruta . "NtpMon.log");
$pingServerAct = file($ruta . "pingServerAct.log");
$estadoDisco = file($rutaS . "estadoServidorMon.log");
$estadoDiscoSecundario = file($rutaS . "estadoServidorSecMon.log");

?>

<script>
  // Array asociativo para almacenar las alertas de faena
  var alertasFaena = {};

  // Función para agregar una nueva alerta de faena al array
  function agregarAlertaFaena(titulo, descripcion, icono) {

    // Crear un nuevo objeto de alerta de faena
    var nuevaAlertaFaena = {
      titulo: titulo,
      descripcion: descripcion,
      icono: icono
    };

    alertasFaena[titulo] = nuevaAlertaFaena; // Agregar la nueva alerta de faena al array

  }

  // Función para eliminar una alerta de faena del array por su ID
  function eliminarAlertaFaena(titulo) {
    delete alertasFaena[titulo];
  }

  // Función para consultar una alerta de faena del array por su ID
  function consultarAlertaFaena(titulo) {
    return alertasFaena[titulo]; // Devolver la alerta de faena correspondiente al ID
  }

  function mensajeAlertasFaena() {
    var titulos = '';
    var descripciones = '';

    for (var id in alertasFaena) {
      if (alertasFaena.hasOwnProperty(id)) {
        var alerta = alertasFaena[id];
        titulos += alerta.titulo + ' - ';
        descripciones += '<li>' + alerta.descripcion + '</li>';
      }
    }

    alertaSwal(titulos, descripciones, "error");
  }

  function cerrarAlerta() {
    Swal.close();
  }

 
</script>



<div class="row">
  <div class="col-md-2">
    <div id="divDatosServidores"></div>
    <div id="divDatosDatos"></div>
  </div>

  <div class="col-md-2">
    <div id="divDatosProcesos"></div>
    <div id="divDatosDatabase"></div>
  </div>
</div>




<script>
  function carga1() {
    $("#div-container").load("pages/servWindows/divServidores.php?id");
      titulo("", "Servidores Codelco"); 
  }

  $(document).ready(function() {
     
    carga1();
    setInterval(carga1, 20000); // 60000 milisegundos = 1 minuto
  });
</script>