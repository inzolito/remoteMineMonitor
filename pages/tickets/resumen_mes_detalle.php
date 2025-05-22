<?php
//Trabajado por Paulina Aedo 

// Ruta del archivo CSV
$csv_file = "../../../globalData/tickets.csv";
setlocale(LC_TIME, 'es_ES.UTF-8');
$filtroFaena = urldecode($_GET['filtroFaena']);

$filtroFaenaPDF = ($filtroFaena == "") ? "Todos los clientes" : $filtroFaena;


$codelco_accounts = [
    "Salvador" => "Codelco | División El Salvador",
    "Hales" => 'Codelco | División Ministro Hales',
    "Chuquicamata" => 'Codelco | División Chuquicamata',
    "Radomiro" => 'Codelco | División Radomiro Tomic',
    "Mina Sur" => 'Codelco | División Mina Sur',
];

$amsa_accounts = [
    "Centinela " => "AMSA | Centinela",
    "Zaldivar" => 'AMSA | Zaldivar',
    "Antucoya" => 'AMSA | Antucoya',
];

$other_accounts = [
    "Caserones" => "Caserones",
    "Verde" => 'Mantoverde',
    "Blanca" => 'Quebrada Blanca',
    "Negro" => 'Cerro Negro',
    "Veladero" => 'Veladero',
    "Hexagon" => 'Hexagon Mining',
    "Andacollo" => 'Carmen de Andacollo',
];

// Leer el archivo CSV
$csv_data = [];
$ticket_status_count = [];
$account_numbers = [];
$product_name_count = [];
$cantidad_monitoreo = 0;

$startDate = $_REQUEST["startDate"];
$endDate = $_REQUEST["endDate"];
if ($startDate == 0 & $endDate == 0) {
    $startDate = date("d-m-Y");
    $endDate = date("d-m-Y");
}


$start = new DateTime($startDate);
$end = new DateTime($endDate);

setlocale(LC_TIME, "es_ES.UTF-8");
$mesInicio = strftime("%B", $start->getTimestamp());
$mesFin = strftime("%B", $end->getTimestamp());
$anio = $start->format("Y");
$anioF= $end->format("Y");

$fechaNombrePdf = ($mesInicio === $mesFin) ? "$mesInicio-$anio" : "$mesInicio-$mesFin-$anio";
//echo $fechaNombrePdf; // Salida: "marzo-abril-2025"
$fechanombrePDF2=($mesInicio === $mesFin) ? "$mesInicio-$anioF" : "$mesInicio-$anio-$mesFin-$anioF";



// Convertir las fechas a formato adecuado
$formattedStartDate = strftime("%d de %B de %Y", strtotime($startDate));
$formattedEndDate = strftime("%d de %B de %Y", strtotime($endDate));


$mensajeFechaInforme = "Desde $formattedStartDate hasta $formattedEndDate";

if (($handle = fopen($csv_file, "r")) !== FALSE) {
    $headers = fgetcsv($handle, 1000, ",");

    // Procesar las filas 
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

        $create_date = date('d-m-Y', strtotime($create_date));

        if (strtotime($create_date) >= strtotime($startDate) && strtotime($create_date) <= strtotime($endDate)) {



            if (!isset($csv_data[$case_number])) {
                $monitoring = (strpos($subject, 'Monitoreo') === 0) ? '1' : '0'; // Asignar el valor '1' o '0' a 'monitoring' dependiendo de si 'subject' empieza con 'Monitoreo'
                $cantidad_monitoreo = $cantidad_monitoreo + (int) $monitoring;


                if (preg_match('/sumarización|sumarizacion|sumarizar|rezumarizar/i', $subject)) {
                    $product_name = 'Sumarización';
                }

                if (preg_match('/importador|importando/i', $subject)) {
                    $product_name = 'Importador';
                }

                if (preg_match('/servidores|servidor/i', $subject)) {
                    $product_name = 'Servidor';
                }

                foreach (array_merge($codelco_accounts, $amsa_accounts, $other_accounts) as $key => $value) {
                    if (stripos($modified_account_name, $key) !== false) {
                        $modified_account_name = $value;
                        break;
                    }
                }

                if (!in_array($modified_account_name, $account_numbers)) {
                    $account_numbers[] = $modified_account_name;
                }

                $csv_data[$case_number] = [
                    'case_id' => $case_id,
                    'case_number' => $case_number,
                    'owner_name' => $owner_name,
                    'account_name' => $modified_account_name,
                    'created_date' => $create_date,
                    'status' => $status,
                    'monitoring' => $monitoring,
                    'subject' => $subject,
                    'product_name' => $product_name
                ];

                // Contar los estados
                if (!isset($ticket_status_count[$status])) {
                    $ticket_status_count[$status] = 0;
                }
                $ticket_status_count[$status]++;

                // Contar los product names
                if (!isset($product_name_count[$product_name])) {
                    $product_name_count[$product_name] = 0;
                }
                $product_name_count[$product_name]++;
            }
        }
    }
    fclose($handle);
}
sort($account_numbers);


