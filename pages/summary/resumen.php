<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");

$system = new systemClass();
$fenaCl = new faena();

$system->validarSesion();
$conn = $system->conectaDB();



// PHP program to print default first
// day of current week

// l will display the name of the day

// d, m, Y will display the day, month
// and year respectively 
$firstday = date('Y-m-d', strtotime("this week"));
$lastday = date("Y-m-d", strtotime($firstday . "+ 6 days"));



$primerDiaTurno = date("Y-m-d");
//--------------------------------------------------------------

$todayNumber = date("N");
$miercoles = strtotime("Wednesday this week");
$fecha_miercoles = date("Y-m-d", $miercoles);

//si hoy es miercoles o un dia posterior de la semana, quiere decir que este turno terminara la semana siguiente
if ($todayNumber >= 2) {
    $primerDiaTurno = $fecha_miercoles;
    $ultimoDiaTurno = date("Y-m-d", strtotime($fecha_miercoles . "+ 6 days"));
}
//si estamos entre lunes y martes quiere decir que el turno comenzó la semana anterior
if ($todayNumber < 2) {
    $ultimoDiaTurno = $fecha_miercoles;
    $primerDiaTurno = date("Y-m-d", strtotime($fecha_miercoles . "- 7 days"));
}

$proxDiaTurno = $primerDiaTurno;
$hoy=date("Y-m-d");
$styleTachado="";
?>


<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title">Resumen de check semanal</h3>
    </div>

    <div class="card-body" >
        <div class="row">

            <div class="col-md-7">


                <div class="row" id="divSemanaTurno">
                    <div class="row">
                    <div class="col-md-8">

                        <?php echo "El turno comenzó el dia <b>" . $system->formatoFecha($primerDiaTurno, "lectura") . "</b> y finaliza el día <b>" . $system->formatoFecha($ultimoDiaTurno, "lectura") . "</b>" ?>


                        <div class="row mt-4">

                            <div class="col-md-3"></div>
                            <div class="col-md-9">

                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                    <?php
                                     
                                    for ($x = 1; $x <= 5; $x++) {
                                        if($proxDiaTurno<$hoy)
                                        {
                                            $styleTachado="text-decoration: line-through";
                                        }else{
                                            $styleTachado="";

                                        }
                                    ?>
                                        <label class="btn btn-default text-center"  style="width: 60px; background-color:#FFF !important;<?php echo $styleTachado ?>">

                                            <?php echo  date("D", strtotime($proxDiaTurno)) . " <br> " . date("d", strtotime($proxDiaTurno));
                                            $proxDiaTurno = date("Y-m-d", strtotime($proxDiaTurno . "+ 1 days"));
                                            ?>
                                        </label>
                                    <?php
                                    }
                                    ?>


                                </div>
                            </div>


                        </div>
                        <div class="row">

                            <div class="col-md-5">

                                <div class="btn-group btn-group-toggle" data-toggle="buttons">

                                    <?php

                                    for ($x = 1; $x <= 2; $x++) {
                                        if($proxDiaTurno<$hoy)
                                        {
                                            $styleTachado="text-decoration: line-through";
                                        }else{
                                            $styleTachado="";

                                        }
                                    ?>
                                        <label class="btn btn-default text-center"  style="width: 60px; background-color:#FFF !important;<?php echo $styleTachado ?>">

                                            <?php echo  date("D", strtotime($proxDiaTurno)) . " <br> " . date("d", strtotime($proxDiaTurno));
                                            $proxDiaTurno = date("Y-m-d", strtotime($proxDiaTurno . "+ 1 days"));
                                            ?>
                                        </label>
                                    <?php
                                    }
                                    ?>

                                </div>
                            </div>

                            <div class="col-md-6"></div>
                        </div>


                    </div>
                    <div class="col-md-4"></div>


                    </div>

                </div>

            </div>


            <div class="col-md-5">
                <p>Selecciona una fecha y se cargara automáticamente el turno correspondiente a ese día.</p>
                <input type="date" id="dateSemanaInicio" name="dateSemanaInicio" class="form-control " value="<?php echo $primerDiaTurno  ?>">
                <input type="date" id="dateSemanaFin" name="dateSemanaFin" style="display: none;" class="form-control " value="<?php echo $ultimoDiaTurno ?>">

                <button type="button" class="form-control btn-success" id="btn-cargar" name="btn-cargar"> Cargar </button>

            </div>
        </div>

    </div>

</div>

<div class="overlay overlayPos">
<img class="fa-spin" src="dist/img/system/logohxg.jpg" alt="AdminLTELogo" height="60" width="60">
</div>

<div id="content-table-resumen">



</div>

<style>
.overlayPos {
    position: fixed;
    top: 70%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999;
}

</style>


<script>
    function cargaResumen() {
       $("#content-table-resumen").fadeOut(200);
        //$("#loadingIcon").show(200);
       
        $('.overlay').fadeIn();
        $("#content-table-resumen").load("pages/summary/tabla_resumen.php", {
            di: $("#dateSemanaInicio").val(),
            df: $("#dateSemanaFin").val()
        ,},function(){
            $('.overlay').fadeOut();
            $("#content-table-resumen").fadeIn(200);

        });

    }

    function cargaSemanaTurno() {
        $("#loadingIcon").
        $("#divSemanaTurno").load("pages/summary/semanaTurno.php", {
            pdt: $("#dateSemanaInicio").val(),
            udt: $("#dateSemanaFin").val()
        })
    }


    $(document).ready(function() {
       
        cargaResumen()

        $("#btn-cargar").click(function() {

            cargaResumen();
        })


        $("#dateSemanaInicio").on("change", function() {
            var selectedDate = new Date($(this).val());
            var dayOfWeek = selectedDate.getUTCDay();
            var monday = new Date(selectedDate);
            var tuesday = new Date(selectedDate);

            //si el dia de la semana no es miercoles
            if (dayOfWeek != 3) {
                // rescata valor de lunes y se busca el miercoles, luego se suma 6 dias
                monday.setDate(selectedDate.getDate() - (dayOfWeek + 4) % 7);
                tuesday.setDate(monday.getDate() + 6);
            } else {
                tuesday.setDate(selectedDate.getDate() + 6);
            }
            $("#dateSemanaInicio").val(monday.toISOString().slice(0, 10));
            $("#dateSemanaFin").val(tuesday.toISOString().slice(0, 10));
            cargaSemanaTurno()

            /*La función se activa cada vez que se selecciona una fecha en el elemento con id "dateSemanaInicio", 
            se obtiene la fecha seleccionada en una variable selectedDate. Luego se obtiene el día de la semana de 
            la fecha seleccionada en dayOfWeek.
              Se establecen dos variables monday y tuesday para guardar las fechas lunes y martes respectivamente. 
              Si el día de la semana seleccionado no es miércoles, se establece la fecha del lunes anterior y la 
              fecha del martes siguiente, si es miércoles se establece el martes siguiente.
              Finalmente se actualiza el valor del input de inicio de la semana con el valor de monday y el valor 
              del input de fin de semana con el valor de tuesday.

              Ten en cuenta que este código esta basado en el uso de los metodos getUTCDay(), getDate() y setDate() 
              que devuelven y establecen el día del mes respectivamente, y en el uso del metodo toISOString() 
              que devuelve una representación en string de la fecha en formato ISO. 
              
              */
        });
    });
</script>