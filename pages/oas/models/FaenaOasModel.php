<?php
// /app/models/FaenaModel.php

class FaenaModel
{

    // Obtiene las faenas (con sus servidores de tipo OAS)
    public function getFaenas($system)
    {
        $conn = $system->conectaDB();
        if ($conn->connect_error) {
            die("Error de conexión: " . $conn->connect_error);
        }
        $query = "
            SELECT 
                f.id AS id_faena,
                f.faena AS nombre_faena,
                s.id AS id_servidor,
                s.nombre AS nombre_servidor
            FROM faenas f
            JOIN faenas_servidores fs ON f.id = fs.id_faena
            JOIN servidores s ON fs.id_servidor = s.id
            WHERE s.tipo_servidor = 'OAS'
        ";
        $result = $conn->query($query);
        if (!$result) {
            throw new Exception("Error en la consulta de faenas: " . $conn->error);
        }
        return ($result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Obtiene las métricas para los servidores indicados
    public function getMetricasForServerIds($system, $serverIds)
    {
        $conn = $system->conectaDB();
        if ($conn->connect_error) {
            die("Error de conexión: " . $conn->connect_error);
        }
        $serverIdsList = implode(",", array_unique($serverIds));
        $query = "
            SELECT ms.id_servidor, m.metrica, msv.valor 
            FROM metricas_servidores_valor msv 
            JOIN metricas_servidores ms ON ms.id = msv.id_metrica_servidor 
            JOIN metricas m ON ms.id_metrica = m.id
            WHERE ms.id_servidor IN ($serverIdsList)
        ";
        $result = $conn->query($query);
        if (!$result) {
            throw new Exception("Error en la consulta de métricas: " . $conn->error);
        }
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['id_servidor']][$row['metrica']] = $row['valor'];
        }
        return $data;
    }
}