//echo "Cantidad = ".count($account_numbers);
?>



<!-- Estilo CSS -->
<style>
    .table-container {
        margin: 20px auto;
        width: 90%;
    }

    .table th {
        white-space: nowrap;
    }

    .table th:nth-child(-n+8) {
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

    .table td.status-centered {
        text-align: center
    }

    .table td.owner-centered {
        text-align: center
    }

    .table td,
    .table th {
        text-align: center;
        vertical-align: middle;
    }

    .table-container {
        width: 45%;
        margin: 0 auto;
    }

    .table-container table {
        width: 100%;
        table-layout: fixed;
    }


    .chart-table-container {
        display: flex;
        justify-content: space-around;
        align-items: flex-start;
        margin-bottom: 30px;
    }


    .chart-container {
        width: 45%;
        max-width: 400px;
        margin-right: 20px;
    }


    h1 {
        text-align: center;
    }
</style>

<div class="card-footer clearfix">
    <button class="dt-button buttons-pdf" onclick="exportToPDF()">
        <i class="fas fa-file-pdf"></i> Exportar PDF
    </button>
</div>


<div id="table-container">
    <table id="dataTable" class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th>Case Number</th>
                <th>Owner Name</th>
                <th id="col_accountName">Account Name</th>
                <th>Created Date</th>
                <th>Status</th>
                <th>Monitoring</th>
                <th>Subject</th>
                <th>Product Name</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($csv_data as $ticket) {
                echo "<tr>";
                echo "<td><a href='https://usa1.lightning.force.com/lightning/r/Case/{$ticket['case_id']}/view' target='_blank'>" . htmlspecialchars($ticket['case_number']) . "</a></td>";
                echo "<td class='status-centered'>" . htmlspecialchars($ticket['owner_name']) . "</td>";
                echo "<td class='status-centered'>" . htmlspecialchars($ticket['account_name']) . "</td>";
                echo "<td class='status-centered'>" . htmlspecialchars($ticket['created_date']) . "</td>";
                echo "<td class='status-centered'>" . htmlspecialchars($ticket['status']) . "</td>";
                echo "<td class='status-centered'>" . htmlspecialchars($ticket['monitoring']) . "</td>";
                echo "<td class='status-centered'>" . htmlspecialchars($ticket['subject']) . "</td>";
                echo "<td class='status-centered'>" . htmlspecialchars($ticket['product_name']) . "</td>";
                echo "</tr>";
            }
            arsort($ticket_status_count);
            arsort($product_name_count);
            arsort($monitoreo);
            ?>

        </tbody>
    </table>
</div>


<div style="display: flex; justify-content: space-around; margin-top: 20px;">
    <!-- Gráfico de Torta por Estado -->
    <div class="chart-container" style="width: 45%;">
        <canvas id="statusPieChart"></canvas>
    </div>

    <!-- Gráfico de Barras por Product Name -->
    <div class="chart-container" style="width: 80%; max-width: 800px;">
        <canvas id="productBarChart"></canvas>
    </div>

    <!-- Gráfico de Torta por Monitoreo -->
    <div class="chart-container" style="width: 45%;">
        <canvas id="monitoringPieChart"></canvas>
    </div>

</div>

<!-- Contenedor de los gráficos y las tablas -->


<div class="row" style="margin-top: -100px;">


    <div class="col-md-4">
        <!-- Gráfico de Torta por Estado -->
        <div class="chart-container">

            <canvas id="statusPieChart"></canvas>
        </div>
        <table class="table table-bordered table-striped w-75 mx-auto" id="statusTable">
            <thead>
                <tr>
                    <th style="text-align: center;">Estado</th>
                    <th style="text-align: center;">Cantidad</th>
                    <th style="text-align: center;">% Representativo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_tickets = array_sum($ticket_status_count);
                $colors = ['#84CCDA', '#FEF376', '#A7D769', '#CB9EC8', '#F1C0D1'];
                $i = 0;
                foreach ($ticket_status_count as $status => $count) {
                    $percentage = ($count / $total_tickets) * 100;
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($status) . "</td>";
                    echo "<td>" . $count . "</td>";
                    echo "<td>" . number_format($percentage, 2) . "%</td>";
                    echo "</tr>";
                    $i++;
                }
                ?>
            </tbody>
        </table>


    </div>



    <div class="col-md-4">

        <!-- Gráfico de Barras por Product Name -->
        <div class="chart-container">

            <canvas id="productBarChart"></canvas>
        </div>
        <table class="table table-bordered table-striped w-75 mx-auto" id="productTable">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>% Representativo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_products = array_sum($product_name_count);
                $total_cantidad = 0;
                $total_porcentaje = 0;
                $num_productos = 0;

                foreach ($product_name_count as $product => $count) {
                    $percentage = ($count / $total_products) * 100;
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($product) . "</td>";
                    echo "<td>" . $count . "</td>";
                    echo "<td>" . number_format($percentage, 2) . "%</td>";
                    echo "</tr>";

                    $total_cantidad += $count;
                    $total_porcentaje += $percentage;
                    $num_productos++;
                }
                $promedio_porcentaje = $num_productos > 0 ? $total_porcentaje / $num_productos : 0;

                echo "<tr>";
                echo "<td><b>Total</b></td>";
                echo "<td><b>" . $total_cantidad . "</b></td>";
                echo "<td><b>" . number_format($promedio_porcentaje, 2) . "%</b></td>";
                echo "</tr>";
                ?>

            </tbody>
        </table>

    </div>


    <div class="col-md-4">

        <!-- Gráfico de Torta de Tickets de Monitoreo -->
        <div class="chart-container">

            <canvas id="monitoringPieChart"></canvas>
        </div>
        <table class="table table-bordered table-striped w-75 mx-auto" id="monitoringTable">
            <thead>
                <tr>
                    <th style="text-align: center;">Categoría</th>
                    <th style="text-align: center;">Cantidad</th>
                    <th style="text-align: center;">% Representativo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_tickets = count($csv_data);
                $colors = ['#A7D769', '#01AEBD'];
                $categories = [
                    'Monitoreo' => $cantidad_monitoreo,
                    'Otros' => $total_tickets - $cantidad_monitoreo
                ];
                foreach ($categories as $category => $count) {
                    $percentage = ($count / $total_tickets) * 100;
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($category) . "</td>";
                    echo "<td>" . $count . "</td>";
                    echo "<td>" . number_format($percentage, 2) . "%</td>";
                    $color_index++;
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

    </div>
</div>


<script>
    function exportToPDF() {

        document.querySelectorAll(".daterangepicker").forEach(el => el.removeAttribute("style"));



        const {
            jsPDF
        } = window.jspdf;
        const doc = new jsPDF();

        const selectedAccount = $('#accountFilter').val() || 'Todos';
        const today = new Date();
        const formattedDate = `Fecha: ${today.getDate().toString().padStart(2, '0')} de ${today.toLocaleString('default', { month: 'long' })} de ${today.getFullYear()}`;

        const title = "TICKETS MENSUALES";
        const titleFontSize = 24;
        const dateRangeFontSize = 12;
        const logoPath = "dist/img/system/hexagon_logo.png";
        const pageWidth = doc.internal.pageSize.width;
        const pageHeight = doc.internal.pageSize.height;

        doc.setFontSize(titleFontSize); // Asegurar que el tamaño se aplica
        doc.setFont("helvetica", "bold");
        const titleWidth = doc.getTextWidth(title);
        const titleX = (pageWidth - titleWidth) / 2;
        const titleY = (pageHeight / 2) - 24;

        doc.text(title, titleX, titleY); // Aquí se dibuja el título en el PDF
        //-----------------------------------------------

        // texto de filtro faena cat
        const additionalText = "<?php echo $filtroFaenaPDF ?>";
        const filtroFaenaFontSize = 16; // Tamaño de fuente para el subtítulo
        doc.setFontSize(filtroFaenaFontSize);
        doc.setFont("helvetica", "normal");
        const additionalTextWidth = doc.getTextWidth(additionalText);
        const filtroFaenaX = (pageWidth - additionalTextWidth) / 2;
        const filtroFaenaY = titleY + 10;

        // Dibuja el texto adicional
        doc.text(additionalText, filtroFaenaX, filtroFaenaY);

        //-----------------------------------------
        const subtitle = '"Soporte Remoto - Hexagon"'; // El subtítulo a mostrar
        const subtitleFontSize = 16; // Tamaño de fuente para el subtítulo
        doc.setTextColor(169, 169, 169); // Gris claro
        doc.setFontSize(subtitleFontSize);
        doc.setFont("helvetica", "normal");
        // Calcula el ancho del subtítulo y su posición X para centrarlo
        const subtitleWidth = doc.getTextWidth(subtitle);
        const subtitleX = (pageWidth - subtitleWidth) / 2; // Centrado horizontalmente
        const subtitleY = filtroFaenaY + 20; // Coloca el subtítulo debajo del título
        doc.text(subtitle, subtitleX, subtitleY);
        doc.setTextColor(0, 0, 0); // Restaurar a negro

        //---------------------------------------------


        const dateRange = "<?php echo $mensajeFechaInforme ?>";
        doc.setFontSize(dateRangeFontSize);
        const dateRangeWidth = doc.getTextWidth(dateRange);
        const dateRangeX = (pageWidth - dateRangeWidth) / 2;
        const dateRangeY = filtroFaenaY + 10;
        doc.text(dateRange, dateRangeX, dateRangeY);


        const imgData = new Image();
        imgData.src = logoPath;
        imgData.onload = function() {
            const imgWidth = 30;
            const imgHeight = 11;
            const logoX = 10;
            const logoY = 10;

            doc.addImage(imgData, "PNG", logoX, logoY, imgWidth, imgHeight);
            //doc.text(title, titleX, titleY);
            //doc.text(subtitle, subtitleX, subtitleY);


            doc.setFontSize(12);
            doc.setFont("helvetica", "normal");
            const dateX = pageWidth - 70;
            const dateY = pageHeight - 20;
            doc.text(formattedDate, dateX, dateY);

            doc.addPage();
            let yPos = 10;

            const tables = document.querySelectorAll("table");
            const charts = document.querySelectorAll("canvas");

            tables.forEach((table, index) => {
                const rows = table.querySelectorAll("tr");
                let rowCount = 1;
                if (table.classList.contains("daterangepicker") || table.closest('.daterangepicker')) {
                    return; // Salir de esta iteración y no agregar la tabla
                }
                if (index === 0) {
                    rows.forEach(row => {
                        const cells = row.querySelectorAll("td, th");
                        const filteredCells = [];

                        if (row.rowIndex === 0) {
                            filteredCells.push("#");
                        }

                        for (let i = 0; i < cells.length; i++) {
                            if (i === 0 || i === 1 || (i === 2 && selectedAccount === 'Todos') || i === 3 || i === 4 || i === 6) {
                                filteredCells.push(cells[i].textContent);
                            }
                        }


                        if (row.rowIndex !== 0) {
                            filteredCells.unshift(`${rowCount}`);
                            rowCount++;
                        }

                        const newRow = row.cloneNode();
                        filteredCells.forEach((content) => {
                            const newCell = document.createElement('td');
                            newCell.textContent = content;
                            newRow.appendChild(newCell);
                        });

                        row.replaceWith(newRow);
                    });
                }

                const rowHeight = 10;
                const maxRowsPerPage = Math.floor((doc.internal.pageSize.height - 30) / rowHeight);
                let currentRow = 0;

                doc.autoTable({
                    html: table,
                    startY: yPos,
                    styles: {
                        overflow: 'linebreak',
                        fontSize: 10,
                        halign: 'center',
                        valign: 'middle',
                        cellPadding: 2,
                        lineWidth: 0.1,
                        lineColor: [0, 0, 0],
                        fillColor: [240, 240, 240],
                        font: 'helvetica',
                        fontStyle: 'normal',
                    },
                    headStyles: {
                        fillColor: ['#004F67'],
                        textColor: [255, 255, 255],
                        fontSize: 10,
                        halign: 'center',
                        valign: 'middle',
                    },
                    alternateRowStyles: {
                        fillColor: [255, 255, 255],
                    },
                    didDrawPage: (data) => {

                        const pageWidth = doc.internal.pageSize.width;
                        const textWidth = doc.getTextWidth("HXG | Monitoreo Remoto");
                        const xPosition = (pageWidth - textWidth) / 2;
                        doc.setFontSize(10);
                        doc.setFont("helvetica", "normal");
                        doc.text("HXG | Monitoreo Remoto", xPosition, 5);



                        // Número de página
                        const pageNumber = doc.internal.getNumberOfPages();
                        const currentPage = doc.internal.getCurrentPageInfo().pageNumber;
                        doc.setFontSize(8);
                        doc.text(`${currentPage}`, pageWidth - 20, pageHeight + 10);

                        // Pie de página
                        doc.setFontSize(8);
                        doc.text(`Confidence to ${selectedAccount} | Copyright © Hexagon`, pageWidth / 2, pageHeight + 10, {
                            align: 'center'
                        });
                    }
                });

                yPos = doc.lastAutoTable.finalY + 10;

                const chart = charts[index];
                if (chart) {
                    if (yPos > 200) {
                        doc.addPage();
                        yPos = 10;
                    }

                    const chartImgData = chart.toDataURL("image/png");

                    if (index === 0 || index === 2) {
                        const centerX = (doc.internal.pageSize.width - 80) / 2;
                        doc.addImage(chartImgData, "PNG", centerX, yPos, 80, 80);
                        yPos += 90;
                    } else if (index === 1) {
                        const centerX = (doc.internal.pageSize.width - 100) / 2.5;
                        doc.addImage(chartImgData, "PNG", centerX, yPos, 120, 70);
                        yPos += 80;
                    }
                }
            });

            const faenaName = selectedAccount;
           // const fileName = `${faenaName}_Tickets_${today.toLocaleString('default', { month: 'long' })}_${today.getFullYear()}.pdf`;
            const fileName = `${faenaName}_Tickets_<?php echo $fechanombrePDF2 ?>.pdf`;

            doc.save(fileName);
        }
    }


    Chart.register(ChartDataLabels);
    $(document).ready(function() {
        var table = $('#dataTable').DataTable({
            dom: '<"top"Bfr>t<"bottom"lp>',
            buttons: [{
                    extend: 'print',
                    text: 'print',
                    customize: function(win) {
                        var selectedAccount = $('#accountFilter').val();
                        var title = 'Tickets Mensuales';
                        var subtitle = `<h2 style="font-size: 32px; margin-top: 10px; display: block; color: #333;">${selectedAccount}</h2>`;
                        var subtitle3 = `<h3 style="font-size: 25px; margin-top: 10px; display: block; color: #444;"><?php echo $mensajeFechaInforme ?> </h3>`;
                        var subtitle2 = `<h4 style="font-size: 20px; margin-top: 30px; display: block; color: #666;">"Soporte Remoto - Hexagon"</h4>`;

                        // Obtener la fecha de hoy en el formato requerido
                        var today = new Date();
                        var day = today.getDate().toString().padStart(2, '0');
                        var month = today.toLocaleString('default', {
                            month: 'long'
                        });
                        var year = today.getFullYear();
                        var formattedDate = `Fecha: ${day} de ${month} de ${year}`;

                        //$(win.document.body).find('table') .find('th:nth-child(6), td:nth-child(6), th:nth-child(7), td:nth-child(7), th:nth-child(8), td:nth-child(8), th:nth-child(9), td:nth-child(9)').hide();
                        $(win.document.body).find('table').find('th:nth-child(6), td:nth-child(6),th:nth-child(7), td:nth-child(7), th:nth-child(8), td:nth-child(8), th:nth-child(9), td:nth-child(9)').hide();


                        $(win.document.body).find('table').each(function() {
                            $(this).find('thead tr').prepend('<th style="text-align: center;">#</th>');
                            $(this).find('tbody tr').each(function(index) {
                                $(this).prepend('<td style="text-align: center;">' + (index + 1) + '</td>');

                            });
                        });

                        $(win.document.body).append(`
                            <div style="position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; padding: 10px; background-color: #f5f5f5; color: #333;">
                                Confidence to &nbsp;&nbsp;&nbsp; ${selectedAccount} &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp; Copyright © Hexagon
                            </div>
                        `);

                        $(win.document.body).append(`
                            <div style="position: absolute; bottom: 100px; right: 10px; background-color: #f5f5f5; padding: 5px 15px; font-size: 20px; color: #333; border-radius: 5px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">
                                ${formattedDate}
                            </div>
                        `);

                        $(win.document.head).prepend(`
                            <style>
                                table {
                                    margin-top: 80px !important;
                                    border-collapse: collapse;
                                    font-family: Arial, sans-serif;
                                    color: #333;
                                }
                                .custom-title-container {
                                    display: flex;
                                    flex-direction: column;
                                    justify-content: center;
                                    align-items: center;
                                    text-align: center;
                                    margin-top: 50px;
                                    padding: 20px;
                                    background-color: #fafafa;
                                    border-radius: 10px;
                                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                                }
                                .image-over-h1 img {
                                    width: 200px;
                                    height: auto;
                                    margin-bottom: 20px;
                                }

                                body {
                                    margin: 0;
                                }

                                h1, h2, h3, h4 {
                                    font-family: 'Arial', sans-serif;
                                    margin: 5px 0;
                                    text-align: center;
                                    color: #444;
                                }
                                h1 {
                                    font-size: 40px;
                                    font-weight: bold;
                                    margin-bottom: 20px;
                                }
                                h2 {
                                    font-size: 28px;
                                }
                                h3 {
                                    font-size: 22px;
                                    margin-top: 10px;
                                }
                                h4 {
                                    font-size: 18px;
                                    margin-top: 20px;
                                }
                                @media print {
                                    .logo-print {
                                        display: block !important;
                                        margin: 0 auto;
                                    }
                                    .footer {
                                        position: fixed;
                                        bottom: 0;
                                        width: 100%;
                                        text-align: center;
                                        font-size: 14px;
                                        padding: 10px;
                                        background-color: #f5f5f5;
                                        color: #333;
                                    }
                                    .image-over-h1 {
                                        position: relative;
                                        text-align: center;
                                    }
                                    .image-over-h1 img {
                                        position: absolute;
                                        top: -80px;
                                        left: 50%;
                                        transform: translateX(-50%);
                                        width: 200px;
                                        height: auto;
                                    }
                                }
                            </style>
                        `);

                        $(win.document.body).prepend(`
                            <div class="custom-title-container" style="display: flex; flex-direction: column; justify-content: center; align-items: center; margin-top: 45vh; text-align: center; page-break-after: always;">
                                <div class="image-over-h1">
                                    <img src="dist/img/system/hexagon_logo.png" alt="Hexagon Mining Logo">
                                </div>
                                <h1 style="font-size: 48px; font-weight: bold; margin-bottom: 10px;">${title}</h1>
                                ${subtitle}
                                ${subtitle3}
                                ${subtitle2}
                            </div>
                        `);
                        //tittle nombre pdf  Todos_Tickets_marzo_2025
                        <?php

                        ?>

                        if ($("#accountFilter").val() == "") {
                            nombrePdfDefault = "Todos_<?php echo $fechaNombrePdf ?>"

                        } else {
                            nombrePdfDefault = $("#accountFilter").val() + "_<?php echo $fechaNombrePdf ?>"

                        }
                        $(win.document).find("title").text(nombrePdfDefault);

                        // Agregar tabla primero y luego el gráfico de estado
                        var statusTableHtml = $('#statusTable tbody').html();
                        var statusChartImg = document.getElementById('statusPieChart').toDataURL();


                        $(win.document.body).append(`
                            <div style="text-align: center; page-break-before: always;">
                                <h2 style="margin-top: 0;">Tickets por Estado</h2>  
                                <table id="statusTableExport" style="margin: 0 auto; border: 1px solid #ccc; border-collapse: collapse; width: 80%; max-width: 800px; text-align: center;">
                                    <thead style="background-color:rgb(0, 0, 0);">
                                        <tr>
                                            <th style="padding: 10px; border: 1px solid #ccc;">#</th> 
                                            <th style="padding: 10px; border: 1px solid #ccc;">Cantidad</th>
                                            <th style="padding: 10px; border: 1px solid #ccc;">% Representativo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${statusTableHtml}
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top: 10px; text-align: center;">
                                <img src="${statusChartImg}" style="width: 40%; margin-bottom: 40px;">
                            </div>
                        `);


                        // Agregar tabla primero y luego el gráfico de productos
                        var productTableHtml = $('#productTable tbody').html();
                        var productChartImg = document.getElementById('productBarChart').toDataURL();

                        $(win.document.body).append(`
                            <div style="margin-top: 10px; text-align: center; page-break-before: always;">
                                <h2>Tickets por Tipo</h2>
                                <table style="margin: 0 auto; border: 1px solid #ccc; border-collapse: collapse; width: 80%; max-width: 800px; text-align: center;">
                                    <thead style="background-color: #f4f4f4;">
                                        <tr>
                                            <th style="padding: 10px; border: 1px solid #ccc;">Categoría</th>
                                            <th style="padding: 10px; border: 1px solid #ccc;">Cantidad</th>
                                            <th style="padding: 10px; border: 1px solid #ccc;">% Representativo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${productTableHtml}
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top: 50px; text-align: center;">
                                <img src="${productChartImg}" style="width: 60%; margin-bottom: 10px;">
                            </div>
                        `);

                        // Agregar tabla primero y luego el gráfico de monitoreo
                        var monitoringTableHtml = $('#monitoringTable tbody').html();
                        var monitoringChartImg = document.getElementById('monitoringPieChart').toDataURL();

                        $(win.document.body).append(`
                            <div style="margin-top: 10px; text-align: center; page-break-before: always;">
                                <h2>Tickets de Monitoreo</h2>
                                <table style="margin: 0 auto; border: 1px solid #ccc; border-collapse: collapse; width: 80%; max-width: 800px; text-align: center;">
                                    <thead style="background-color: #f4f4f4;">
                                        <tr>
                                            <th style="padding: 10px; border: 1px solid #ccc;">Categoria</th>
                                            <th style="padding: 10px; border: 1px solid #ccc;">Cantidad</th>
                                            <th style="padding: 10px; border: 1px solid #ccc;">% Representativo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${monitoringTableHtml}
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top: 50px; text-align: center;">
                                <img src="${monitoringChartImg}" style="width: 40%; margin-bottom: 40px;">
                            </div>
                        `);

                    },

                    filename: function() {
                        var selectedAccount = $('accountFilter').val();
                        var month = new Date().toLocaleString('default', {
                            month: 'long'
                        });
                        var year = new Date().getFullYear();
                        return selectedAccount + "_Tickets_" + month + "_" + year;

                    }
                },
                'copy',
                'csv',
                'excel',
            ],
            "pageLength": 5,
            "lengthMenu": [50, 100, 300],
            "order": []
        });


        let statusChart = null;
        let productChart = null;
        let monitoringChart = null;

        updateCharts($('#accountFilter').val());

        function updateCharts(accountName) {
            table.columns(2).search(accountName).draw();

            var filteredData = filterDataByAccount(accountName);
            var statusCounts = countStatusByAccount(filteredData);
            var productNameCounts = countProductByAccount(filteredData);
            var monitoringCounts = countMonitoringByAccount(filteredData);

            resetChart(statusChart);
            resetChart(productChart);
            resetChart(monitoringChart);

            statusChart = createPieChart('statusPieChart', 'Tickets por Estado', statusCounts);
            productChart = createBarChart('productBarChart', 'Tickets por Tipo', productNameCounts);
            monitoringChart = createPieChart('monitoringPieChart', 'Tickets de Monitoreo', monitoringCounts);
            actualizarTablasDesdeGraficos(statusCounts, productNameCounts, monitoringCounts)

        }

        function actualizarTablasDesdeGraficos(statusCount, productCount, totalMonitoreo) {

            // alert(JSON.stringify(totalMonitoreo));
            totalProduct = Object.values(productCount).reduce((a, b) => a + b, 0)
            actualizarTabla("#statusTable tbody", statusCount);
            actualizarTabla("#productTable tbody", productCount);
            actualizarTabla("#monitoringTable tbody", {
                "Monitoreo": totalMonitoreo['Monitoreo'],
                "No Monitoreo": totalMonitoreo['Otros']
            });
        }

        function actualizarTabla(selector, datos) {
            const tbody = document.querySelector(selector);
            tbody.innerHTML = "";

            let sumaCantidad = 0;
            let sumaPorcentaje = 0;

            const total = Object.values(datos).reduce((a, b) => a + b, 0);
            const porcentajes = [];

            Object.entries(datos).forEach(([clave, valor]) => {
                const porcentaje = total > 0 ? (valor / total) * 100 : 0;
                porcentajes.push(porcentaje);
                const fila = `<tr><td>${clave}</td><td>${valor}</td><td>${porcentaje.toFixed(2)}%</td></tr>`;
                tbody.innerHTML += fila;
                sumaCantidad += valor;
            });


            let diferencia = 100 - porcentajes.reduce((a, b) => a + b, 0);
            if (diferencia !== 0) {
                porcentajes[porcentajes.length - 1] += diferencia;
            }

            sumaPorcentaje = porcentajes.reduce((a, b) => a + b, 0);

            const filaTotal = `<tr><th>Total</th><th>${sumaCantidad}</th><th>${sumaPorcentaje.toFixed(2)}%</th></tr>`;
            tbody.innerHTML += filaTotal;
        }



        function filterDataByAccount(accountName) {
            let allData = Object.values(<?= json_encode($csv_data) ?>);
            return accountName ? allData.filter(ticket => ticket.account_name === accountName) : allData;
        }

        function countStatusByAccount(filteredData) {
            var statusCounts = {};
            filteredData.forEach(ticket => {
                var status = ticket.status;
                statusCounts[status] = (statusCounts[status] || 0) + 1;
            });
            return statusCounts;
        }

        function countProductByAccount(filteredData) {
            var productCounts = {};
            filteredData.forEach(ticket => {
                var productName = ticket.product_name;
                productCounts[productName] = (productCounts[productName] || 0) + 1;
            });
            return productCounts;
        }

        function countMonitoringByAccount(filteredData) {
            var monitoringCounts = {
                'Monitoreo': 0,
                'Otros': 0
            };
            filteredData.forEach(ticket => {
                var isMonitoring = ticket.monitoring === '1' ? 'Monitoreo' : 'Otros';
                monitoringCounts[isMonitoring]++;
            });
            return monitoringCounts;
        }

        function resetChart(chart) {
            if (chart) {
                chart.destroy();
            }
        }

        function createPieChart(chartId, title, dataCounts) {
            const ctx = document.getElementById(chartId).getContext('2d');
            const colors = ['#FEF376', '#84CCDA', '#A7D769', '#CB9EC8', '#F29A41', '#004F67', '#969696', '#CB9EC8', '#A0522D'];
            const backgroundColors = Object.keys(dataCounts).map((_, index) => colors[index % colors.length]);

            return new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: Object.keys(dataCounts),
                    datasets: [{
                        label: title,
                        data: Object.values(dataCounts),
                        backgroundColor: backgroundColors,
                        borderColor: 'rgba(33, 37, 41, 0.5)',
                        borderWidth: 1,
                        hoverOffset: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: title,
                            font: {
                                weight: 'bold',
                                color: '#343a40',
                                size: 20
                            },
                            padding: {
                                top: 10,
                                bottom: 10
                            }
                        },
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                color: '#343a40',
                                font: {
                                    size: 14
                                }
                            }
                        },
                        datalabels: {
                            formatter: (value, context) => {
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = Math.round((value / total) * 100);
                                return `${value}\n${percentage}%`;
                            },
                            color: '#343a40',
                            font: {
                                weight: 'bold',
                                size: 14
                            },
                            anchor: 'center',
                            align: 'center',
                            textAlign: 'center',
                            clamp: true
                        }
                    },
                    layout: {
                        padding: {
                            left: 20,
                            right: 20,
                            top: 20,
                            bottom: 20
                        }
                    }
                }
            });
        }

        function createBarChart(chartId, title, dataCounts, opciones = {}) {
            const ctx = document.getElementById(chartId).getContext('2d');
            const colors = ['#FEF376', '#84CCDA', '#A7D769', '#F29A41', '#CB9EC8', '#004F67', '#969696', '#CB9EC8', '#A0522D'];
            const backgroundColors = Object.keys(dataCounts).map((_, index) => colors[index % colors.length]);


            const opcionesPorDefecto = {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: title,
                        font: {
                            weight: 'bold',
                            size: 20,
                            color: '#333'
                        },
                        padding: {
                            top: 20,
                            bottom: 30
                        }
                    },
                    datalabels: {
                        formatter: (value, context) => {
                            let totalDataset = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / totalDataset) * 100);
                            return `${value} (${percentage}%)`;
                        },
                        color: '#333',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        anchor: 'end',
                        align: 'end',
                        offset: 4,
                        clamp: true
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const valor = context.formattedValue;
                                const etiqueta = context.label;
                                return `${etiqueta}: ${valor} unidades`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Categorías',
                            color: '#333'
                        },
                        ticks: {
                            color: '#333'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad',
                            color: '#333'
                        },
                        ticks: {
                            color: '#333'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuad'
                }
            };

            const opcionesFinales = {
                ...opcionesPorDefecto,
                ...opciones
            };

            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(dataCounts),
                    datasets: [{
                        label: title,
                        data: Object.values(dataCounts),
                        backgroundColor: backgroundColors,
                        borderColor: 'black',
                        borderWidth: 2,
                        borderRadius: 5,
                        barPercentage: 0.8,
                        categoryPercentage: 0.8

                    }]
                },
                options: opcionesFinales
            });

            return chart;
        };
    });
</script>