<?php
require_once __DIR__ . '/../models/FaenaOasModel.php';
require_once __DIR__ . '/../core/MetricsTransformer.php';

class OasController
{

    protected $system;
    protected $faenaModel;

    public function __construct($system)
    {
        $this->system = $system;
        // Se crea la instancia del modelo pasando el objeto $system,
        // de modo que el modelo pueda obtener la conexión con $system->conectaDB()
        $this->faenaModel = new FaenaOasModel();
    }

    // Obtiene las faenas, adjunta las métricas y las transforma (si se requiere)
    public function getFaenasConMetricas($transform = false)
    {
        // Se obtiene la lista de faenas usando el objeto $system
        $faenas = $this->faenaModel->getFaenas($this->system);
        if (empty($faenas)) {
            return [];
        }
        // Extraemos los IDs de los servidores para consultar las métricas
        $serverIds = array_column($faenas, 'id_servidor');
        // Se obtienen las métricas para esos servidores
        $metricasData = $this->faenaModel->getMetricasForServerIds($this->system, $serverIds);

        // Se asocian las métricas a cada faena
        foreach ($faenas as &$faena) {
            $idServidor = $faena['id_servidor'];
            $faena['metricas'] = isset($metricasData[$idServidor]) ? $metricasData[$idServidor] : [];
            if ($transform) {
                // Se aplican las transformaciones definidas en MetricsTransformer
                $faena = MetricsTransformer::transform($faena);
            }
        }
        unset($faena);
        return $faenas;
    }
}
