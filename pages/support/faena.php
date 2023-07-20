<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();
$id_faena = $_REQUEST["id"];


//Falta validar cuando no existe la faena
$faenaDatos = $fenaCl->datos($id_faena);
$estadoCheckFaena = $fenaCl->estado($id_faena);
$checkDatos = $fenaCl->datosCheck($id_faena);



?>


<script>
    titulo("<?php echo $faenaDatos->faena ?>", "faena");
</script>





<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title">Estado de soporte.</h3>
    </div>

    <div class="card-body">

        <form id="form-iniciar-check" name="form-iniciar-check" method="post" action="">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Faena</th>
                        <th>Fecha ultimo check</th>
                        <th>Estado</th>
                        <th> Acción </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $faenaDatos->faena ?></td>
                        <td><?php
                            if (isset($checkDatos->fecha)) {
                                echo date("d-m-Y", strtotime($checkDatos->fecha));
                            } else {
                                echo "-";
                            } ?></td>
                        <td><?php echo $estadoCheckFaena ?></td>
                        <td>
                            <input type="hidden" id="idf" name="idf" value="<?php echo $faenaDatos->id; ?>">

                            <?php

                            if (is_object($checkDatos)) {
                                if ($estadoCheckFaena == "Finalizado") {
                                    if ($checkDatos->aprobado == 2) {
                                        echo '<p class="text-warning"><i class="fa-solid fa-clock"></i> Esperando aprobación </p>';
                                    } else {
                            ?>
                                        <button type='submit' id="btn-check" name="btn-check" value="comenzar" class='btn btn-block bg-gradient-info btn-xm'>Comenzar</button>
                                <?php
                                    }
                                }
                            } else {
                                ?>
                                <button type='submit' id="btn-check" name="btn-check" value="comenzar" class='btn btn-block bg-gradient-info btn-xm'>Comenzar</button>

                            <?php
                            }

                            ?>






                            <input type="hidden" id="accion" name="accion" value="crearCheck">

                        </td>
                    </tr>
                </tbody>
            </table>

        </form>
    </div>

</div>


<div id="content-table-check">



</div>







<script>
    $(document).ready(function() {

        $("#form-iniciar-check").submit(function(event) {
            event.preventDefault();

            // .---------------
            var datastring = $('#form-iniciar-check').serialize();
            $.ajax({
                type: "POST",
                url: "build/model/model-faena.php",
                data: datastring,
                success: function(data) {

                    if (data == 0) {

                        $("#btn-check").removeClass("bg-gradient-info");
                        $("#btn-check").addClass("bg-gradient-warning");
                        $("#btn-check").html("Continuar");
                        $("#content-table-check").load("pages/support/tabla_check.php", {
                            idf: $("#idf").val()
                        });

                    } else {
                        $("#content-table-check").load("pages/support/tabla_check.php", {
                            idf: $("#idf").val()
                        });
                    }
                },
                error: function(ss) {
                    alert(JSON.stringify(ss, null, 4));

                }

            });

        });
        //-


    });
</script>