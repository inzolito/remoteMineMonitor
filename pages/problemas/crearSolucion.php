<?php

require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-problem.php");

$idp = $_POST["idp"];


?>


<form action="" method="POST" id="form-solucion" name="form-solucion">

    <span class="text-muted"> <?php echo $_SESSION["user"] ?> </span>
    <img class="img-fluid img-circle img-sm" src="dist/img/system/logohxg.jpg" alt="Alt Text">

    <input type="hidden" name="idp" id="idp" value="<?php echo $idp  ?>">
    <input type="hidden" name="accion" id="accion" value="agregarSolucion">

    <div class="img-push mt-2 ml-4">
        <textarea name="descripcionSolucion" id="descripcionSolucion" class="form-control" placeholder="Solución , puede incluir fotos."></textarea>
    </div>
    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-success"><i class="far fa-save"></i> Guardar</button>
        <button type="reset" class="btn btn-default ml-2"  onclick="problemas()" > Volver</button>
    </div>

</form>

<script>
    $(function() {
        //Add text editor
        $('#descripcionSolucion').summernote()
    });
    $(document).ready(function() {

        $("#form-solucion").submit(function(event) {

            event.preventDefault();

            var datastring = $("#form-solucion").serialize()
            $.ajax({
                type: "POST",
                url: "build/model/model-problem.php",
                data: datastring,
                success: function(data) {
                     
                    if(data == "1") {
                        Swal.fire(
                            '¡Guardado!',
                            'La solucion fue guardada correctamente.',
                            'success'
                        ).then((result) => {
                            if (result.isConfirmed) {
                                verProblema(<?php echo $idp ?>);
                            }
                        });
                    } else {
                        Swal.fire(
                            '¡Ups!',
                            'Algo salió mal. Inténtelo de nuevo -CodErr:'+ data.toString(),
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