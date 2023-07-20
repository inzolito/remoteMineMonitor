<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();

?>


 


<div class="card card-info ">

    <div class="card-header">
        <h3 class="card-title">
            <?php echo " Este es un resumen del soporte hecho entre el día <b> " . $_POST['di'] . " </b>y el <b>" . $_POST['df'] . "</b>";  ?>
        </h3>
    </div>

    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th scope="col" colspan="3"></th>

                    <?php
                    $faenasSql = $conn->query("select *  from faenas where estado=1 order by faena");
                    while ($faenasDatos = $faenasSql->fetch_object()) {
                        /*
                        {
                            $filasCheckFaenasSql = $conn->query("select id_proceso,id_subproceso, subproceso, id_check_faena,estado,scf.comentario,fecha 
                            FROM subprocesos as s left join subproceso_check_faena as scf on(s.id=scf.id_subproceso) 
                            where id_check_faena=".$datosCheckFaena->id." and   id_proceso=". $procesosDatos["id"] . " 
                            order by id_subproceso asc;
                            ");
                            $objetCheckFaenas;
                            $x=0;
                            while($filasCheckFaenasDatos=$filasCheckFaenasSql->fetch_object())
                            {
                                $objetCheckFaenas[$filasCheckFaenasDatos->id_subproceso][$faenasDatos->id_faena]=$filasCheckFaenasDatos[$x];
                                $x++;
                                echo "<td>".$filasCheckFaenasDatos[$x]."</td>";
                            }
                        }
                            */
                        
                        $datFechaFaena = $fenaCl->datosCheck($faenasDatos->id, $_POST['di'], $_POST['df']);
                        $idFaenaVer = 0;
                        if (is_object($datFechaFaena)) {

                            $date = date_create($datFechaFaena->fecha);
                            $fechTh = date_format($date, 'd-m-Y');
                            $idFaenaVer = $datFechaFaena->id_faena;
                        } else {
                            $fechTh = "-";
                        }


                        echo "<th scope='col' colspan=2>" . $faenasDatos->faena . "<br>
                                <a href='#'   onclick ='verFaena(" . $idFaenaVer . ")' >" . $fechTh . "</a> 
                              </th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody>

                <tr style="background-color: #eee;">

                    <th>#</th>
                    <th>Proceso</th>
                    <th>Subproceso</th>
                    <?php
                    $faenasSql = $conn->query("select *  from faenas where estado=1 order by faena");

                    while ($faenasDatos = $faenasSql->fetch_object()) {
                        echo " <th>Estado </th>            <th>Comentario</th> ";
                    }
                    ?>
                </tr>



                <?php
                $procesosSql = $conn->query("select * from procesos order by id asc");
                while ($procesosDatos = $procesosSql->fetch_assoc()) {

                    $subpSql = $conn->query("select * from subprocesos where id_proceso=" . $procesosDatos["id"] . " order by id asc");
                    $totalSubproceso = $subpSql->num_rows;
                    echo '
                        <tr>
                            <td style="background-color:#f3f3f3" rowspan=' . $totalSubproceso . '>' . $procesosDatos["id"] . '</td>
                            <td  style="background-color:#f3f3f3"   rowspan=' . $totalSubproceso . '>' . $procesosDatos["proceso"] . '</td>
                        
                        ';
                    $larow = 0;
                    while ($subpDatos = $subpSql->fetch_assoc()) {

                        if ($larow == 1) {
                            echo "<tr>";
                        }
                        $larow = 1;
                        echo '<td style="background-color: #f7f7f7;">' . $subpDatos["subproceso"] . '</td>';

                        // por faena
                        $faenasSql = $conn->query("select *  from faenas where estado=1 order by faena");

                        while ($faenasDatos = $faenasSql->fetch_object()) {
                            $datosCheckFaena = $fenaCl->datosCheck($faenasDatos->id, $_POST['di'], $_POST['df']);
                            if (!is_object($datosCheckFaena)) {

                                echo "<td>-</td>  <td>-</td>";
                            } else {

                                $datosSubCheck = $fenaCl->datoSubprocesoCheck($datosCheckFaena->id, $subpDatos["id"]);
                                $est = "";
                                if ($datosSubCheck->estado == 0) $est = '<i class="fa-solid fa-x " style="color:red"></i>';
                                if ($datosSubCheck->estado == 1) $est = '<i class="fa-solid fa-check " style="color:green"></i>';
                                if ($datosSubCheck->estado == 3) $est = '-';
                                //imagenes
                                $fotoVar="";
                                

                                $fotos=$fenaCl->fotoSubprocesoCheck($datosSubCheck->id);
                                if(is_object($fotos))
                                {
                                    $fotoVar.="<ul>";
                                    while($fotosDatos=$fotos->fetch_object())
                                    {
                                        $fotoVar.='<li><a href="#" onclick="verImagen(\''.$fotosDatos->foto.'\')"  >Imagen</a></li>';

                                     
                                    }
                                    $fotoVar.="</ul>";
                                }




                                echo "<td> " . $est . "</td>";
                                echo "<td> " . $datosSubCheck->comentario . $fotoVar."</td>";
                            }
                        }



                        echo '</tr>';
                    }
                }

                ?>
            </tbody>
        </table>

    </div>
     

</div>

 
 


<script>
    
     function verFaena(idFaenaVer)
     {
         $("body").append("<span id='btnModalFaena' data-toggle='modal' data-target='#modalLarge'>  </span>");
        $("#btnModalFaena").click();
        $("#btnModalFaena").remove();
        $("#modalLargeBody").load("pages/support/vista_tabla_check.php",{idf:idFaenaVer});
     }


     function verImagen(img)
     {
         $("body").append("<span id='btnModalImg' data-toggle='modal' data-target='#modalStandard'>  </span>");
        $("#btnModalImg").click();
        $("#btnModalImg").remove();
        $("#modalStandardBody").html("<img src='dist/img/checksupport/"+img+"' class='img-fluid' >");

      }

    $(document).ready(function() {

    });

</script>