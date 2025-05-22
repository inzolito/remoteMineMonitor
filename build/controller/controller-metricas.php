<?php
require_once("controller-functions.php");

class Metricas
{
    private $mysqli;

    public function __construct()
    {
        $system = new systemClass();
        $this->mysqli = $system->conectaDB();
    }

    // 🔹 Agregar nueva métrica
    public function agregarMetrica($simbolo)
    {
        // Convertir el símbolo en nombre de métrica
        $nombre = preg_replace('/([a-z])([A-Z])/', '$1 $2', $simbolo);

        // Verificar si la métrica ya existe
        $query = "SELECT id FROM metricas WHERE simbolo = ?";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("s", $simbolo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            return "La métrica ya existe.";
        }

        // Insertar la nueva métrica
        $query = "INSERT INTO metricas (simbolo, nombre) VALUES (?, ?)";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("ss", $simbolo, $nombre);
        $stmt->execute();

        return "Métrica agregada correctamente.";
    }

    // 🔹 Mostrar métricas de una faena
    public function mostrarMetricasFaena($id_faena)
    {
        $query = "
            SELECT m.id, m.simbolo, m.nombre
            FROM metricas m
            JOIN metricas_servidores ms ON m.id = ms.id_metrica
            WHERE ms.id_faena = ?";

        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("i", $id_faena);
        $stmt->execute();
        $resultado = $stmt->get_result();

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    // 🔹 Mostrar últimos valores de métricas en una faena
    function obtenerUltimosValoresServidor($id_servidor)
    {
        $system = new systemClass();
        $mysqli = $system->conectaDB();
        /*   
        $query = "
            SELECT ms.id_servidor, m.simbolo AS metrica, msv.valor, msv.updated_at
            FROM metricas_servidores_valor msv
            JOIN metricas_servidores ms ON ms.id = msv.id_metrica_servidor
            JOIN metricas m ON ms.id_metrica = m.id
            WHERE ms.id IN (
                SELECT id FROM metricas_servidores WHERE id_servidor = " . $id_servidor . "
            )
            AND msv.updated_at = (
                SELECT MAX(updated_at) FROM metricas_servidores_valor WHERE id_metrica_servidor = msv.id_metrica_servidor
            )
            ORDER BY msv.updated_at DESC
        ";
        */

        $metricasServidorQuery = "select id from metricas_servidores where id_servidor=" . $id_servidor;
        $metricasServidorDatos = $mysqli->query($metricasServidorQuery);
        //echo $metricasServidorQuery;
        $data = [];

        if ($metricasServidorDatos->num_rows > 0) {
            while ($row = $metricasServidorDatos->fetch_assoc()) {

                $valorMetricaServidorQuery = "select ms.id id_metrica_servidor, msv.id id_metrica_servidor_valor, ms.id_faena, ms.id_servidor, msv.created_at, msv.updated_at, msv.deleted_at,m.simbolo,m.metrica, msv.valor 
                                            from metricas_servidores ms join metricas_servidores_valor msv on (msv.id_metrica_servidor=ms.id) 
                                            join metricas m on (ms.id_metrica=m.id)
                                            where ms.id=" . $row['id'] . "
                                            order by msv.updated_at desc
                                            limit 1";

               
                $valorMetricaServidorDatos = $mysqli->query($valorMetricaServidorQuery);
                //echo $valorMetricaServidorQuery;
                 

                if ($valorMetricaServidorDatos->num_rows > 0) {
                    while ($valorFinal = $valorMetricaServidorDatos->fetch_assoc()) {
                        $data[$valorFinal['simbolo']] = $valorFinal['valor'];
                    }
                }
            }
        }

        return $data;
    }



    // 🔹 Mostrar últimos valores de métricas por tipo de servidor
    public function mostrarUltimosValoresPorTipo($id_faena, $tipo)
    {
        $query = "
            SELECT m.simbolo, m.nombre, s.tipo, mv.valor, mv.fecha
            FROM metricas m
            JOIN metricas_servidores ms ON m.id = ms.id_metrica
            JOIN metricas_servidores_valor mv ON ms.id = mv.id_metrica_servidor
            JOIN servidores s ON ms.id_servidor = s.id
            WHERE ms.id_faena = ? AND s.tipo = ?
            ORDER BY mv.fecha DESC
            LIMIT 1";

        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("is", $id_faena, $tipo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }
}
