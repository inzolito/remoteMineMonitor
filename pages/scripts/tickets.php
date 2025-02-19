<?php
// Ruta del archivo CSV
$csv_file = "../../data/tickets.csv";

$last_status = [];
$status_counts = [];
$closed_count = 0;
$closed_case_numbers = [];
$rows = [];
$show_alert = false;


// Leer el archivo CSV
if (file_exists($csv_file)) {
    if (($handle = fopen($csv_file, 'r')) !== false) {
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 10) {
                continue;
            }
            $case_number = $row[1];
            $case_id = $row[0];
            $status = $row[2];
            $account_name = $row[3];

            $created_at = $row[8] ?? '';
            if (!empty($created_at)) {
                $formatted_date = date('d-m-Y', strtotime($created_at));
                $row[8] = $formatted_date;
                $year = date('Y', strtotime($created_at));

                if ($year < 2024) {
                    continue;
                }
            }

            if (!isset($last_status[$case_number])) {
                $last_status[$case_number] = [
                    'case_id' => $case_id,
                    'data' => array_slice($row, 1),
                ];

                if ($status === 'Closed' && !in_array($case_number, $closed_case_numbers)) {
                    $closed_case_numbers[] = $case_number;
                    $closed_count++;
                } else {
                    if ($status !== 'Closed') {
                        if (isset($status_counts[$status])) {
                            $status_counts[$status]++;
                        } else {
                            $status_counts[$status] = 1;
                        }
                    }
                }
            }

            $rows[] = $row;
        }

        fclose($handle);
        $status_counts['Closed'] = $closed_count;

        $owner_names = [];
        foreach ($rows as $row) {
            $owner_name = $row[3];
            if ($owner_name !== 'N/A' && !in_array($owner_name, $owner_names)) {
                $owner_names[] = $owner_name;
            }
        }

        foreach ($rows as $row) {
            $owner_name = $row[3];
            $status = $row[2];

            if (strcasecmp($owner_name, 'South American Support Q') === 0 && $status !== 'Closed') {
                $show_alert = true;
                break;
            }
        }
        echo '<script>';
        echo 'var ownerNames = ' . json_encode($owner_names) . ';';
        echo '</script>';

        $records_to_display = array_filter($last_status, function ($row) {
            return $row['data'][1] !== 'Closed';
        });
    }
} else {
    echo "El archivo $csv_file no se encuentra.";
}

//print_r($rows);  t k 
echo '<script>';
echo 'var ownerNames = ' . json_encode($owner_names) . ';';
echo '</script>';

if ($show_alert) {
    echo '<div id="alert-box" style="
        display: flex; 
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background-color: #004F67; 
        color: white; 
        z-index: 9999; 
        justify-content: center; 
        align-items: center; 
        font-size: 24px; 
        font-weight: bold; 
        text-align: center;
        animation: blink 1s infinite;">
        <div style="
            position: relative; 
            padding: 40px; 
            background-color:#F60000; 
            border-radius: 10px; 
            box-shadow: 0px 0px 20px #004F67;">
            <button onclick="closeAlert()" style="
                position: absolute; 
                top: 10px; 
                right: 10px; 
                background: transparent; 
                border: none; 
                color: white; 
                font-size: 20px; 
                cursor: pointer;">
                ✖
            </button>
            🚨 <br><br>
            
            <span>¡Se detectó un ticket de <b>South American Support!</b></span>
        </div>
    </div>';
}
?>

<style>
    @keyframes blink {
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }

        100% {
            opacity: 1;
        }
    }
</style>


<script>
    function closeAlert() {
        var alertBox = document.getElementById("alert-box");
        alertBox.style.display = "none";
    }
</script>

<!-- Incluir DataTables y Chart.js -->
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



