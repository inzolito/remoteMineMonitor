<?php
require_once("../../build/controller/controller-functions.php");
require_once("../../build/controller/controller-faena.php");
require_once("../../build/controller/controller-alerta.php");

date_default_timezone_set('America/Santiago');

$system = new systemClass();
$faenaCl = new faena();
$system->validarSesion();
$conn = $system->conectaDB();

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// todas las metricas de los servidores oas
// SELECT * FROM `metricas_servidores` where id_servidor in (select id from servidores where tipo_servidor='OAS');

/*
--  query donde te tira todas los valores de las metricas de los servidores oas 


select * 
from metricas_servidores_valor msv join metricas_servidores ms on (ms.id = msv.id_metrica_servidor)
where id_metrica_servidor in (
SELECT id
FROM `metricas_servidores`
where id_servidor in (select id from servidores where tipo_servidor='OAS')
 );


 v2 



 select ms.id_servidor, 
            m.metrica, 
            msv.valor
from metricas_servidores_valor msv join metricas_servidores ms on (ms.id = msv.id_metrica_servidor) join metricas m on (ms.id_metrica=m.id)
where id_metrica_servidor in (
SELECT id
FROM `metricas_servidores`
where id_servidor in (select id from servidores where tipo_servidor='OAS')
 );


 select ms.id_servidor, m.metrica, msv.valor 
 from metricas_servidores_valor msv join metricas_servidores ms 
 
 on (ms.id = msv.id_metrica_servidor) join metricas m on (ms.id_metrica=m.id) 
 
 where id_metrica_servidor in ( SELECT id FROM `metricas_servidores` where id_servidor in (select id from servidores where id=42) );

 */

// 1. Consulta principal: obtener faenas y servidores de tipo 'OAS'
$queryFaenas = "
    SELECT 
        f.id          AS id_faena,
        f.faena       AS nombre_faena,
        s.id          AS id_servidor,
        s.nombre      AS nombre_servidor
    FROM faenas f
    JOIN faenas_servidores fs ON f.id = fs.id_faena
    JOIN servidores s         ON fs.id_servidor = s.id
    WHERE s.tipo_servidor = 'OAS';
";

$resultFaenas = $conn->query($queryFaenas);
if ($resultFaenas === false) {
    die("Error en la consulta: " . $conn->error);
}

// 2. Guardamos los resultados en un array $faenas
$faenas = [];
if ($resultFaenas->num_rows > 0) {
    $faenas = $resultFaenas->fetch_all(MYSQLI_ASSOC);
}

// 3. Si tenemos faenas, sacamos los IDs de servidores para hacer la segunda consulta
if (!empty($faenas)) {
    // Extrae todos los id_servidor de $faenas
    $serverIds = array_column($faenas, 'id_servidor');
    // Elimina duplicados si fuera necesario
    $serverIds = array_unique($serverIds);
    // Prepara la lista para el IN de SQL
    $serverIdsList = implode(",", $serverIds);

    /*
    El problema con esta metrica es que no obtiene las metricas mas recientes sino el historico, por lo que luego de un tiempo hace que colapse la variables que guarda el contenido de la query
    // 4. Consulta para obtener SOLO las métricas más recientes por servidor y métrica
    $queryMetricas = "
        select ms.id_servidor, m.metrica, msv.valor 
        from metricas_servidores_valor msv join metricas_servidores ms on (ms.id = msv.id_metrica_servidor) 
        join metricas m on (ms.id_metrica=m.id) 
        
        where id_metrica_servidor in 
             ( SELECT id 
               FROM `metricas_servidores` 
               where id_servidor in (select id from servidores where id in ($serverIdsList)) 
             )
    ";*/

    // 4. Consulta para obtener SOLO las métricas más recientes por servidor y métrica
    $queryMetricas = "
    SELECT 
        ms.id_servidor, 
        m.metrica, 
        msv.valor 
    FROM metricas_servidores_valor msv
    JOIN (
        SELECT 
            id_metrica_servidor, 
            MAX(created_at) AS max_created
        FROM metricas_servidores_valor
        GROUP BY id_metrica_servidor
    ) AS sub 
        ON msv.id_metrica_servidor = sub.id_metrica_servidor 
        AND msv.created_at = sub.max_created
    JOIN metricas_servidores ms ON ms.id = msv.id_metrica_servidor
    JOIN metricas m ON m.id = ms.id_metrica
    WHERE ms.id_servidor IN ($serverIdsList)
    ";


    $resultMetricas = $conn->query($queryMetricas);
    if ($resultMetricas === false) {
        die("Error en la consulta de métricas: " . $conn->error);
    }

    // 5. Construimos un array indexado por id_servidor => [metrica => valor]
    $metricasPorServidor = [];
    while ($row = $resultMetricas->fetch_assoc()) {
        $idServidor     = $row['id_servidor'];
        $nombreMetrica  = $row['metrica'];
        $valorMetrica   = $row['valor'];

        if (!isset($metricasPorServidor[$idServidor])) {
            $metricasPorServidor[$idServidor] = [];
        }
        // Guardamos la métrica como clave y el valor como valor
        $metricasPorServidor[$idServidor][$nombreMetrica] = $valorMetrica;
    }

    // 6. Agregamos el sub-array 'metricas' a cada faena/servidor
    foreach ($faenas as &$faena) {
        $idServidor = $faena['id_servidor'];
        // Si existen métricas para este servidor, las asignamos; de lo contrario, un array vacío
        $faena['metricas'] = isset($metricasPorServidor[$idServidor])
            ? $metricasPorServidor[$idServidor]
            : [];
    }
    // Rompemos la referencia del foreach
    unset($faena);
}

// Convierte el array a JSON
$faenasJSON = json_encode($faenas);

// Muestra un bloque <script> para imprimir en la consola del navegador
/*echo "<script>
        var faenas = $faenasJSON;
        console.log(faenas);
      </script>";
*/



