<?php

require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-problem.php");

$titulo = "";
$area = 0;
$descripcion = "";
$idp = 0;

if (isset($_POST['idp'])) {
    $idp = $_POST["idp"];

    $problemas = new problemas;
    $problemasSql = $problemas->problemaDatos($idp); //obtiene el registro del problema y area que debe estar en la tabla

    if ($problemasSql->num_rows == 1) {
        $problemasDatos = $problemasSql->fetch_object();
        $titulo = $problemasDatos->titulo;
        $area = $problemasDatos->id_area;
        $descripcion = $problemasDatos->descripcion;
    }
}

?>
<script>
    titulo("Crear problema", "problemas")
</script>
<form action="" method="POST" id="form-problema" name="form-problema">-
    <div class="row">

        <?php
        if (isset($_POST["idp"])) {

        ?>
            <input type="hidden" name="accion" id="accion" value="editarProblema">
            <input type="hidden" name="idp" id="idp" value="<?php echo $idp ?>">
        <?php
        } else {
        ?><input type="hidden" name="accion" id="accion" value="crearProblema"><?php
                                                                            }

                                                                                ?>
        <input type="hidden" name="idp" id="idp" value="<?php echo $idp ?>">
        <!-- /.col -->
        <div class="col-md-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Nuevo tip o problema.</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="form-group">
                        <input type="text" class="form-control" name="tituloProblema" id="tituloProblema" placeholder="Título" value="<?php echo $titulo ?>" required>
                    </div>
                    <div class="form-group">

                        <select class="custom-select" required name="area" id="area">
                            <option value="">Área</option>
                            <?php
                            $areas = new problemas;
                            $areasSql = $areas->areas();
                            $selected = "";
                            while ($areasSeleccion = $areasSql->fetch_object()) {
                                if ($areasSeleccion->id == $area) {
                                    $selected = "selected";
                                }
                            ?>
                                <option <?php echo $selected  ?> value="<?php echo $areasSeleccion->id ?>"><?php echo $areasSeleccion->area ?></option>
                            <?php
                                $selected = "";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="form-group">
                        <textarea name="descripcionProblema" id="descripcionProblema" class="form-control" placeholder="Desci´ción , puede incluir fotos."><?php echo $descripcion; ?></textarea>
                    </div>

                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success"><i class="far fa-save"></i> Guardar</button>
                        <button type="reset" class="btn btn-default ml-2"  onclick="problemas()" > Volver</button>
                    </div>

                </div>
                <!-- /.card-footer -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->


    </div>
</form>

<script>
    $(function() {
        //Add text editor
        $('#descripcionProblema').summernote()
    });
    $(document).ready(function() {

        $("#form-problema").submit(function(event) {
            event.preventDefault();

            var datastring = $("#form-problema").serialize()
            $.ajax({
                type: "POST",
                url: "build/model/model-problem.php",
                data: datastring,
                success: function(data) {
                    if(data == "1") {
                        Swal.fire(
                            '¡Guardado!',
                            'Los datos fueron guardados correctamente.',
                            'success'
                        ).then((result) => {
                            if (result.isConfirmed) {
                                problemas();
                            }
                        });
                    } else {
                        Swal.fire(
                            '¡Ups!',
                            'Algo salió mal. Inténtelo de nuevo. -Cod:'+data,
                            'warning'
                        );
                    }
                },
                error: function(ss) {
                    alert(JSON.stringify(ss, null, 4));
                }
            });
        });
    });
</script>