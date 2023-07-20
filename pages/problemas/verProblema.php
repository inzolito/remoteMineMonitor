<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-problem.php");

$idProblema = $_POST['idp']; //capturamos la id del registro donde fue clickeado el botón 
//echo $idProblema;
 

$problemas = new problemas;
$problemasSql = $problemas->problemaDatos($idProblema); //obtiene el registro del problema y area que debe estar en la tabla
$problemasDatos = $problemasSql->fetch_object();

$soluciones = new problemas;
$solucionesSql = $soluciones->soluciones($idProblema); //obtiene todas las soluciones dependiendo del id del registro que se quiere ver

$funciones = new systemClass;
$datos_usuario= $funciones->datosUsuario($problemasDatos->id_usuario);
?>
<script>
    titulo('<i class="fa-regular fa-book"></i> <?php echo $problemasDatos->titulo ?>', "problemas")
</script>



<div class="card card-widget">
    <div class="card-header">
        <div class="user-block">
            <img class="img-circle" src="dist/img/system/logohxg.jpg" alt="User Image">
            <span class="username"><a href="#"> <?php echo  $datos_usuario->nombre ?></a></span>
            <span class="description"><?php echo $system->formatoFecha($problemasDatos->fecha, "lectura")  ?></span>
        </div>


        <div class="card-tools">

            <button type="button" onclick="editarProblema(<?php echo $idProblema?>)" class="btn btn-tool">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-tool">
                <i class="fas fa-trash"></i>
            </button>
        </div>

    </div>

    <div class="card-body" style="display: block;">

        <?php echo $problemasDatos->descripcion; ?>

    </div>

    <div class="card-footer card-comments" style="display: block;">




        <?php
        $count = 1;
        if ($solucionesSql->num_rows > 0) {

            echo "<h5>Soluciones:</h5>";
            while ($solucionesDatos = $solucionesSql->fetch_object()) {
        ?>


                <div class="card-comment">

                    <img class="img-circle img-sm" src="dist/img/system/logohxg.jpg" alt="User Image">
                    <div class="comment-text">
                        <span class="username">
                            <?php 
                            $datos_usuario_solucion= $funciones->datosUsuario($solucionesDatos->id_usuario);
                            echo $datos_usuario_solucion->nombre
                            ?>
                            <span class="text-muted float-right"><?php echo $system->formatoFecha($solucionesDatos->updated_at, "lectura") ?></span>
                        </span>

                        <?php echo $solucionesDatos->solucion ?>

                    </div>

                </div>

        <?php
            }
        }
        ?>
        <div class="row">
            <div class="col-md-12" id="divCrearSolucion">


                <div class="img-push">
                    <input type="text" class="form-control form-control-sm" placeholder="Agrega un aporte ">
                </div>

            </div>
        </div>

         


    </div>


</div>




<script>
    function editarProblema(id)/*carga en el index lo que realicemos en el archivo crearProblemas.php */
    {

      //var params = { id1: 123, id2: 456};
      //cargaPagina("pages/problemas/problemas.php", "#div-container", params);
      cargarPaginaEnDiv("pages/problemas/crearProblema.php",{idp:id});
    //  $("#div-container").load("pages/problemas/crearProblema.php",{idp:id});
      titulo("Tips y problemas Soporte","Tips");    
    }

    $(document).ready(function() {
        $('#divCrearSolucion').load("pages/problemas/crearSolucion.php", {
            idp: <?php echo $idProblema ?>
        });

    });
</script>