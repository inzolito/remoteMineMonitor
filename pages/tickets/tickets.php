<?php
// Ruta del archivo CSV
$csv_file = "../../../globalData/tickets.csv";
$last_status = [];
$status_counts = [];
$closed_count = 0;
$closed_case_numbers = [];
$rows = [];
$show_alert = false;
$ultimoCaseNumber = 0;

// Leer el archivo CSV
if (file_exists($csv_file)) {
    if (($handle = fopen($csv_file, 'r')) !== false) {
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 10) {
                continue;
            }
            $case_number = $row[1];
            $subject = $row[5];
            $case_id = $row[0];
            $status = $row[2];
            $account_name = $row[3];

            // $created_at = $row[8] ?? '';
            //  if (!empty($created_at)) {
            //     $formatted_date = date('d-m-Y', strtotime($created_at));
            //     $row[8] = $formatted_date;
            //     $year = date('Y', strtotime($created_at));

            //     if ($year < 2024) {
            //         continue;
            //     }
            // }ws

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
            //print_r($row);
            $ultimoCaseNumber = $row[1];
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


//-------------------------------------- Para el websocket ------------------------------------------------- 
$headers = [];
$registros = [];
$id = 0;
if (($handle = fopen($csv_file, "r")) !== FALSE) {
    // Leer la fila de cabecera y guardarla en $headers
    if (($headerRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $headers = $headerRow;
    }
    // Leer las filas de datos
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $fila = [];
        /*
        Esta sintaxis se utiliza para iterar sobre el array $headers.
        $index es la clave del elemento actual del array $headers, que en este caso es el índice numérico del encabezado.
        $header es el valor del elemento actual del array $headers, que es el nombre del encabezado.*/
        foreach ($headers as $index => $header) {
            $fila[$header] = isset($data[$index]) ? $data[$index] : '';
        }

        //Asignar un identificador único al registro:
        $fila['id'] = $id;
        //Agregar el registro al array $registros:
        $registros[] = $fila;
        //Incrementar el identificador único:
        $id++;
    }
    fclose($handle);
}
//-------------------------------------- End Para el websocket ------------------------------------------------- 


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

 
<!DOCTYPE html>
<html lang="es">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject de Tickets</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }


        th {
            background-color: #f2f2f2;
        }

        td[title] {
            position: relative;
            cursor: pointer;
        }

        td[title]:hover::after {
            content: attr(title);
            position: absolute;
            left: 50%;
            top: -30px;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px;
            border-radius: 5px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 10;
        }
    </style>
</head>

