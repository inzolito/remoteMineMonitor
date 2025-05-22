<?php
// Ruta del archivo CSV

$csv_file = "../../../globalData/tickets.csv";

if ($_GET['sd'] == 0) {

    $startDate =  date('Y-m-d', strtotime('today')) . " 00:00:00";
    $endDate =  date('Y-m-d', strtotime('today')) . " 23:59:59";
} else {

    $startDate = isset($_GET['sd']) ? $_GET['sd'] . " 08:00:00" : date('Y-m-d', strtotime('-1 day')) . " 08:00:00";
    $endDate = isset($_GET['ed']) ? $_GET['ed'] . " 07:59:59" : date('Y-m-d', strtotime('today')) . " 07:59:59";
}

$startDateObj = DateTime::createFromFormat('Y-m-d H:i:s', $startDate);
$endDateObj = DateTime::createFromFormat('Y-m-d H:i:s', $endDate);

// Cuentas de Codelco
$codelco_accounts = [ 
    "CODELCO | División El Salvador",
    "Mina Ministro Hales",
    "CODELCO | Division Mina Ministro Hales",
    "CODELCO | Division Norte Chuquicamata",
    "Radomiro Tomic Mine",
    "Corporacion Nacional del Cobre Mina Sur",
    "Chuquicamata Mine",
    "Division El Salvador"
];
// Cuentas de Codelco
$codelco_accounts = [
    "Salvador" => "CODELCO | División El Salvador",
    "Hales" => 'Codelco | División Ministro Hales',
    "Chuquicamata" => 'Codelco | División Chuquicamata',
    "Radomiro" => 'Codelco | División Radomiro Tomic',
    "Mina Sur" => 'Codelco | División Mina Sur',
];

// Cuentas de AMSA
$amsa_accounts = [
    "Centinela Mine",
    "Minera Antucoya",
    "Centinela Antofagasta Minerals",
    "Zaldivar Mine"
];

// Cuentas de AMSA
$amsa_accounts = [
    "Centinela" => 'Amsa | Centinela',
    "Antucoya" => 'Amsa | Antucoya',
    "Zaldivar" => 'Amsa | Zaldivar'
];

// Cuenta de Manto VERDE

$mv_accounts = [
    "Mantoverde S.A." => 'Mantoverde'
];

// Ingenieros
$allowed_owner_names = [
    "Claudio Ponce",
    "Roberto Maldonado",
    "Fernando Coronado",
    "Ricardo Rubio"
];


$last_status = [];
$codelco_records = [];
$amsa_records = [];
$mv_account = [];
$status_counts = ['Closed' => 0];
$closed_case_numbers = [];
$rows = [];
$records_to_display = [];


function dateBetween($startDate, $endDate, $rowDate)
{
    $startDateObj = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    $rowDateObj = new DateTime($rowDate);
    $startDay = $startDateObj->format('d');
    $startMonth = $startDateObj->format('m');
    $startYear = $startDateObj->format('Y');
    $startHour = $startDateObj->format('H');
    $startMinute = $startDateObj->format('i');
    $startSecond = $startDateObj->format('s');

    $endDay = $endDateObj->format('d');
    $endMonth = $endDateObj->format('m');
    $endYear = $endDateObj->format('Y');
    $endHour = $endDateObj->format('H');
    $endMinute = $endDateObj->format('i');
    $endSecond = $endDateObj->format('s');

    $rowDay = $rowDateObj->format('d');
    $rowMonth = $rowDateObj->format('m');
    $rowYear = $rowDateObj->format('Y');
    $rowHour = $rowDateObj->format('H');
    $rowMinute = $rowDateObj->format('i');
    $rowSecond = $rowDateObj->format('s');


    $startComparison =
        ($rowYear > $startYear) ||
        ($rowYear == $startYear && $rowMonth > $startMonth) ||
        ($rowYear == $startYear && $rowMonth == $startMonth && $rowDay > $startDay) ||
        ($rowYear == $startYear && $rowMonth == $startMonth && $rowDay == $startDay && $rowHour > $startHour) ||
        ($rowYear == $startYear && $rowMonth == $startMonth && $rowDay == $startDay && $rowHour == $startHour && $rowMinute > $startMinute) ||
        ($rowYear == $startYear && $rowMonth == $startMonth && $rowDay == $startDay && $rowHour == $startHour && $rowMinute == $startMinute && $rowSecond >= $startSecond);

    $endComparison =
        ($rowYear < $endYear) ||
        ($rowYear == $endYear && $rowMonth < $endMonth) ||
        ($rowYear == $endYear && $rowMonth == $endMonth && $rowDay < $endDay) ||
        ($rowYear == $endYear && $rowMonth == $endMonth && $rowDay == $endDay && $rowHour < $endHour) ||
        ($rowYear == $endYear && $rowMonth == $endMonth && $rowDay == $endDay && $rowHour == $endHour && $rowMinute < $endMinute) ||
        ($rowYear == $endYear && $rowMonth == $endMonth && $rowDay == $endDay && $rowHour == $endHour && $rowMinute == $endMinute && $rowSecond <= $endSecond);

    return $startComparison && $endComparison;
}


