<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$faenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();
  $monitoreoCas=$_GET["mc"];
 

?>

<script>
  
 
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
    $("#div-container").load("pages/servWindows/divServidores.php?mc=<?php echo $monitoreoCas ?>");
      titulo("", "Servidores <?php echo $monitoreoCas ?>"); 
  }

  $(document).ready(function() {
     
    carga1();
    setInterval(carga1, 20000); // 60000 milisegundos = 1 minuto
  });
</script>