</html>

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

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>


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

    @keyframes pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 8px rgba(237, 241, 237, 0.4);
        }

        50% {
            transform: scale(1.02);
            box-shadow: 0 0 15px rgba(237, 241, 237, 0.4);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 8px rgba(237, 241, 237, 0.4);
        }
    }

    .new-row {
        color: #2E4A3B;
        font-weight: bold;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        transition: background-color 0.3s ease, transform 0.3s ease;
        animation: pulse 3s ease-in-out infinite;
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

    /* Tamaño consistente para inputs y selects */
    .filter-select,
    .filter-input {
        width: 100% !important;
        min-width: 150px;
        max-width: 100%;
        height: 38px;
        padding: 6px 12px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        text-align: center;
        /* Centrado horizontal */
    }

    /* Tamaño consistente de Select2 */
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        height: 38px !important;
        padding: 0px 12px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 4px;
        display: flex;
        align-items: center;
        /* Centrado vertical */
    }

    /* Centrar el texto del placeholder */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: normal !important;
        text-align: center;
        color: #6c757d;
        /* Placeholder color */
    }

    /* Centrar el placeholder en los múltiples */
    .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
        text-align: center;
        line-height: 38px;
    }

    /* Ajuste para el texto dentro de Select2 */
    .select2-selection__rendered {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Ajuste del ícono de flecha */
    .select2-selection__arrow {
        height: 36px !important;
        align-self: center;
    }
</style>
</head>
<h1>
    <center>
        <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
        Tickets Soporte Remoto
        <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
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
                    <div id="divPruebaTemporal">

                    </div>
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
                                </select>

                            </th>

                            <th>Casenumber
                                <input type="text" class="form-control" placeholder="Filtrar..." id="filter_case">
                            </th>

                            <th>Status
                                <select id="filter_status" multiple="multiple" class="form-control">

                                    <option value="Working">Working</option>
                                    <option value="Seeking Customer Clarification">Seeking Customer Clarification</option>
                                    <option value="Assigned">Assigned</option>
                                    <option value="Resolved">Resolved</option>
                                    <option value="Escalate PD">Escalate PD</option>
                                    <option value="Escalate GT">Escalate GT</option>
                                </select>

                            </th>

                            <th>Ownername
                                <select id="filter_account" class="form-control">
                                    <option value=0>Todos</option>


                                </select>
                            </th>
                            <th>AccountName <input type="text" class="form-control" placeholder="Filtrar..." id="filter_case"></th>
                        </tr>
                    </thead>
                    <tbody id="dataTickets">

                        <?php foreach ($registros as $fila):

                            $sw_tabla_datos = 0;
                            $newTicket = "";


                            if ($fila[$headers[4]] <> "N/A") {
                                $sw_tabla_datos = 1;
                            } else {

                                if ($fila[$headers[3]] == "South American Support Q") {
                                    $sw_tabla_datos = 1;
                                } else {
                                    $sw_tabla_datos = 0;
                                }
                            }

                            if ($fila[$headers[3]] <> "") {
                            } else {
                                $sw_tabla_datos = 0;
                            }
                            if (date('Y-m-d', strtotime($fila[$headers[8]])) === date('Y-m-d')) {
                                $newRowClass = "new-row";
                            } else {
                                $newRowClass = "";
                            }

                            if ($sw_tabla_datos == 1) {


                        ?>
                                <tr id="row-<?php echo $fila['id']; ?>" class="<?php echo $newRowClass ?>">
                                    <?php
                                    echo "<td>" . $fila[$headers[8]] . "</td>";
                                    echo "<td title='" . htmlspecialchars($fila[$headers[5]]) . "'>
                                            <a href='https://usa1.lightning.force.com/lightning/r/Case/" . $fila[$headers[0]] . "/view' target='_blank'>" . $fila[$headers[1]] . " 
                                            </a>
                                        </td>";

                                    echo "<td>" . $fila[$headers[2]] . "</td>";
                                    echo "<td>" . $fila[$headers[3]] . "</td>";
                                    echo "<td>" . $fila[$headers[4]] . "</td>";
                                    ?>
                                </tr>


                        <?php
                            }
                        endforeach;
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

        function createSelect2() {
            $('#filter_status, #filter_account').select2({
                placeholder: "Todos",
                allowClear: false
            });
        }

        function createDataTable() {
            return $('#ticketsTable').DataTable({
                paging: true,
                pageLength: 10,
                searching: true,
                order: [1, 'desc'],
                initComplete: function() {
                    this.api().columns(2).search('^(?!Closed$).*$', true, false).draw();
                }
            });
        }

        function createChart(data) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        label: 'Cantidad de Tickets por Estado',
                        data: Object.values(data),
                        backgroundColor: ['#FCC003', '#B3DF60', '#7EC9D5', '#FF538A', '#06A59A'],
                        borderColor: '#000000',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        function applyFilter(table, selector, columnIndex, chart) {
            const value = $(selector).val() || '';
            table.column(columnIndex).search(value).draw();
            updateChart(table, chart);
        }

        function updateChart(table, chart) {
            const data = {};
            table.rows({
                filter: 'applied'
            }).data().each(function(row) {
                const status = row[2];
                if (status && status !== 'Closed') {
                    data[status] = (data[status] || 0) + 1;
                }
            });
            chart.data.labels = Object.keys(data);
            chart.data.datasets[0].data = Object.values(data);
            chart.update();
        }

        createSelect2();
        const table = createDataTable();
        const chart = createChart({});

        $('#filter_status').on('change', function() {
            applyFilter(table, this, 2, chart);
        });
        $('#filter_account').on('change', function() {
            applyFilter(table, this, 3, chart);
        });

        updateChart(table, chart);
    });
</script>






















<script>
    /*
    const source = "tickets";
    const socket = new WebSocket("ws://10.169.140.99:9503");

    // Definir las columnas visibles (debe coincidir con las mostradas en PHP)
    const visibleHeaders = <?php echo json_encode($columnas_visibles); ?>;

    socket.onopen = function() {
        console.log("Conectado al WebSocket");
    };

    socket.onmessage = function(event) {
        let data = JSON.parse(event.data);
        if (data.source !== source) return;
        console.log("Mensaje recibido:", data);

        if (data.action === "update") {
            data.users.forEach(user => {
                if (user.deleted) {
                    let row = document.getElementById(`row-${user.id}`);
                    if (row) row.remove();
                } else {
                    let row = document.getElementById(`row-${user.id}`);
                    if (row) {
                        // Actualizar solo las columnas visibles
                        visibleHeaders.forEach((header, index) => {
                            row.cells[index].textContent = user[header];
                        });
                    } else {
                        let tbody = document.getElementById("dataTickets");
                        let newRow = document.createElement("tr");
                        newRow.setAttribute("id", `row-${user.id}`);
                        let innerHTML = "";
                        visibleHeaders.forEach(header => {
                            innerHTML += `<td>${user[header]}</td>`;
                        });
                        newRow.innerHTML = innerHTML;
                        tbody.appendChild(newRow);
                    }
                }
            });
        } else if (data.action === "sync") {
            let tbody = document.getElementById("dataTickets");
            tbody.innerHTML = "";
            data.users.forEach(user => {
                let newRow = document.createElement("tr");
                newRow.setAttribute("id", `row-${user.id}`);
                let innerHTML = "";
                visibleHeaders.forEach(header => {
                    innerHTML += `<td>${user[header]}</td>`;
                });
                newRow.innerHTML = innerHTML;
                tbody.appendChild(newRow);
            });
            console.log("Sincronización completa recibida.");
        }
    };

    */
</script>