function getTurno($created_date)
{
    $date = new DateTime($created_date);
    $hour = (int)$date->format('H');
    $day = (int)$date->format('d');

    if ($hour >= 8 && $hour < 20) {
        return 'A';
    }
    // Si la hora es igual o mayor a 8 PM Y es el mismo día, es turno A del día siguiente
    elseif ($hour >= 20 && $day == $date->format('d')) {
        return 'A';
    }
    // En cualquier otro caso, es turno B
    else {
        return 'B';
    }
}


function updateStatusCount($status)
{
    global $status_counts;
    $status = trim($status);
    if ($status !== 'New' && $status !== '') {
        if (!isset($status_counts[$status])) {
            $status_counts[$status] = 0;
        }
        $status_counts[$status]++;
    }
}

function isValidDate($date)
{
    try {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) {
            return true;
        }

        if (strtotime($date) !== false) {
            return true;
        }

        return false;
    } catch (Exception $e) {
        return false;
    }
}

// Leer el archivo CSV
try {
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
                $account_name = $row[4];
                $owner_name = $row[3];
                //if($case_number=="00506493") print_r($row);
                // Formatear la fecha de "Created At"
                $created_at = $row[8];

                if (isValidDate($created_at)) {
                    $created_datetime = new DateTime($created_at);
                    $year = $created_datetime->format('Y');
                    $row[8] = $created_datetime->format('d-m-Y H:i:s');
                    if ($year < 2024) {
                        continue;
                    }

                    if (!isset($last_status[$case_number])) {
                        $last_status[$case_number] = [
                            'case_id' => $case_id,
                            'data' => array_slice($row, 1),
                        ];

                        if ($status === 'Closed' && !in_array($case_number, $closed_case_numbers)) {
                            $closed_case_numbers[] = $case_number;
                            $status_counts['Closed']++;
                        } else {
                            updateStatusCount($status);
                        }
                    }

                    // Fecha de cierre, si existe
                    $closed_date = $row[9] ?? '';
                    if (isValidDate($closed_date)) {
                        $closed_datetime = new DateTime($closed_date);
                        $row[9] = $closed_datetime->format('d-m-Y H:i:s');
                    }

                    
                    $startDateObj = new DateTime($startDate);
                    $endDateObj = new DateTime($endDate);
                    $rowDate = new DateTime($row[8]);
                    $startDay = $startDateObj->format('d');
                    $startMonth = $startDateObj->format('m');
                    $startYear = $startDateObj->format('Y');
                    $startHour = $startDateObj->format('H');
                    $startMinute = $startDateObj->format('i');
                    $startSecond = $startDateObj->format('s');

                    $endDay = $endDateObj->format('d');
                    $endMonth = $endDateObj->format('m');
                    $endYear = $endDateObj->format('Y');
                    $endHour = $endDateObj->format('H');
                    $endMinute = $endDateObj->format('i');
                    $endSecond = $endDateObj->format('s');

                    $rowDay = $rowDate->format('d');
                    $rowMonth = $rowDate->format('m');
                    $rowYear = $rowDate->format('Y');
                    $rowHour = $rowDate->format('H');
                    $rowMinute = $rowDate->format('i');
                    $rowSecond = $rowDate->format('s');



                    $startComparison =
                        ($rowYear > $startYear) ||
                        ($rowYear == $startYear && $rowMonth > $startMonth) ||
                        ($rowYear == $startYear && $rowMonth == $startMonth && $rowDay > $startDay) ||
                        ($rowYear == $startYear && $rowMonth == $startMonth && $rowDay == $startDay && $rowHour > $startHour) ||
                        ($rowYear == $startYear && $rowMonth == $startMonth && $rowDay == $startDay && $rowHour == $startHour && $rowMinute > $startMinute) ||
                        ($rowYear == $startYear && $rowMonth == $startMonth && $rowDay == $startDay && $rowHour == $startHour && $rowMinute == $startMinute && $rowSecond >= $startSecond);

                    $endComparison =
                        ($rowYear < $endYear) ||
                        ($rowYear == $endYear && $rowMonth < $endMonth) ||
                        ($rowYear == $endYear && $rowMonth == $endMonth && $rowDay < $endDay) ||
                        ($rowYear == $endYear && $rowMonth == $endMonth && $rowDay == $endDay && $rowHour < $endHour) ||
                        ($rowYear == $endYear && $rowMonth == $endMonth && $rowDay == $endDay && $rowHour == $endHour && $rowMinute < $endMinute) ||
                        ($rowYear == $endYear && $rowMonth == $endMonth && $rowDay == $endDay && $rowHour == $endHour && $rowMinute == $endMinute && $rowSecond <= $endSecond);


                    if ($startComparison && $endComparison) {
                        $codelcoExiste = false;
                        $amsaExiste = false;
                        $mvExiste = false;

                        foreach ($codelco_accounts as $clave => $valor) {
                            if (strpos(strtolower($account_name), strtolower($clave)) !== false) {
                                $codelcoExiste = true;
                                $row[4] = $valor;
                                break;
                            }
                        }

                        foreach ($amsa_accounts as $clave => $valor) {
                            if (strpos(strtolower($account_name), strtolower($clave)) !== false) {
                                $amsaExiste = true;
                                $row[4] = $valor;
                                break;
                            }
                        }

                        foreach ($mv_accounts as $clave => $valor) {
                            if (strpos(strtolower($account_name), strtolower($clave)) !== false) {
                                $mvExiste = true;
                                $row[4] = $valor;
                                break;
                            }
                        }


                        if ($codelcoExiste && in_array($owner_name, $allowed_owner_names)) {
                            $codelco_records[] = $row;
                        } elseif ($amsaExiste && in_array($owner_name, $allowed_owner_names)) {
                            $amsa_records[] = $row;
                        } elseif ($mvExiste && in_array($owner_name, $allowed_owner_names)) {
                            $mv_records[] = $row; 
                        }
                    }
                }
            }

            fclose($handle);
            $records_to_display = $last_status;
        } else {
            echo "No se pudo abrir el archivo CSV.";
        }
    } else {
        echo "El archivo $csv_file no se encuentra.";
    }
} catch (Exception $e) {
    echo "Ocurrió un error: " . $e->getMessage();
}