<style>
    body {
        font-family: 'Arial', sans-serif;

        background-color: #f4f6f9;
    }

    header {
        background-color: #004F67;
        color: white;
        font-size: 24px;
    }



    h3 {
        color: #004F67;
        font-size: 24px;
        margin-bottom: 20px;
    }

    .status-section {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 40px;
        margin-top: 50px;
    }

    .status-summary {
        font-weight: bold;
        font-size: 16px;
        color: #333;
        margin-left: 20px;
        margin-top: 30px;
        text-align: center;
        display: block;

    }

    .status-summary div {
        margin-bottom: 20px;
    }



    .table-container {
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin: 0 auto;
        width: 100%;
        max-width: 1200px;
        text-align: center;
        min-height: 300px;
        max-height: 500px;
        overflow-y: auto;
    }

    table {
        width: 100%;
        margin: 0 auto;
        text-align: center !important;
    }

    th,
    td {
        text-align: center !important;
        padding: 10px;
    }

    th {
        background-color: #004F67;
        color: white;
        text-align: center !important;
    }

    .small-chart {
        width: 100%;
        max-width: 1000px;
        height: center;
    }

    .chart-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
    }

    .filter-info {
        font-size: 16px;
        margin-top: 20px;
        color: #333;
    }

    .filter-input {
        width: 100%;
        padding: 5px 8px;
        margin-top: 5px;
        box-sizing: border-box;
        height: 30px;
    }

    .select2-container {
        width: 100% !important;
        max-width: 300px;
        display: block;
    }

    .select2-selection__choice {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 90%;
    }

    .select2-selection__choice__remove {
        float: right;
        margin-left: 5px;
    }

    .select2-selection__rendered {
        max-height: 50px;
        overflow-y: auto;
    }

    th {
        background-color: #004F67;
        color: white;
        text-align: center !important;
        font-size: 14px;
        padding: 8px;
    }

    #filter_status {
        width: 300px;
    }

    #filter_account {

        height: 30px;

    }
</style>
</head>
<h1>
    <center>
        <img style="width: 2%;" src="dist/img/system/logohxg.jpg" alt="User Avatar">
        Tickets Soporte Remoto
        <img style="width: 2%;" src="dist/img/system/logohxg.jpg" alt="User Avatar">
    </center>
</h1>
<br>
<br>


<div class="row">
    <div class="col-md-4">
        <div class="row">
            <div class="col-md-12">
                <h3>
                    <center>Tickets por Estado</center>
                </h3>
                <div class="table-container">
                    <canvas id="statusChart"></canvas>
                </div>

                <div class="col-md-12 chart-item status-summary">
                    <div><strong>Escalate PD:</strong> <span id="escalate_pd"> </span></div>
                    <div><strong>Escalate GT:</strong> <span id="escalate_gt"> </span></div>
                    <div><strong>Seeking Customer Clarification:</strong> <span id="seeking_customer_clarification"> </span></div>
                    <div><strong>Working:</strong> <span id="working"> </span></div>
                    <div><strong>Assigned:</strong> <span id="assigned"> </span></div>
                </div>
            </div>
        </div>
    </div>


    <div class="col-md-8">
        <div class="card">
            <div class="card-body" style="padding: 50px">
                <table id="ticketsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date/Time Opened
                                <select id="filter_date" multiple="multiple" class="filter-input">
                                    <option value="">Todos</option>
                                    <option value="01-2024">01-2024</option>
                                    <option value="02-2024">02-2024</option>
                                    <option value="03-2024">03-2024</option>
                                    <option value="04-2024">04-2024</option>
                                    <option value="05-2024">05-2024</option>
                                    <option value="06-2024">06-2024</option>
                                    <option value="07-2024">07-2024</option>
                                    <option value="08-2024">08-2024</option>
                                    <option value="09-2024">09-2024</option>
                                    <option value="10-2024">10-2024</option>
                                    <option value="11-2024">11-2024</option>
                                    <option value="12-2024">12-2024</option>
                                    <option value="01-2025">01-2025</option>
                                </select>

                            </th>

                            <th>Casenumber <input type="text" class="filter-input" placeholder="Filtrar..." id="filter_case"></th>
                            <th>Status
                                <select id="filter_status" multiple="multiple" class="filter-input">

                                    <option value="Working">Working</option>
                                    <option value="Seeking Customer Clarification">Seeking Customer Clarification</option>
                                    <option value="Assigned">Assigned</option>
                                    <option value="Resolved">Resolved</option>
                                    <option value="Escalate PD">Escalate PD</option>
                                    <option value="Escalate GT">Escalate GT</option>
                                </select>

                            </th>
                            <th>Ownername
                                <select id="filter_account" class="filter-input">
                                    <option value=0>Todos</option>
                                    <!-- Opciones de filtro -->
                                </select>
                            </th>
                            <th>AccountName <input type="text" class="filter-input" placeholder="Filtrar..." id="filter_case"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        $sw_tabla_datos=0;
                        foreach ($records_to_display as $case_number => $record) {

                            if ($record['data'][3] <> "N/A") {
                                $sw_tabla_datos=1;
                            }else{

                                if($record['data'][2] == "South American Support Q"){
                                    $sw_tabla_datos=1;
                                }else{
                                    $sw_tabla_datos=0;
                                }
                            }

                            if($sw_tabla_datos==1){
                                
                                echo "<tr>";
                                echo "<td>{$record['data'][7]}</td>";
                                echo "<td><a href='https://usa1.lightning.force.com/lightning/r/Case/{$record['case_id']}/view' target='_blank'>{$case_number}</a></td>";
                                echo "<td>{$record['data'][1]}</td>";
                                echo "<td>{$record['data'][2]}</td>";
                                echo "<td>{$record['data'][3]}</td>";
                                echo "</tr>";
                                $sw_tabla_datos=0;
                            }



                        }
                        ?>
                    </tbody>
                </table>
                <div class="filter-info">
                    <p><strong>Mostrando:</strong> <span id="rowsCount"></span> registros.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo "<script>var statusCounts = " . json_encode($status_counts) . ";</script>"; ?>
