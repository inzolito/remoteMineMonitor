<?php
class MetricsTransformer
{
    public static function transform($faena)
    {
        $metricas = $faena['metricas'];

        // Transformación para Disco Duro
        $faena['diskPorcentaje'] = 0;
        $faena['discoUsado'] = "";
        $faena['discoTotal'] = "";
        if (isset($metricas['Espacio Total Disco Duro']) && isset($metricas['Espacio Utilizado Disco Duro'])) {
            $lineaDiscoTotal = trim($metricas['Espacio Total Disco Duro']);
            $lineaDiscoUtilizado = trim($metricas['Espacio Utilizado Disco Duro']);
            $faena['discoTotal'] = $lineaDiscoTotal;
            $faena['discoUsado'] = $lineaDiscoUtilizado;
            $discoTotalValue = floatval($lineaDiscoTotal);
            $discoUtilizadoValue = floatval($lineaDiscoUtilizado);
            $unidadTotal = strtoupper(substr($lineaDiscoTotal, -1));
            $unidadUtilizado = strtoupper(substr($lineaDiscoUtilizado, -1));
            if ($unidadTotal === 'M') {
                $discoTotalValue /= 1024;
            } elseif ($unidadTotal === 'T') {
                $discoTotalValue *= 1024;
            }
            if ($unidadUtilizado === 'M') {
                $discoUtilizadoValue /= 1024;
            } elseif ($unidadUtilizado === 'T') {
                $discoUtilizadoValue *= 1024;
            }
            if ($discoTotalValue > 0) {
                $faena['diskPorcentaje'] = (int)(($discoUtilizadoValue * 100) / $discoTotalValue);
            }
        }

        // Transformación para Particiones Disco Duro
        $faena['lineasParticiones'] = [];
        if (isset($metricas['Particiones Disco Duro'])) {
            $particionesRaw = trim($metricas['Particiones Disco Duro']);
            if (!empty($particionesRaw)) {
                $faena['lineasParticiones'] = array_values(array_filter(explode("\n", $particionesRaw), 'strlen'));
            }
        }

        // Transformación para Memoria RAM
        $faena['ramPorcentaje'] = 0;
        $faena['ramTotalStr'] = "";
        $faena['ramUsedStr'] = "";
        if (isset($metricas['Memoria Ram Utilizada']) && isset($metricas['Memoria Ram Total'])) {
            $lineaRamUtilizada = trim($metricas['Memoria Ram Utilizada']);
            $lineaRamTotal = trim($metricas['Memoria Ram Total']);
            $faena['ramUsedStr'] = $lineaRamUtilizada;
            $faena['ramTotalStr'] = $lineaRamTotal;
            $ramUtilizada = (stripos($lineaRamUtilizada, 'mi') !== false) ? floatval($lineaRamUtilizada) / 1024 : floatval($lineaRamUtilizada);
            $ramTotal = (stripos($lineaRamTotal, 'mi') !== false) ? floatval($lineaRamTotal) / 1024 : floatval($lineaRamTotal);
            if ($ramTotal > 0) {
                $faena['ramPorcentaje'] = round(($ramUtilizada * 100) / $ramTotal);
            }
        }

        // Transformación para "top c Lectura"
        $faena['lineasTopCLectura'] = [];
        if (isset($metricas['top c Lectura'])) {
            $topCLecturaRaw = trim($metricas['top c Lectura']);
            if (!empty($topCLecturaRaw)) {
                $faena['lineasTopCLectura'] = array_values(array_filter(explode("\n", $topCLecturaRaw), 'strlen'));
            }
        }

        // Transformación para Load Average
        $faena['loadAverageArray'] = [];
        if (isset($metricas['Load Average'])) {
            $loadAverageString = $metricas['Load Average'];
            if ($loadAverageString !== "") {
                $faena['loadAverageArray'] = array_map('floatval', array_map('trim', explode(',', $loadAverageString)));
            }
        }

        // Ejemplo de transformación para Tclas Max (puedes extender para otras métricas)
        $faena['tclasValue'] = 0;
        if (isset($metricas['Tclas Max Tiempo Clasificar Eventos'])) {
            $tclasRaw = $metricas['Tclas Max Tiempo Clasificar Eventos'];
            $lineasTclas = explode("\n", $tclasRaw);
            if (count($lineasTclas) >= 3) {
                $rowData = explode("|", $lineasTclas[2]);
                if (count($rowData) == 2) {
                    $faena['tclasValue'] = floatval(trim($rowData[0]));
                }
            }
        }

        // Agrega aquí otras transformaciones necesarias (Tclas Min, TturnA, Equipos desconectados, Snapchots, etc.)

        // Contadores para algunos elementos (puedes ajustarlos según necesites)
        $faena['numEquipos'] = isset($faena['equipos']) ? count($faena['equipos']) : 0;
        $faena['countSnapchots'] = isset($faena['snapchots']) ? count($faena['snapchots']) : 0;
        $faena['countEventos'] = isset($faena['eventosGenerados']) ? count($faena['eventosGenerados']) : 0;
        $faena['countEventosSinClasificarActual'] = isset($faena['eventosSinClasificarActual']) ? count($faena['eventosSinClasificarActual']) : 0;
        $faena['countEventosSinClasificarAnterior'] = isset($faena['eventosSinClasificarAnterior']) ? count($faena['eventosSinClasificarAnterior']) : 0;

        return $faena;
    }
}
?>