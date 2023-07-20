<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hexagon</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="../../plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
</head>

<body class="hold-transition login-page">
  <div class="login-box">
    <div class="login-logo">
      <a href="../../index2.html"><b>Hexa</b>gon</a>
    </div>
    <!-- /.login-logo -->
    <div class="card">
      <div class="card-body login-card-body">
        <p class="login-box-msg">Iniciar sesión</p>

        <form action="" id="form-login" name="form-login" method="post">


          <div class="input-group mb-3">
            <input type="text" class="form-control" id="user" name="user" placeholder="user">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-user"></span>
              </div>
            </div>
          </div>

          <div class="input-group mb-3">
            <input type="password" class="form-control" id="password" name="password" placeholder="Password">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>

              </div>
            </div>
          </div>


          <div class="input-group mb-3">
            <div class="input-group mb-3">

              <span id="span-error" class="text-danger" style="visibility:hidden"><i class="icon fas fa-ban"></i> Usuario o contraseña incorrectos.</span>

            </div>
          </div>



          <div class="row">
            <div class="col-8">
              <div class="icheck-primary">
                <input type="checkbox" id="remember">
                <label for="remember">
                  Recuerdame
                </label>
              </div>
            </div>

            <!-- /.col -->
            <div class="col-4">
              <button type="submit" id="btn-login" class="btn btn-primary btn-block">Sign In</button>

            </div>
            <!-- /.col -->
          </div>
        </form>


      </div>
      <!-- /.login-card-body -->
    </div>
  </div>

  <!-- /.login-box -->

  <!-- jQuery -->
  <script src="../../plugins/jquery/jquery.js"></script>
  <!-- Bootstrap 4 -->
  <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <!-- AdminLTE App -->
  <script src="../../dist/js/adminlte.min.js"></script>
  <script>
    $(document).ready(function() {


      $("#form-login").submit(function(event) {
        event.preventDefault();

        var datastring = $('#form-login').serialize();
        $.ajax({
          type: "POST",
          url: "../../build/model/model-login.php",
          data: datastring,

          success: function(data) {
            if (data == 0) {

            } else {
              
              window.location.replace(data);

            }
          },
          error: function(ss) {
            alert("error " + ss);
          }

        });

      });



    });
  </Script>



</body>

</html>