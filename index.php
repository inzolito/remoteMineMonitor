<?php
$configuracionJson = '.config.json';
$arrayConfig = json_decode(file_get_contents($configuracionJson), true);
$entorno = $arrayConfig["APP_ENV"];
require_once("../" . $arrayConfig["APP_DIR"] . "/build/controller/controller-functions.php");

$system = new systemClass();
$system->validarSesion();


$configuracionJson = '.config.json';
$arrayConfig = json_decode(file_get_contents($configuracionJson), true);
$entorno = $arrayConfig["APP_ENV"];
$mensajeEntornoDesarrollo = '<div class="alert alert-warning test-message" role="alert" style="position: sticky; top:
  0;margin-bottom:-5px; background-color: yellow; text-align: center; z-index: 9999;">Entorno de desarrollo del
   sistema de monitoreon Hexagon Minning.</div>';



?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HXG | Monitoreo Remoto</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <!-- our project just needs Font Awesome Solid + Brands -->
  <link href="plugins/fontawesome-free/css/fontawesome.css" rel="stylesheet">
  <link href="plugins/fontawesome-free/css/brands.css" rel="stylesheet">
  <link href="plugins/fontawesome-free/css/solid.css" rel="stylesheet">



  <!-- Clase nativa de RMM-->
  <link rel="stylesheet" href="build/css/clasesNativas.css">




  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="plugins/summernote/summernote-bs4.min.css">
  <!--sweetAlert -->
  <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <!-- Calendar -->
  <link rel="stylesheet" href="plugins/fullcalendar/main.css">

  <!-- Incluir CodeMirror.js -->
  <script src="plugins/codemirror/codemirror.js"></script>

  <!-- Incluir CodeMirror.css -->
  <link rel="stylesheet" href="plugins/codemirror/codemirror.css">

  <!-- Incluir el plugin de CodeMirror para AdminLTE -->
  <link rel="stylesheet" href="plugins/codemirror/addon/fold/foldgutter.css">
  <link rel="stylesheet" href="plugins/codemirror/addon/dialog/dialog.css">
  <link rel="stylesheet" href="plugins/codemirror/addon/search/matchesonscrollbar.css">
  <link rel="stylesheet" href="plugins/codemirror/addon/search/searchcursor.css">

  <!--  Plugin jszip export  -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>


  <!-- Datatables -->

  <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.dataTables.min.css">


  <link rel="stylesheet" href="plugins/datatables-buttons/js/buttons.html5.min.js">
  <link rel="stylesheet" href="plugins/datatables-buttons/js/dataTables.buttons.min.js">


</head>




<body class="hold-transition sidebar-mini layout-fixed">
  <?php if ($entorno == "development") echo $mensajeEntornoDesarrollo; ?>
  <div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
      <img class="animation__shake" src="dist/img/system/logohxg.jpg" alt="AdminLTELogo" height="60" width="60">
    </div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <!-- Left navbar links -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" id="btn_menu" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>

      </ul>

      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">


        <!-- Messages Dropdown Menu -->


        <!-- Notifications Dropdown Menu
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge">15</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-item dropdown-header">15 Notifications</span>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-envelope mr-2"></i> 4 new messages
            <span class="float-right text-muted text-sm">3 mins</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-users mr-2"></i> 8 friend requests
            <span class="float-right text-muted text-sm">12 hours</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-file mr-2"></i> 3 new reports
            <span class="float-right text-muted text-sm">2 days</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
        </div>
      </li>

 -->


        <!-- Menu para cerrar sesion -->
        <li class="nav-item dropdown user-menu">
          <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
            <i class="fas fa-user"></i>
            <span class="hidden-xs"><?php echo $_SESSION['user'] ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <li class="dropdown-header"><?php echo $_SESSION['nombre'] . " " . $_SESSION['apellido'] ?></li>
            <li class="dropdown-divider"></li>
            <a href="#" id="cerrarSesionButton">
              <li class="dropdown-item">

                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar sesión

              </li>

            </a>
            <li class="dropdown-divider"></li>
            <li class="dropdown-item dropdown-footer">
              <span class="float-right text-muted text-sm"><?php echo ""; ?></span>
            </li>
          </ul>
        </li>


        <!-- Menu para notificaciones -->
        <li class="nav-item dropdown" id="divContenedorNotificaciones">

        </li>

        <li class="nav-item">
          <a class="nav-link" data-widget="fullscreen" href="#" role="button">
            <i class="fas fa-expand-arrows-alt"></i>
          </a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background: #004f67 !important;">
      <!-- Brand Logo -->
      <a href="<?php echo $system->urlSystem() ?>" class="brand-link">
        <img src="dist/img/system/logohxg.jpg" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Hexagon </span>
      </a>

      <!-- Sidebar -->
      <div class="sidebar">




        <!-- Sidebar Menu -->
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->



            <li class="nav-header">Home</li>
            <li class="nav-item">
              <a href="#" class="nav-link" onclick="carga_modulo_container('index/inicio.php','Home')">
                <i class="nav-icon far fa-home"></i>
                <p>
                  Inicio

                </p>
              </a>
            </li>



            <li class="nav-header">Menú</li>

            <!--
            <li class="nav-item">
              <a href="#" class="nav-link" onclick='resumen()'>
                <i class="nav-icon far fa-newspaper"></i>
                <p>
                  Resumen

                </p>
              </a>
            </li>