echo "<script>var statusCounts = " . json_encode($status_counts) . ";</script>";

//echo $amsa_records[1][7];

?>

<div class="table-container">
    <div class="table-responsive">
        <table id="ticketsTableCodelco" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Case Number</th>
                    <th>Account Name</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Closed Date</th>
                    <th>Owner Name</th>
                    <th>Description</th>
                    <th>Case Resolution</th>
                </tr>
            </thead>
            <tbody>
                <?php

                foreach ($codelco_records as $record) {
                    echo "<tr>";
                    echo "<td><a href='https://usa1.lightning.force.com/lightning/r/Case/{$record[0]}/view' target='_blank'>{$record[1]}</a></td>"; // CaseNumber
                    echo "<td>{$record[4]}</td>"; // AccountName
                    echo "<td>{$record[5]}</td>"; // Subject
                    echo "<td>{$record[2]}</td>"; // Status
                    echo "<td>{$record[8]}</td>"; // CreatedDate
                    echo "<td>{$record[9]}</td>"; // ClosedDate
                    echo "<td>{$record[3]}</td>"; // OwnerName
                    echo "<td>{$record[6]}</td>"; // Description
                    echo "<td>{$record[7]}</td>"; // CaseResolution
                    echo "</tr>";
                }

                foreach ($data as $key => $row) {
                    foreach ($row as $column => $value) {
                        if (isset($name_mapping[$value])) {
                            $data[$key][$column] = $name_mapping[$value];
                        }
                    }
                }




                ?>
            </tbody>
        </table>
    </div>

    <h1>
        <center>
            <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
            Tickets Turno - AMSA
            <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
        </center>
    </h1>

    <div class="table-responsive">
        <table id="ticketsTableAmsa" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Case Number</th>
                    <th>Account Name</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Closed Date</th>
                    <th>Owner Name</th>
                    <th>Description</th>
                    <th>Case Resolution</th>
                </tr>
            </thead>
            <tbody>
                <?php

                foreach ($amsa_records as $record) {
                    echo "<tr>";
                    echo "<td><a href='https://usa1.lightning.force.com/lightning/r/Case/{$record[0]}/view' target='_blank'>{$record[1]}</a></td>"; // CaseNumber
                    echo "<td>{$record[4]}</td>"; // AccountName
                    echo "<td>{$record[5]}</td>"; // Subject
                    echo "<td>{$record[2]}</td>"; // Status
                    echo "<td>{$record[8]}</td>"; // CreatedDate
                    echo "<td>{$record[9]}</td>"; // ClosedDate
                    echo "<td>{$record[3]}</td>"; // OwnerName
                    echo "<td>{$record[6]}</td>"; // Description
                    echo "<td>{$record[7]}</td>"; // CaseResolution
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<h1>
    <center>
        <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
        Tickets Turno - Manto Verde
        <img style="width: 1.5%;" src="https://companieslogo.com/img/orig/HEXA-B.ST-f7fd0700.png?t=1720244492" alt="User Avatar">
    </center>
</h1>

<div class="table-responsive">
        <table id="ticketsTableMv" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Case Number</th>
                    <th>Account Name</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Closed Date</th>
                    <th>Owner Name</th>
                    <th>Description</th>
                    <th>Case Resolution</th>
                </tr>
            </thead>
            <tbody>
                <?php

                foreach ($mv_records as $record) {
                    echo "<tr>";
                    echo "<td><a href='https://usa1.lightning.force.com/lightning/r/Case/{$record[0]}/view' target='_blank'>{$record[1]}</a></td>"; // CaseNumber
                    echo "<td>{$record[4]}</td>"; // AccountName
                    echo "<td>{$record[5]}</td>"; // Subject
                    echo "<td>{$record[2]}</td>"; // Status
                    echo "<td>{$record[8]}</td>"; // CreatedDate
                    echo "<td>{$record[9]}</td>"; // ClosedDate
                    echo "<td>{$record[3]}</td>"; // OwnerName
                    echo "<td>{$record[6]}</td>"; // Description
                    echo "<td>{$record[7]}</td>"; // CaseResolution
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>


<script>
    var tableCodelco = $('#ticketsTableCodelco').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "order": [
            [0, 'desc']
        ]
    });

    var tableAmsa = $('#ticketsTableAmsa').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "order": [
            [0, 'desc']
        ]
    });

    var tableMv = $('#ticketsTableMv').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "order": [
            [0, 'desc']
        ]
    });
</script>