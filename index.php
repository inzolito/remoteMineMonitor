<?php
require_once("../soporte/build/controller/controller-functions.php");
$system = new systemClass();
$system->validarSesion();




?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HXG | Support Check</title>

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



  <!-- Datatables -->

  <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
</head>




<body class="hold-transition sidebar-mini layout-fixed">
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
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
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



        <li class="nav-item">
          <!-- Sidebar user panel (optional) -->
          <div class="user-panel   d-flex">
            <div class="image">


              <i class="fa-sharp fa-solid fa-user-gear" class="img-circle  " alt="User Image" style=" font-size:20px; padding-top: 8px;"></i>
            </div>
            <div class="info">
              <span class="d-block"> <?php echo $_SESSION['user'] ?></span>
            </div>
          </div>
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


            <li class="nav-header">Menú</li>
            <li class="nav-item">
              <a href="#" class="nav-link" onclick='resumen()'>
                <i class="nav-icon far fa-newspaper"></i>
                <p>
                  Resumen

                </p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link" onclick="lista_faenas()">
                <i class="nav-icon far fa-hammer"></i>
                <p>
                  Monitoreo remoto
                </p>
              </a>

            </li>

            <li class="nav-item">
              <a href="#" class="nav-link" onclick="problemas()">
                <i class="nav-icon far fa-database"></i>
                <p>
                  MaikFluence
                </p>
              </a>

            </li>



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
              <h1 class="m-0" id="htitle" name="htitle">Resumen</h1>
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
      By MaikolSalas
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
  </style>
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
            location.reload()
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




    function lista_faenas2() {
      $("#div-container").load("pages/support/lista_faenas.php");
      titulo("Soporte - Lista faenas", "faenas");

    }


    function resumen() {
      $("#div-container").load("pages/summary/resumen.php");
      titulo("Resumen faenas ", "Resumen");


    }

    function cargaFaena(id) {
      $("#div-container").load("pages/support/faena.php?id=" + id);
      titulo("", "faena");

    }

    function ssh() {
      $("#div-container").load("pages/ssh/ssh.php");
      titulo("Problemas comunes Soporte", "Problemas");
    }

    function cargaMonitoreo(id) {
      $("#div-container").load("pages/support/monitoreo.php?id=" + id);
      titulo("", "faena");

    }

    function cargaMonitoreo2(id, al) {
      $("#div-container").load("pages/support/monitoreoTwo.php?id=" + id);
      titulo("", "faena");

    }

    function cargaServidoresWindows() {
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


    //----------------------------


    function developMessaje() {
      var pruebaDiv = '<div class="alert alert-warning test-message" role="alert" style="position: sticky; top: 0;margin-bottom:-5px; background-color: yellow; text-align: center; z-index: 9999;">' +
        'Entorno de desarrollo del sistema de monitoreon Hexagon Minning.' + 
        '</div>';
      $("body").prepend(pruebaDiv);
    }
    $(document).ready(function() {


      lista_faenas2()

      // este mensaje solo se debe activar en el entorno de desarrollo
      developMessaje()

    });
  </Script>











</body>

</html>