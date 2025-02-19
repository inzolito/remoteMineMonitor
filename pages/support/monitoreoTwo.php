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
//$ruta = "/home/jigsaw/monitoreoRemoto/" . $carpeta;
$ruta = $system->rutaDataSet() . $carpeta;
$Ntp = file($ruta . "NtpMon.log");

$pingServerAct = file($ruta . "pingServerAct.log");
$estadoDisco = file($ruta . "estadoServidorMon.log");
$estadoDiscoSecundario = file($ruta . "estadoServidorSecMon.log");

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

  titulo("", "monitoreo");
  /*
    $(document).ready(function() {
      // Función para hacer clic en el botón

      // Configurar el intervalo de tiempo
      var intervaloMinutos = 10;
      var intervaloMilisegundos = intervaloMinutos * 60 * 1000;

      // Hacer clic en el botón cada 10 minutos
      setInterval(clickButton, intervaloMilisegundos);
    });
  */
</script>

<h1>
  <center>
    <div style="font-size: 18px;">
      <i id="IconoOnline" <?php echo $system->iconStatusConexionFaena($alias) ?>> <span style="font-family: Century Gothic">Online</span> </i>
    </div>


    <?php echo "Monitoreo " . $faenaDatos->faena ?>

    <button id="btnRecarga" class='btn btn-primary' onclick='cargaMonitoreo2(<?php echo $id_faena ?>)'><i class='fas fa-sync-alt'></i></button>
  </center>
</h1>


<div class="row">
  <div class="col-md-8">
    <div id="divDatosServidores"></div>
    <div id="divDatosDatos"></div>
  </div>

  <div class="col-md-4">
    <div id="divDatosProcesos"></div>
    <div id="divDatosDatabase"></div>
  </div>
</div>




<script>
  function carga1() {

    $.ajax({
      url: 'pages/support/divServidores.php?idf=' + <?php echo $id_faena ?>,
      success: function(data) {
        $('#divDatosServidores').html(data);
      }

    });

  }

  function carga2() {

    $.ajax({
      url: 'pages/support/divDatabase.php?id=' + <?php echo $id_faena ?>,
      success: function(data) {
        $('#divDatosDatabase').html(data);
      }
    });
  }

  function carga3() {

    $.ajax({
      url: 'pages/support/divDatos.php?id=' + <?php echo $id_faena ?>,
      success: function(data) {
        $('#divDatosDatos').html(data);
      }
    });
  }

  function carga4() {

    $.ajax({
      url: 'pages/support/divProcesos.php?id=' + <?php echo $id_faena ?>,
      success: function(data) {
        $('#divDatosProcesos').html(data);
      }
    });
    notificaciones_alertas()
  }

  function carga5() {

    
    Swal.close();


    idf = '<?php echo $id_faena ?>'
    var formDataAlarmaMensaje = new FormData();
    formDataAlarmaMensaje.append('accion', 'swalAlarma');
    formDataAlarmaMensaje.append('idf', idf);
    $.ajax({
      url: "pages/scripts/scriptAlertaSwal.php",
      dataType: 'json',
      cache: false,
      contentType: false,
      processData: false,
      data: formDataAlarmaMensaje,
      type: 'post',
      success: function(response) {
         if(response.valid>0)
        {
             reproducirAlertaSWA(response.texto, response.idf)
             reproducirMensajeVoz(response.voz)
        
        }


      },
    });
  }


  function iconoOnline(val) {
    //alert(val);
    if (val == 0) {
      $("#IconoOnline").removeClass("text-success")
      $("#IconoOnline").addClass("text-danger")
    } else {
      $("#IconoOnline").removeClass("text-danger")
      $("#IconoOnline").addClass("text-success")
    }
  }



  $(document).ready(function() {

    carga1()
    carga2()
    carga3()
    carga4()
    carga5()
    setInterval(carga1, 20000);
    setInterval(carga2, 180000);
    setInterval(carga3, 180000);
    setInterval(carga4, 30000);
    setInterval(carga5, 8000);

    //reproducirMensajeVoz("Monitoreando <?php //echo $faenaDatos->faena  ?>")
  });
</script>