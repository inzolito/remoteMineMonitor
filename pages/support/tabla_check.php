<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();
$id_faena = $_POST["idf"];

$datosCheckFaena = $fenaCl->datosCheck($id_faena);


?>


<form action="" id="form-tablaCheck" method="post" name="form-tablaCheck" enctype="multipart/form-data">

    <div class="card ">´

        <div class="card-header">

            <h3 class="card-title">

                <span id="span-guardando">
                    <!--<i class="fa fa-spinner fa-spin fa-1x fa-fw"></i> Guardando -->
                </span>

                <span id="span-guardado-fecha">

                </span>

            </h3>
            <div class="card-tools btn-group">



                <button type="submit" id="btn-guardar" class="form-control btn-warning ml-1 " name="btn-guardar">
                    guardar
                </button>
                <button type="button" id="btn-finalizar" class="form-control btn-success ml-1" name="btn-finalizar">
                    Finalizar
                </button>
                <input type="hidden" id="accion" name="accion" value="guardarCheck">
                <input type="hidden" id="idf" name="idf" value="<?php echo $id_faena ?>">

            </div>

        </div>

        <div class="card-body">

            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th style="width: 10px">#</th>
                        <th style="width:10%">Proceso</th>
                        <th style="width:40px">Subproceso</th>
                        <th>Estado </th>
                        <th>Comentario</th>
                        <th style="width:20%">Upload</th>
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
                              <td><select id="estado_' . $subpDatos["id_subproceso"] . '" name ="estado_' . $subpDatos["id_subproceso"] . '" class="form-control"  enctype="multipart/form-data">
                                    ';

                            if ($subpDatos["estado"] == 3) {
                                echo '<option value=3 selected >-</option>';
                            } else {
                                echo '<option value=3 >-</option>';
                            }



                            if ($subpDatos["estado"] == 1) {
                                echo '<option value=1 selected >Ok</option>';
                            } else {
                                echo '<option value=1 >Ok</option>';
                            }

                            if ($subpDatos["estado"] == 0) {
                                echo '<option value=0 selected >Bad</option>';
                            } else {
                                echo '<option value=0 >Bad</option>';
                            }
                            echo '</select>
                              </td>
                              <td>
                              <textarea name="comentario_' . $subpDatos["id_subproceso"] . '" id="comentario_' . $subpDatos["id_subproceso"] . '" class="form-control" rows="2" placeholder="comentarios">' . $subpDatos["comentario"] . '</textarea>
                              </td>
                              
                              <td>
                              <div class="custom-file">
                              <input type="file" onchange="uploadImage(this.id)" class="custom-file-input" name="img_' . $subpDatos["id_subpchf"] . '[]" id="img_' . $subpDatos["id_subpchf"] . '" multiple>
                              <label class="custom-file-label" for="customFile">Selecciona imagen</label>
                              </div>
                              </td>
                              <td>
                              
                              

                                ';
                            $fotos = $fenaCl->fotoSubprocesoCheck($subpDatos["id_subpchf"]);

                            if (is_object($fotos)) {
                                echo "<ul>";
                                while ($fotosDatos = $fotos->fetch_object()) {
                    ?> <li>
                                        <a href="<?php echo $system->urlSystem() . "/dist/img/checksupport/" . $fotosDatos->foto ?>" target="_blank">Imagen</a> -
                                        <a href="#" id="borrar_ <?php echo $fotosDatos->id ?>" onclick="deleteImage('<?php echo $fotosDatos->id ?>','<?php echo $fotosDatos->foto ?>')"><i class='fa-solid fa-trash'></i></a>
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



<script>
    function deleteImage(idImg, foto) {
        var form_data = new FormData();
        form_data.append('id', idImg);
        form_data.append('foto', foto);
        form_data.append('accion', 'borrarImagen');
        $.ajax({
            url: "build/model/model-faena.php",
            dataType: 'text',
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            type: 'post',
            success: function(response) {
                if (response == 0) {
                    // esto pertenece al formulario de faena.php
                    $("#form-iniciar-check").submit();
                } else {
                    alert(response)
                }
            },
        });
    }


    function autom() {
        $("#form-tablaCheck").submit()
    }

    function uploadImage(idImg) {

        var form_data = new FormData();
        var ins = document.getElementById(idImg).files.length;
        for (var x = 0; x < ins; x++) {
            form_data.append("documentfiles[]", document.getElementById(idImg).files[x]);
        }
        if (ins > 0) {
            var imgArray = idImg.split("_");

            form_data.append('idschf', imgArray[1]);

            form_data.append('accion', 'subirImgSub');
            $.ajax({
                url: "build/model/model-faena.php",
                dataType: 'text',
                cache: false,
                contentType: false,
                processData: false,
                data: form_data,
                type: 'post',
                success: function(response) {
                    // esto pertenece al formulario de faena.php
                    $("#form-iniciar-check").submit();
                },
            });
        } else {
            alert("Please choose the file");
        }
    }




    $(document).ready(function() {
        $("#span-guardado-fecha").show(500)
        // $("#span-guardando").hide()
        $("#form-tablaCheck").submit(function(event) {
            event.preventDefault();
            //$("#span-guardado-fecha").hide()
            // $("#span-guardando").show(300)
            // .---------------
            var datastring = $('#form-tablaCheck').serialize();
            $.ajax({
                type: "POST",
                url: "build/model/model-faena.php",
                data: datastring,
                success: function(data) {


                    if (data == 0) {
                        var dt = new Date();
                        var time = dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds();

                        setTimeout(function() {

                            $("#span-guardado-fecha").html("<i class='fa-solid fa-floppy-disk'></i> Guardado a las " + time)
                            // $("#span-guardando").hide(200)
                            //$("#span-guardado-fecha").show(500)

                        }, 2000)

                    } else {
                        $("#span-guardado-fecha").html("Ups, algo ocurrió. cod error : " + data)

                       // $("#span-guardado-fecha").html("Ups, algo ocurrió. Último guardado a las : " + time)

                    }
                },
                error: function(ss) {
                    alert(JSON.stringify(ss, null, 4));

                }

            });

        });


        $("#btn-finalizar").click(function(event) {

            $.ajax({
                type: "POST",
                url: "build/model/model-faena.php",
                data: "accion=finalizarCheck&idCheckFaena=<?php echo $datosCheckFaena->id ?>",
                success: function(data) {

                    if (data == 1) {
                        Swal.fire({
                            title: 'No puedes finalizar el check aun.',
                            text: "te quedan cosas que revisar",
                            icon: 'warning',
                            showCancelButton: true,
                            textCancelButton: "Okay",
                            showConfirmButton: false

                        })
                    } else {

                        if (data == 0) {


                            Swal.fire({
                                title: "¿Estás seguro de que deseas finalizar el check?",
                                text: "",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonText: 'Sí',
                                cancelButtonText: 'Volver',
                            }).then((result) => {
                                if (result.value) {
                                    Swal.fire(
                                        'Check Realizado',
                                        '',
                                        'success'
                                    )
                                    lista_faenas()
                                }
                            })


                        } else {

                        }

                    }

                },
                error: function(ss) {
                    alert(JSON.stringify(ss, null, 4));

                }

            });

        });

        setInterval("autom()", 30000);




    });
</script>