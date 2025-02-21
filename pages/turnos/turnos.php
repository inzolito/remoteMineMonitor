<?php
// Ruta del archivo CSV
$csv_file = "../../../globalData/tickets.csv";
?>

<style>
    #ticketsTableMv th,
    #ticketsTableMv td,
    #ticketsTableCodelco th,
    #ticketsTableCodelco td,
    #ticketsTableAmsa th,
    #ticketsTableAmsa td {
        text-align: center;
        vertical-align: middle;
    }

    #ticketsTableMv thead,
    #ticketsTableCodelco thead,
    #ticketsTableAmsa thead {
        background-color: #004F67;
        color: white;
    }

    #ticketsTableMv th,
    #ticketsTableCodelco th,
    #ticketsTableAmsa th {
        text-align: center;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f9f9f9;
    }

    .table-hover tbody tr:hover {
        background-color: #f1f1f1;
    }

    .table-responsive {
        margin-bottom: 20px;
    }

    .dt-buttons {
        margin-bottom: 10px;
    }

    .dataTables_filter {
        display: none;
    }
</style>

<!-- Incluir DataTables y otros scripts necesarios -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

<h1>
    <center>
        <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
        Tickets Turno - Codelco
        <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
    </center>
</h1>
<br><br>

<center>
    <label for="dateFilter">Seleccionar rango de fechas:</label>
</center>

<div class="row">
    <div class="col-md-4"></div>

    <div class="col-md-3">
        <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <i class="far fa-calendar-alt"></i>
                    </span>
                </div>
                <input type="text" class="form-control float-right" id="filtro">
            </div>
        </div>

    </div>

    <div class="col-md-1">
        <button class="form-control" id="applyDateFilter">Aplicar filtro</button>
    </div>


</div>

<div id="table-container"></div>


<script>
    function cargadetalle(sd, ed) {
        $("#table-container").load(
            "pages/turnos/turnos_detalle.php?sd=" + sd + "&ed=" + ed
        );
    }

    $(document).ready(function() {
        $('#filtro').daterangepicker({
            locale: {
                format: 'DD-MM-YYYY'
            }

        }, function(start, end) {
            window.startDate = start.format('YYYY-MM-DD');
            window.endDate = end.format('YYYY-MM-DD');


            cargadetalle(window.startDate, window.endDate);
        });



        $("#applyShiftFilter").click(function() {
            cargadetalle(window.startDate, window.endDate);
        });

        cargadetalle(0, 0);
    });
</script>