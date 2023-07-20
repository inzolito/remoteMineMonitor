<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();
$id_faena = $_POST["idf"];

$datosCheckFaena = $fenaCl->datosCheck($id_faena);
$datosFaena = $fenaCl->datos($id_faena);




?>

<div class="card ">

    <div class="card-header bg-info">

        <h3 class="card-title">
            <?php echo $datosFaena->faena ?>
        </h3>
        <div class="card-tools">

            <input type="hidden" id="accion" name="accion" value="guardarCheck">
            <input type="hidden" id="idf" name="idf" value="<?php echo $id_faena ?>">
        </div>

    </div>

    <div class="card-body">
        <form action="" id="form-comentarioCheck" method="post" name="form-comentarioCheck" enctype="multipart/form-data">

            <!-- Comentarios -->
            <div class="row">
                <div class="col-md-12">
                    <span>
                    <?php 
                     
                        echo "Check de la faena de ".$datosFaena->faena." correspondiente al dia <b>".$system->formatoFecha($datosCheckFaena->fecha ,"lectura").":</b>";
                    ?>
                    </span>
                    <?php

                        $comentariosCheckFaena=$fenaCl->comentariosCheckFaena($datosCheckFaena->id);
                        if(is_object($comentariosCheckFaena))
                        {
                            echo "<p><ul>";
                            while($datosComentariosCheckFaena=$comentariosCheckFaena->fetch_object())
                            {
                                // echo "<li>".$system->formatoFecha($datosComentariosCheckFaena->fecha,"vistaDT")." -->".$datosComentariosCheckFaena->comentario."</li>";
                               $estadoPrint="<i class='fa-solid fa-check'></i> Aprobado";
                                if($datosComentariosCheckFaena->estado==0)
                                {
                                    $estadoPrint="<i class='fa-solid fa-x'></i> Rechazado";

                                }
                                echo "<li>".$estadoPrint." > ".$datosComentariosCheckFaena->comentario."</li>";
                            }
                            echo "</ul></p>";
                        }else{

                        }
                
                        
                    ?>
                    <textarea id="comentarioCheckFaena" name="ComentarioCheckFaena" class="form-control" placeholder="Comentario (Opcional)"></textarea>
                    
                </div>
            </div>

            <!-- Botones -->
            <div class="row  mt-2">
                <div class="col-md-12 text-right">
                    <input type="hidden" name="idCheckFaena" value="<?php echo $datosCheckFaena->id  ?>" />
                    <input type="hidden" name="accion" value="guardarComentario" />
                    <button type="button" name="btnAprobar"  id="btnAprobar"  class="btn  btn-success btn-sm text-right">Aprobar</button>
                    <button type="button" name="btnRechazar" id="btnRechazar" class="btn  btn-warning btn-sm text-right" >Rechazar</button>
                </div>
            </div>

            <!-- Tabla -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th style="width:10%">Proceso</th>
                                <th style="width:40px">Subproceso</th>
                                <th>Estado </th>
                                <th>Comentario</th>
                                <th style="width:20%">Imágenes</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php

                            $procesosSql = $conn->query("select * from procesos order by id asc");
                            while ($procesosDatos = $procesosSql->fetch_assoc()) {

                                $subpSql = $conn->query("select id_proceso,id_subproceso, subproceso, id_check_faena,estado,scf.comentario,fecha ,scf.id id_subpchf
                                                    FROM subprocesos as s left join subproceso_check_faena as scf on(s.id=scf.id_subproceso) 
                                                    where id_check_faena=" . $datosCheckFaena->id . " and   id_proceso=" . $procesosDatos["id"] . " 
                                                    order by id_subproceso asc;
                            
                            
                            
                                                    ");
                                // select * from subproceso_check_faena where id_proceso=" . $procesosDatos["id"] . " order by id asc"
                                $totalSubproceso = $subpSql->num_rows;
                                echo '
                        <tr>
                            <td rowspan=' . $totalSubproceso . '>' . $procesosDatos["id"] . '</td>
                            <td rowspan=' . $totalSubproceso . '>' . $procesosDatos["proceso"] . '</td>
                        
                        ';
                                $pr = 0;
                                while ($subpDatos = $subpSql->fetch_assoc()) {

                                    if ($pr == 1) echo '<tr>';

                                    $comentario = $subpDatos["comentario"];
                                    echo '<td>' . $subpDatos["subproceso"] . '</td>
                                <td>
                                ';

                                    if ($subpDatos["estado"] == 3) {
                                        echo '<b>-</b>';
                                    }



                                    if ($subpDatos["estado"] == 1) {
                                        echo '<span style="color: green">Ok </span>';
                                    }

                                    if ($subpDatos["estado"] == 0) {
                                        echo '<span style="color: red">Bad </span>';
                                    }


                                    echo '
                                </td>
                                <td>
                                ' . $subpDatos["comentario"] . '
                                </td>
                                
                                <td>
                                <div class="custom-file">
                                </div>
                                
                                
                                

                                    ';
                                    $fotos = $fenaCl->fotoSubprocesoCheck($subpDatos["id_subpchf"]);

                                    if (is_object($fotos)) {
                                        echo "<ul>";
                                        while ($fotosDatos = $fotos->fetch_object()) {
                            ?> <li>
                                                <a href="<?php echo $system->urlSystem() . "/dist/img/checksupport/" . $fotosDatos->foto ?>" target="_blank">Imagen</a>

                                            </li>

                            <?php
                                        }
                                        echo "</ul>";
                                    }



                                    echo '</td>
                                </tr>
                                ';
                                    $pr = 1;
                                }
                            }
                            ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>


</div>


<script>
 
function guardarComentario($estado){

            var datastring = $('#form-comentarioCheck').serialize();
             $.ajax({
                type: "POST",
                url: "build/model/model-faena.php",
                data: datastring+"&estado="+$estado,
                success: function(data) {
                     if (data == 0) {
                      
                        $("#modalLargeBody").load("pages/support/vista_tabla_check.php",{idf:<?php echo $id_faena ?>});
                    
                    } else {
                     }
                },
                error: function(ss) {
                    alert(JSON.stringify(ss, null, 4));

                }

            });

}
    $(document).ready(function() {

    

        $("#btnAprobar").click(function() {
             guardarComentario(1)         
        });

        $("#btnRechazar").click(function() {
            guardarComentario(0)         

        });
        


    });
</script>