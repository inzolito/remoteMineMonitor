<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-problem.php");

$system = new systemClass();
//$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();
?>





<div class="card">

    <div class="card-header">
        <h3 class="card-title">Lista</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-5"></div>
            <div class="col-md-2 "><!--columna de tamano 4 corrido por 8 hacia la derecha-->

                <button type="button" onclick="crearProblema()" class="btn btn-block bg-success btn-md">
                    <i class="fa-sharp fa-solid fa-database mr-2"></i> Añadir un nuevo problema</button>

            </div>
            <div class="col-md-5"></div>
        </div>




        <div class="row mt-2">
            <div class="col-sm-12">
                <table class="table table-bordered" id="tableProblemas" name="tableProblemas">
                    <thead>
                        <tr>
                            <th>id</th>
                            <th>problema</th>
                            <th>Area</th>
                            <th>Descripción</th>
                            <th style="width:10%">Fecha</th>
                            <th style="width:10%">Visualizar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $problema = new problemas();
                        $problemasSql = $problema->problemasDatos();

                        while ($problemasDatos = $problemasSql->fetch_object()) {
                        ?>
                            <tr>
                                <td><?php echo $problemasDatos->id ?></td>
                                <td><?php echo $problemasDatos->titulo ?></td>
                                <td><?php echo $problemasDatos->area ?></td>
                                <td><?php

                                    $text = $problemasDatos->descripcion;
                                    $cut_text = mb_substr($text, 0, 600, "UTF-8");
                                    $stripped_text = strip_tags($cut_text);
                                    echo $stripped_text;

                                    ?></td>

                                <td><?php echo $system->formatoFecha($problemasDatos->updated_at, "vista") ?></td>
                                <td>
                                    <button type="button" onclick="verProblema(<?php echo $problemasDatos->id  ?>)" class="btn btn-block btn-info btn-sm">Ver</button>

                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>


            </div>


        </div>
    </div>

</div>


<script>
    $(document).ready(function() {
       
        $('#tableProblemas').DataTable({
            order: [
                [5, 'desc']
            ]
        });


    });
</script>