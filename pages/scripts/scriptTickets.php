<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");
require_once("../../build/controller/controller-alerta.php");

$system = new systemClass();
$alertas = new alertas();
$faena = new faena();

// Inicializar las variables para los casos cerrados
$closed_case_numbers = [];  // Array vacío para almacenar los números de caso cerrados
$closed_count = 0;  // Contador para los casos cerrados

// Inicializar el arreglo para los estados
$status_counts = [];  // Array para almacenar el conteo de diferentes estados

$csv_file = "../../../globalData/tickets.csv";
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

            // Verificar si el caso ya ha sido procesado
            if (!isset($last_status[$case_number])) {
                $last_status[$case_number] = [
                    'case_id' => $case_id,
                    'data' => array_slice($row, 1),
                ];

                // Si el estado es 'Closed', agregar el caso a los casos cerrados
                if ($status === 'Closed' && !in_array($case_number, $closed_case_numbers)) {
                    $closed_case_numbers[] = $case_number;
                    $closed_count++;
                } else {
                    // Si el estado no es 'Closed', contar los diferentes estados
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
            $ultimoCaseNumber = $row[1];  // Guardar el último número de caso procesado
        }

        fclose($handle);  // Asegurarse de cerrar el archivo CSV después de procesarlo
    }
}

echo $ultimoCaseNumber;  // Mostrar el último número de caso procesado
/*
echo "<pre>";
print_r(str_getcsv($lastRow));  
echo "</pre>";
*/

?>