<script>
    $(document).ready(function() {
        var filterAccount = $('#filter_account');
        ownerNames.forEach(function(owner) {
            filterAccount.append(new Option(owner, owner));
        });

        $('#filter_status').select2({
            placeholder: "Todos",
            allowClear: false
        });

        var table = $('#ticketsTable').DataTable({
            paging: true,
            pageLength: 10,
            lengthMenu: [10, 15, 20],
            searching: true,
            columnDefs: [{
                type: 'date',
                targets: 4
            }],
            dom: '<"row align-items-center"<"col-md-6 d-flex"lB><"col-md-6"f>>rtip',
            buttons: [{
                extend: 'excelHtml5',
                text: 'Exportar a Excel',
                title: 'ticketsTable',
                className: 'btn btn-default ml-3'
            }]
        });

        // Filtros para cada columna
        $('#filter_date').select2({
            placeholder: "Todos",
            allowClear: false
        });


        $('#filter_case').on('keyup', function() {
            table.column(1).search(this.value).draw();
            updateChart();
        });

        $('#filter_status').on('change', function() {
            var selectedStatuses = $(this).val();
            if (selectedStatuses && selectedStatuses.length > 0) {
                var filterValue = selectedStatuses.join('|');
                table.column(2).search(filterValue, true, false).draw();
            } else {
                table.column(2).search('').draw();
            }
            updateChart();
        });

        $('#filter_account').on('change', function() {

            valChange = ""
            if ($('#filter_account').val() != 0) {
                valChange = $('#filter_account').val()
            }

            table.column(3).search(valChange).draw();
            updateChart();
        });

        // Función para actualizar el gráfico
        function updateChart() {
            var filteredData = table.rows({
                filter: 'applied'
            }).data().toArray();
            var filteredStatusCounts = {};

            // Contar la cantidad de tickets por estado
            filteredData.forEach(function(row) {
                var status = row[2];
                if (status && status !== 'Closed') {
                    filteredStatusCounts[status] = (filteredStatusCounts[status] || 0) + 1;
                }
            });

            if ($('#filter_status').val().length === 0 && $("#filter_account").val() == 0) {
                filteredStatusCounts = Object.keys(statusCounts)
                    .filter(function(status) {
                        return status !== 'Closed';
                    })
                    .reduce(function(result, status) {
                        result[status] = statusCounts[status];
                        return result;
                    }, {});
            }

            var updatedData = {
                labels: Object.keys(filteredStatusCounts),
                datasets: [{
                    label: 'Tickets',
                    data: Object.values(filteredStatusCounts),
                    backgroundColor: ['#FCC003', '#B3DF60', '#7EC9D5', '#FF538A', '#06A59A'],
                    borderColor: '#000000',
                    borderWidth: 2
                }]
            };

            // Actualizar el gráfico con los datos filtrados
            statusChart.data = updatedData;
            statusChart.update();
            updateStatusSummary(filteredStatusCounts);
            $('#rowsCount').text(filteredData.length);
        }

        // Función para actualizar el resumen de estados
        function updateStatusSummary(filteredStatusCounts) {
            var statuses = ['Escalate PD', 'Escalate GT', 'Seeking Customer Clarification', 'Working', 'Assigned'];
            statuses.forEach(function(status) {
                var count = filteredStatusCounts[status] || 0;
                $("#" + status.replace(/\s+/g, '_').toLowerCase()).text(count + ' tickets');
            });

            //alert(JSON.stringify(filteredStatusCounts));

        }

        // Inicializar gráfico


        var ctx = document.getElementById('statusChart').getContext('2d');
        var statusChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(statusCounts), // Inicializar con todos los estados
                datasets: [{
                    label: 'Cantidad de Tickets por Estado',
                    data: Object.values(statusCounts),
                    backgroundColor: ['#FCC003', '#B3DF60', '#7EC9D5', '#FF538A', '#06A59A'],
                    borderColor: '#000000',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        ticks: {
                            stepSize: 1,
                            beginAtZero: true,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    }
                }
            }
        });

        updateChart();
    });
</script>