-->

            <li class="nav-item">
              <a href="#" class="nav-link" onclick="lista_faenas()">
                <!-- <a href="#" class="nav-link" onclick="carga_modulo_container('support/lista_faenas.php','Lista faenas')"> -->
                <i class="nav-icon far fa-hammer"></i>

                <p>
                  Monitoreo remoto
                </p>
              </a>

            </li>


            <?php
            if ($_SESSION["permiso"] == "Administrador" || $_SESSION["permiso"] == "Soporte") {
            ?>

              <!-- Tickets -->
              <li class="nav-item ">
                <a href="#" class="nav-link">
                  <i class="nav-icon far fa-chart-pie"></i>
                  <p>
                    Tickets
                    <i class="fas fa-angle-right right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">


                  <li class="nav-item ml-4">
                    <a href="#" class="nav-link" onclick="carga_modulo_container2('tickets/tickets.php','Tickets')">
                      <i class="fa-solid fa-angle-right"></i>
                      <p>
                        Vista Tickets
                      </p>


                    </a>
                  </li>

                  <li class="nav-item ml-4">
                    <a href="#" class="nav-link" onclick="carga_modulo_container2('tickets/trazabilidad.php','Trazabilidad')">
                      <i class="fa-solid fa-angle-right"></i>
                      <p>Trazabilidad</p>
                    </a>
                  </li>

                  <li class="nav-item ml-4">
                    <a href="#" class="nav-link" onclick="carga_modulo_container2('tickets/resumen_mes.php','Resumen mensual')">
                      <i class="fa-solid fa-angle-right"></i>
                      <p>Resumen Mes</p>
                    </a>
                  </li>



                </ul>
              </li>



              <!-- Turnos -->
              <li class="nav-item ">
                <a href="#" class="nav-link">
                  <i class="nav-icon far fa-user-group"></i>
                  <p>
                    Turnos
                    <i class="fas fa-angle-right right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">


                  <li class="nav-item ml-4">
                    <a href="#" class="nav-link" onclick="carga_modulo_container('turnos/turnos.php','Turnos')">
                      <i class="fa-solid fa-angle-right"></i>
                      <p>
                        Vista Turnos
                      </p>


                    </a>
                  </li>

                  <li class="nav-item ml-4">
                    <a href="#" class="nav-link" onclick="carga_modulo_container('turnos/checklist.php','CheckList')">
                      <i class="fa-solid fa-angle-right"></i>
                      <p>Checklist</p>
                    </a>
                  </li>




                </ul>
              </li>

            <?php
            }
            ?>


            <li class="nav-item">
              <a href="#" class="nav-link" onclick="problemas()">
                <i class="nav-icon far fa-database"></i>
                <p>
                  MaikFluence
                </p>
              </a>

            </li>


            <!-- Websocket 
            <li class="nav-item ">
              <a href="#" class="nav-link">
                <i class="nav-icon far fa-lab"></i>
                <p>
                  Webssocket
                  <i class="fas fa-angle-right right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">


                <li class="nav-item ml-4">
                  <a href="#" class="nav-link" onclick="carga_modulo_container('webSocket/clienteWebsocket.php','cliente')">
                    <i class="fa-solid fa-angle-right"></i>
                    <p>
                      Cliente WebSocket
                    </p>


                  </a>
                </li>

                <li class="nav-item ml-4">
                  <a href="#" class="nav-link" onclick="carga_modulo_container('webSocket/servidorWebsocket.php','cliente')">
                    <i class="fa-solid fa-angle-right"></i>
                    <p>Servidor Websocket</p>
                  </a>
                </li>




              </ul>
            </li>
