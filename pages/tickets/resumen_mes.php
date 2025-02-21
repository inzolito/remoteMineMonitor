<?php

$csv_file = "../../../globalData/tickets.csv";

$account_numbers = [];

if (($handle = fopen($csv_file, "r")) !== FALSE) {

    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $case_id = $row[0]; // "Case ID"
        $case_number = $row[1]; // "Case Number"
        $status = $row[2]; // "Status"  
        $owner_name = $row[3]; // "Owner Name"  
        $account_name = $row[4]; // "Account Name"
        $subject = $row[5]; // "Subject"
        $case_resolution = $row[7]; // "Case Resolution"
        $create_date = $row[8]; // "Create Date"
        $closed_date = $row[9]; // "Closed Date"
        $product_name = $row[10]; // "Product Name"
        $product_family = $row[11]; // "Product Family"
        $modified_account_name = $account_name;
        
        if (strtotime($create_date) >= strtotime("2025-01-01") && strtotime($create_date) < strtotime("2025-02-01")) {

            if (!isset($csv_data[$case_number])) {

                if (preg_match('/sumarización|sumarizacion|sumarizar|rezumarizar/i', $subject)) { // Modificar 'product_name' si 'subject' contiene "sumarización"
                    $product_name = 'Sumarización';
                }

                if (preg_match('/importador|importando/i', $subject)) { // Modificar 'product_name' si 'subject' contiene "importador"
                    $product_name = 'Importador';
                }

                if (preg_match('/servidores|servidor/i', $subject)) { // Modificar 'product_name' si 'subject' contiene "servidor"
                    $product_name = 'Servidor';
                }

                if (preg_match('/Centinela Mine/i', $modified_account_name)) {
                    $modified_account_name = 'AMSA | Centinela';
                }

                if (preg_match('/Corporacion Nacional del Cobre Mina Sur/i',$modified_account_name)) {
                    $modified_account_name = 'CODELCO | División Mina Sur';
                }

                if (preg_match('/Radomiro Tomic Mine/i', $modified_account_name)) {
                    $modified_account_name = 'CODELCO | División Radomiro Tomic';
                }

                if (preg_match('/CODELCO | Division Mina Ministro Hales|Mina Ministro Hales/i', $modified_account_name)) {
                    $modified_account_name = 'CODELCO | División Ministro Hales';
                }

                if (preg_match('/Division El Salvador | Codelco \| Division El Salvador/i', $modified_account_name)) {
                    $modified_account_name = 'CODELCO | División El Salvador';
                }

                if (preg_match('/Zaldivar Mine|Compania Minera Zaldivar SPA/i', $modified_account_name)) {
                    $modified_account_name = 'AMSA | Zaldivar';
                }

                if (preg_match('/Minera Antucoya/i', $modified_account_name)) {
                    $modified_account_name = 'AMSA | Antucoya';
                }


                // Agregar cuenta a la lista de cuentas
                if (!in_array($modified_account_name, $account_numbers)) {
                    $account_numbers[] = $modified_account_name;
                }
            }
        }
    }
    fclose($handle);
}
?>



<!-- Incluir Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>


<!-- Incluir DataTables y otros scripts necesarios -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>


<h1>
    <center>
        <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
        Tickets Soporte Remoto
        <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
    </center>
</h1>
<br><br>


<!-- Filtro por Account Number -->



<div class="row">
    <div class="col-md-3"></div>
    <div class="col-md-3">

        <div class="form-group"><label for="filtro">Rango de fecha</label><br>
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


    <div class="col-md-3">
        <label for="accountFilter">Filtrar por Faena:</label>
        <select id="accountFilter" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($account_numbers as $account): ?>
                <option value="<?= htmlspecialchars($account) ?>"><?= htmlspecialchars($account) ?></option>
            <?php endforeach; ?>
        </select>

    </div>
    <div class="col-md-3"></div>


</div>



<div class="row">
    <div class="col-md-4"></div>

    <div class="col-md-4"></div>
</div>






<div id="div_cont_resumen">


</div>


<script>
    function carga_modulo_resumen_mes(fechaInicio, fechaFin) {

        $("#div_cont_resumen").load('pages/tickets/resumen_mes_detalle.php?startDate=' + fechaInicio + '&endDate=' + fechaFin + '&filtroFaena=')
    }
</script>


<script>
    $(document).ready(function() {
        // Inicializar el Date Range Picker en el input #filtro

        $('#filtro').daterangepicker({
            locale: {
                format: 'DD-MM-YYYY'
            }
            //,startDate: moment.utc().subtract(1, 'days').local(),  // Ayer
            //endDate: moment.utc().local()  // Hoy
        }, function(start, end) {
            window.startDate = start.format('YYYY-MM-DD');
            window.endDate = end.format('YYYY-MM-DD');
            carga_modulo_resumen_mes(window.startDate, window.endDate)
        });

        $('#accountFilter').on('change', function() {
            if (window.startDate && window.endDate) {
                // Llama a la función para cargar el módulo con las fechas seleccionadas
                carga_modulo_resumen_mes(window.startDate, window.endDate);
            } else {
                // Si no hay fechas definidas, usa las fechas predeterminadas del daterangepicker
                const picker = $('#filtro').data('daterangepicker');
                const defaultStartDate = picker.startDate.format('YYYY-MM-DD');
                const defaultEndDate = picker.endDate.format('YYYY-MM-DD');
                carga_modulo_resumen_mes(defaultStartDate, defaultEndDate);
            }
        });


        carga_modulo_resumen_mes(0, 0)
    });
</script>