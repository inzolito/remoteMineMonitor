# Procesos Importantes y Arquitectura Lógica - Monitoreo V3

Este documento centraliza las explicaciones de lógicas críticas que deben mantenerse estrictas para asegurar el funcionamiento dinámico y escalable del sistema.

---

## 1. Evaluación de Alertas en Tiempo Real (No-Hardcoding)

### Objetivo
Que los colores y estados de alerta del Dashboard (Verde/Amarillo/Rojo) respondan **instantanéamente** a los cambios realizados en el panel de SuperAdmin, sin depender de lógica escrita en el código PHP o React.

### El Flujo de Datos (Ejemplo: Disco Duro, Conteo de Equipos, Backups, JAMS o Scripts)

1.  **Configuración (DB `alert_rules`)**:
    *   Las reglas se definen por `metric_id` (ej: ID para `system.disk.percent`, `app.repc`, `backup.daily.status`, `app.jams.service` o `app.fms.active_scripts`) o `metric_pattern`.
    *   Se especifica un operador (`>`, `<` , `BETWEEN`, etc.) y un valor de umbral (`threshold_value`).

2.  **Read-Time Evaluation (Backend `api/metrics.php`)**:
    *   **Carga Dinámica**: El sistema NO tiene escrito `if ($disco > 90)`. En su lugar, carga TODAS las reglas de la tabla `alert_rules` al momento de la petición.
    *   **Mapeo de Métricas**: Al iterar los servidores, el sistema identifica el valor actual de la métrica y su ID correspondiente.
    *   **Evaluación Genérica**: Se llama a la función `evaluate()`, la cual compara el valor de la métrica contra la regla cargada desde la DB usando un motor de comparación genérico.

3.  **Independencia del Código**:
    *   Si se cambia un umbral en el SuperAdmin, el Backend lee el nuevo valor de la DB en la siguiente petición.
    *   El Dashboard se actualiza visualmente de inmediato.

### Reglas de Oro para este Proceso
*   **NUNCA** escribir comparaciones numéricas fijas en el Frontend (`MonitoreoSite.tsx`).
*   **NUNCA** escribir lógica de "si es mayor a X" en el Backend fuera del `MetricsEvaluator`.
*   **SIEMPRE** pasar el `metric_id` al evaluador para asegurar que las reglas vinculadas por el SuperAdmin funcionen correctamente en todos los servidores (Primario, Secundario, etc.).

---

*(Más procesos se agregarán aquí en el futuro)*
