<?php
// Ruta del archivo CSV
$csv_file = "../../../globalData/trazabilidad.csv";

// Leer el archivo CSV
$csv_data = [];
if (($handle = fopen($csv_file, "r")) !== FALSE) {
    $headers = fgetcsv($handle, 1000, ",");

    // Procesar las filas 
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $case_id = $row[0]; // "Case ID"
        $case_number = $row[1]; // "Case Number"
        $case_created = $row[2]; // "Case Number"
        $status = $row[3]; // "Status"
        $subject = $row[4]; // "Subject"
        $owner = $row[5]; // "Owner"    
        $created_by = $row[6];  // "Created By"
        $create_date = $row[7]; // "Create Date"
        $comment_body = $row[8]; // "Comment Body"


        if (strtotime($case_created) >= strtotime("2025-02-01")) {
            if (!isset($csv_data[$case_number])) {
                $csv_data[$case_number] = [
                    'case_id' => $case_id,
                    'status' => $status,
                    'owner' => $owner,
                    'comments' => []
                ];
            }

            if (empty($comment_body) || strtolower($comment_body) == "sin comentarios" && $status === 'Closed') {
                $csv_data[$case_number]['comments'][] = [
                    'created_by' => $created_by,
                    'create_date' => $create_date,
                    'comment_body' => 'Sin comentarios'
                ];
            } else {
                $csv_data[$case_number]['comments'][] = [
                    'created_by' => $created_by,
                    'create_date' => $create_date,
                    'comment_body' => $comment_body
                ];
            }
        }
    }
    fclose($handle);
}

$alerted_tickets = [];
$contador_com = 0;
$total_tickets = 0;

foreach ($csv_data as $case_number => $data) {
    $comments = $data['comments'];
    $case_id = $data['case_id'];
    $status = $data['status'];
    $owner = $data['owner'];


    $num_comments = 0;
    foreach ($comments as $comment) {
        if (strtolower($comment['comment_body']) != "sin comentarios") {
            $num_comments++;
        }
    }

    if ($num_comments == 0) {
        $contador_com++;
    }

    $total_tickets++;

    $alerted_tickets[] = [
        'case_number' => $case_number,
        'case_id' => $case_id,
        'status' => $status,
        'owner' => $owner,
        'comments' => $comments,
        'num_comments' => $num_comments
    ];
}

?>



<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>


<!-- Estilo CSS -->
<style>
    .table-container {
        margin: 20px auto;
        width: 90%;
    }

    .table th {
        white-space: nowrap;
    }

    .table th:nth-child(-n+4) {
        text-align: center;
    }

    .table td {
        text-align: left;
        vertical-align: top;
        padding: 10px;
    }

    .table thead {
        background-color: #004F67;
        color: white;
    }

    .table thead th {
        text-align: center;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f9f9f9;
    }

    .table-hover tbody tr:hover {
        background-color: #f1f1f1;
    }

    .table td:first-child {
        text-align: center;
    }

    .table td.centered {
        text-align: center;
    }

    .comment-container {
        margin-bottom: 5px;
        padding: 8px;
        border-bottom: 1px solid #ddd;
    }

    .comment-container:last-child {
        border-bottom: none;
    }

    .comment-header {
        font-weight: bold;
        color: #004F67;
    }

    .comment-body {
        margin-top: 5px;
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .alert-icon {
        font-size: 1.2rem;
        margin-left: 8px;
    }

    .table td.status-centered {
        text-align: center
    }

    .table td.owner-centered {
        text-align: center
    }

    .zero-comment-counter {
        background-color: #F4F6F9;
        color: #004F67;
        border: 1px solid #F4F6F9;
        padding: 15px;
        margin: 20px auto;
        width: 50%;
        display: flex;
        align-items: center;
        border-radius: 10px;
        font-size: 1.2rem;
        font-weight: bold;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        justify-content: center;
    }
</style>


<h1>
    <center>
        <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
        Trazabilidad
        <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
    </center>
</h1>
<br><br>

<div class="zero-comment-counter">
    <i class="fa-solid fa-comment-dots"></i>&nbsp;&nbsp;&nbsp;
    <span class="counter-text"><?php echo $contador_com; ?> Tickets Sin Comentarios de <?php echo $total_tickets; ?> Tickets Totales</span>
    &nbsp;&nbsp;&nbsp;<i class="fa-solid fa-comment-dots"></i>
</div>

<div id="table-container">
    <table id="dataTable" class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th>Ticket N°</th>
                <th>Estado</th>
                <th>Creado Por</th>
                <th>Cantidad de Comentarios</th>
                <th>Comentarios</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($alerted_tickets as $ticket) {
                $case_number = $ticket['case_number'];
                $case_id = $ticket['case_id'];
                $status = $ticket['status'];
                $owner = $ticket['owner'];
                $comments = $ticket['comments'];
                $num_comments = $ticket['num_comments'];

                // Icono de alerta
                $icon = '';
                if ($num_comments === 0) {
                    $icon = '<i class="fa-solid fa-circle-exclamation" style="color: #c41c1c;"></i>';
                } elseif ($num_comments === 1) {
                    $icon = '<i class="fa-solid fa-circle-exclamation" style="color: #e8ba11;"></i>';
                }

                echo "<tr>";
                echo "<td><a href='https://usa1.lightning.force.com/lightning/r/Case/{$case_id}/view' target='_blank'>" . htmlspecialchars($case_number) . "</a></td>";
                echo "<td class='status-centered'>" . htmlspecialchars($status) . "</td>";
                echo "<td class='status-centered'>" . htmlspecialchars($owner) . "</td>";
                echo "<td class='centered'>" . ($num_comments > 0 ? $num_comments : "0") . " $icon</td>";
                echo "<td>";

                foreach ($comments as $comment) {
                    echo "<div class='comment-container'>";
                    echo "<div class='comment-header'>" . htmlspecialchars($comment['created_by']) . " - " . htmlspecialchars($comment['create_date']) . "</div>";
                    echo "<div class='comment-body'>" . nl2br(htmlspecialchars($comment['comment_body'])) . "</div>";
                    echo "</div>";
                }

                echo "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        var table = $('#dataTable').DataTable({
            "pageLength": 5,
            "lengthChange": true,
            "dom": '<"top"f>rt<"bottom"lp><"clear">',
            "language": {
                "lengthMenu": "Mostrar _MENU_ resultados por página",
                "zeroRecords": "No se encontraron resultados",
                "info": "Mostrando página _PAGE_ de _PAGES_",
                "infoEmpty": "No hay datos disponibles",
                "infoFiltered": "(filtrado de _MAX_ resultados en total)",
                "search": "Buscar:",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            "order": [
                [3, 'asc'],
                [0, 'desc']
            ]
        });

        table.on('draw', function() {
            var filteredResults = table.rows({
                filter: 'applied'
            }).count();
            $(".counter-text").text(filteredResults + " Tickets totales");
        });
    });
</script>