-->



          </ul>
        </nav>
        <!-- /.sidebar-menu -->
      </div>
      <!-- /.sidebar -->
    </aside>




    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0" id="htitle" name="htitle"> </h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" id="liruta" name="liruta">Support</li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content-header -->








      <!-- Main content  contenedor donde se cargan las paginas-->
      <section class="content">

        <div class="container-fluid" id="div-container">



        </div>

      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->







    <!-- Modals -->

    <div class="modal" tabindex="-1" role="dialog" id="modalStandard">
      <div class="modal-dialog modal-lg" style="width:1000px !important" role="document">
        <div class="modal-content" id="modalStandardContent">
          <div class="modal-header" id="modalStandardHeader">
            <h5 class="modal-title" id="modalStandarTittle"> </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body img-responsive" id="modalStandardBody">

          </div>
          <div class="modal-footer" id="modalStandardFooter">
            <button type="button" class="btn btn-primary" id="modalStandarOk">Aceptar</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal" id="modalStandarClose">Cerrar</button>
          </div>
        </div>
      </div>
    </div>








    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalLarge">
      <div class="modal-dialog modal-lg " style="max-width:1300px !important">

        <div class="modal-content" id="modalLargeContent">
          <div class="modal-header" id="modalLargeHeader">
            <h5 class="modal-title" id="modalLargeTittle"> </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" id="modalLargeBody">
            <p> </p>
          </div>
          <div class="modal-footer" id="modalLargeFooter">
            <button type="button" class="btn btn-primary" id="modalLargeOk">Aceptar</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal" id="modalLargeClose">Cerrar</button>
          </div>
        </div>



      </div>
    </div>


    <!--  End Modals -->




    <footer class="main-footer">


      <strong><a href="<?php echo $system->urlSystem(); ?>">Sistema de monitoreo remoto.</a></strong>
      Developed by Maikol Salas.
      <div class="float-right d-none d-sm-inline-block">
        <b>Version</b> 1.0
      </div>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-light">
      <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
  </div>
  <!-- ./wrapper -->

  <!-- jQuery -->
  <script src="plugins/jquery/jquery.min.js"></script>
  <!-- jQuery UI 1.11.4 -->
  <script src="plugins/jquery-ui/jquery-ui.min.js"></script>
  <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
  <script>
    $.widget.bridge('uibutton', $.ui.button)
  </script>
  <!-- Bootstrap 4 -->
  <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <!-- ChartJS -->
  <script src="plugins/chart.js/Chart.min.js"></script>
  <!-- Sparkline -->
  <script src="plugins/sparklines/sparkline.js"></script>
  <!-- JQVMap -->
  <script src="plugins/jqvmap/jquery.vmap.min.js"></script>
  <script src="plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
  <!-- jQuery Knob Chart -->
  <script src="plugins/jquery-knob/jquery.knob.min.js"></script>
  <!-- daterangepicker -->
  <script src="plugins/moment/moment.min.js"></script>
  <script src="plugins/daterangepicker/daterangepicker.js"></script>
  <!-- Tempusdominus Bootstrap 4 -->
  <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
  <!-- Summernote -->
  <script src="plugins/summernote/summernote-bs4.min.js"></script>
  <!-- overlayScrollbars -->
  <script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <!-- AdminLTE App -->
  <script src="dist/js/adminlte.js"></script>
  <!-- AdminLTE for demo purposes -->
  <script src="dist/js/demo.js"></script>
  <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
  <script src="dist/js/pages/dashboard.js"></script>

  <!--SweetAlert -->
  <script src="plugins/sweetalert2/sweetalert2.min.js"></script>


  <!-- Include the calendar JS -->
  <script src="plugins/fullcalendar/main.min.js"></script>


  <script src="plugins/datatables/jquery.dataTables.min.js"></script>
  <script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
  <script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
  <script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
  <script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
  <script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>

  <script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
  <script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
  <script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

  <style>
    .loading {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100%;
      background: rgba(255, 255, 255, 0.7);
    }

    .dropdown-menu-xl {
      width: 100%;
      max-width: 1000px;
    }
  </style>


  <script>
    document.getElementById("cerrarSesionButton").addEventListener("click", function(e) {



      e.preventDefault();
      Swal.fire({
        title: '¿Estás seguro?',
        text: "¿Deseas cerrar sesión?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {

          $.ajax({
            url: 'pages/scripts/closeSession.php',
            success: function(data) {
              location.reload();
            }
          });


        }
      });
    });
  </script>






  <script>
    function titulo(titulo, ruta) {
      $("#htitle").html(titulo)
      $("#liruta").html(ruta)

    }

    function lista_faenas() {

      Swal
        .fire({
          title: "¿Realmente deseas entrar al area de monitoreo?",
          text: "Se perderá toda la información no guardada",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: "Sí",
          cancelButtonText: "No",
        })
        .then(resultado => {
          if (resultado.value) {
            // Hicieron click en "Sí"
            //alert("se elimina la venta*");
            carga_modulo_container('support/lista_faenas.php', 'Lista faenas');
            // La manera correcta de cargar la url  es con este codigo de php 
            // window.location.href = "";

          } else {
            // Dijeron que no
          }
        });


    }

    function alertaSwal(titulo, mensaje, icono) {

      Swal.fire({
        icon: icono,
        title: titulo,
        html: mensaje,
        confirmButtonText: "Aceptar",
        confirmButtonColor: '#d33'
      });


    }

    function verAlerta(ida) {

      $("body").append("<span id='btnModalAlerta' data-toggle='modal' data-target='#modalStandard'>  </span>");
      $("#btnModalAlerta").click();
      $("#btnModalAlerta").remove();

      $("#modalStandarOk")
        .removeClass("btn-primary")
        .addClass("btn-danger")
        .text("Resuelto")
        .click(function() {
          alerta_solucionada(ida);
        });

      // Cambiar el texto del botón "Cerrar"
      $("#modalStandarClose").text("Cancelar");

      // Centrar ambos botones horizontalmente
      $("#modalStandardFooter button").addClass("mx-auto");

      $("#modalStandardFooter").addClass("pl-10");

      $("#modalStandardBody").load("pages/scripts/scriptVentanaNotificacion.php", {
        ida: ida
      });
    }


    function alerta_solucionada(idAlerta) {



      Swal
        .fire({
          title: "Cerrar alerta",
          text: "La alerta se cerrará y no aparecerá en las notificaciones",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: "Cerrar alerta",
          cancelButtonText: "Cancelar",
        })
        .then(resultado => {

          if (resultado.value) {
            var formDataAlerta = new FormData();
            formDataAlerta.append('accion', 'alertaSolucionada');
            formDataAlerta.append('ida', idAlerta);
            $.ajax({
              url: "build/model/model-alerta.php",
              dataType: 'text',
              cache: false,
              contentType: false,
              processData: false,
              data: formDataAlerta,
              type: 'post',
              success: function(data) {
                Swal.fire({
                  icon: 'success',
                  title: 'Alerta Cerrada',
                  text: 'La alerta se cerró correctamente.',
                });
                notificaciones_alertas()
                $("#modalStandarClose").click();
                Swal.close()

              }
            });


            //location.reload()
          } else {

          }
        });


    }

    function notificaciones_alertas() {

      $("#divContenedorNotificaciones").load("pages/scripts/scriptNotificaciones.php");

    }



    function resumen() {
      $("#div-container").load("pages/summary/resumen.php");
      titulo("Resumen faenas ", "Resumen");


    }

    function cargaFaena(id) {
      $("#div-container").load("pages/support/faena.php?id=" + id);
      titulo("", "faena");

    }


    function carga_modulo_container(page, title) {
      $("#div-container").load(`pages/${page}`);

    }
    /*nueva función para que cuando se carguen las páginas del módulo haga recall y no haya que refrescar manualmente la página para que estos se muestren */
    function carga_modulo_container2(page, title) {
      $("#div-container").fadeOut(100)
      $("#div-container").load(`pages/${page}`, function() {
        setTimeout(() => { //dar tiempo a la primera carga antes de volver a cargar.
          $("#div-container").load(`pages/${page}`, function() {
            $("#div-container").fadeIn(100)

          });
        }, 1000); //
      });
      titulo("", title);

    }

 

    function cargaMonitoreo(id) {
      $("#div-container").load("pages/support/monitoreo.php?id=" + id);
      titulo("", "faena");

    }

    /*
    function cargaMonitoreoDos(id, al) {
      $("#div-container").load("pages/support/monitoreoTwo.php?id=" + id);
      titulo("", "faena");

    }
*/
    function cargaMonitoreoDos(id, al) {
      $("#div-container").load("pages/support/monitoreoTwo.php?id=" + id);
      titulo("", "faena");

    }

    function cargaFaenasMonitoreoMaster(id, div) {
      $("#" + div).load("pages/support/monitoreoTwo.php?id=" + id);
      titulo("", "faena");

    }

    function cargaMonitoreo2(id, alias) {
      var ruta = "pages/support/monitoreoTwo.php";
      var url = 'router.php?url=' + ruta + '&alias=' + alias + '&id=' + id;

      $("#div-container").load("pages/support/monitoreoTwo.php?id=" + id);
      window.history.pushState({}, '', "<?php echo $system->urlSystem() ?>" + alias)

    }


    function cargaServidoresWindows() {
      $("#div-container").load("pages/servWindows/monitoreoWin.php");
      titulo("", "Servidores Codelco");

    }

    function monitoreoCAS(mc) {
      $("#div-container").load("pages/servWindows/monitoreoCAS.php?mc=" + mc);
      titulo("", "Servidores " + mc);

    }

    function casCodelco() {
      $("#div-container").load("pages/servWindows/monitoreoWin.php");
      titulo("", "Servidores Codelco");

    }

    function problemas() /*carga en el index lo que realicemos en el archivo problemas.php */ {
      // $("#div-container").load("pages/problemas/problemas.php");
      cargarPaginaEnDiv("pages/problemas/problemas.php", {});
      titulo("Tips y problemas Soporte", "Tips");


    }


    function crearProblema() /*carga en el index lo que realicemos en el archivo crearProblemas.php */ {
      $("#div-container").load("pages/problemas/crearProblema.php");
      titulo("Tips y problemas Soporte", "Tips");
    }

    function verProblema(idp) { //Se le pasa como arfumento id ya que es el indice verificador

      cargarPaginaEnDiv("pages/problemas/verProblema.php", {
        idp: idp
      });
      titulo("Tips y problemas Soporte", "Tips");

    }

    function statusListaFaena(id, idDiv, sizeIcon = 0) {
      $.ajax({
        url: 'pages/support/iconOnline.php?idf=' + id,
        success: function(data) {
          $('#' + idDiv + '_' + id).html(data);
        }
      });
    }

    // Funcion para cargar cualquier pagina en cualquier div. por defecto es el div container del index.
    function cargarPaginaEnDiv(pagina, argumentos, div = "div-container") {

      // $("#"+div).load(pagina, argumentos, function() {
      // });

      // Agregar mensaje de carga
      $("#" + div).html("<div class='loading-message'>Cargando...</div>");

      // Cargar página
      $("#" + div).load(pagina, argumentos, function() {
        // Remover mensaje de carga una vez que se completa la carga
        $(".loading-message").remove();
      });
    }

    function reproducirMensajeVoz(mensaje) {

      var utterance = new SpeechSynthesisUtterance(mensaje);
      var synth = window.speechSynthesis;
      var vocesDisponibles = synth.getVoices();
      utterance.voice = vocesDisponibles[1]; // Selecciona la primera voz
      synth.speak(utterance);


    }

    function cargaMaster() {

      $("#div-container").load("pages/support/monitoreoMaster.php?");
      titulo("", "faena");

    }


    function reproducirAlertaSWA(mensajeAlertConfirm, idf) {
      Swal.fire({
        id: 'swalAlert',
        title: 'Alerta!',
        html: mensajeAlertConfirm,
        icon: 'error',
        confirmButtonColor: 'rgb(214 48 48)',
        confirmButtonText: 'Visto',
        backdrop: `
        rgba(255, 0, 0, 0.8)
                  left top
                  no-repeat
                `
      }).then((result) => {
        if (result.isConfirmed) {

          var formDataAlerta = new FormData();
          formDataAlerta.append('accion', 'alertaVista');
          formDataAlerta.append('idf', idf);
          $.ajax({
            url: "build/model/model-alerta.php",
            dataType: 'text',
            cache: false,
            contentType: false,
            processData: false,
            data: formDataAlerta,
            type: 'post',
            success: function(response) {

              if (response == 1) {

              } else {
                alert(response)
              }
            },
          });



        }
      })
    }

    //----------------------------


    $(document).ready(function() {

      //$('[data-toggle="tooltip"]').tooltip();
      setInterval(notificaciones_alertas(), 3000);
      $("#btn_menu").click()
      var urlSegments = window.location.pathname.split('/');
      var alias = urlSegments[2];

      var formDataAlerta = new FormData();
      formDataAlerta.append('accion', 'cargarRuta');
      formDataAlerta.append('alias', alias);
      $.ajax({
        url: "router.php",
        dataType: 'text',
        cache: false,
        contentType: false,
        processData: false,
        data: formDataAlerta,
        type: 'post',
        success: function(data) {

          if (data == 0) {


          } else {

            //cargaMonitoreoDos()
            $("#div-container").load(data);


          }
        }


      });



      carga_modulo_container('index/inicio.php', 'Home')
      notificaciones_alertas()





    });
  </Script>











</body>

</html>