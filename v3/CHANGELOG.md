# Historial de Cambios - Monitoreo V3

- **2026-02-02**: Limpieza profunda de métricas legadas (`ramPercent`, `porcentajeCpu`, `reiniciosJAMS`).
- **2026-02-02**: Normalización de mapeos en `api/metrics.php` hacia nomenclatura oficial V3.
- **2026-02-02**: Eliminación de métricas duplicadas de servicios (`system.services.*`) en `server_app_metrics`.
- **2026-02-02 18:05**: Rediseño completo de la UI de Alertas en SuperAdmin (Agrupación por métrica y sección de reglas residuales).
- **2026-02-02 18:05**: Centralización de lógica de normalización en `MetricsEvaluator` (Single Source of Truth).
- **2026-02-02 18:05**: Soporte para conteo de equipos en tiempo real para `app.repc` y `app.connectivity.trucks`.
- **2026-02-02 18:05**: Implementación de parser para formato de antigüedad de backups `D-HH:MM:SS`.
- **2026-02-02 18:05**: Corrección de desfase horario forzando `America/Santiago` en el motor de evaluación.
- **2026-02-02 18:05**: Soporte para decimales en umbrales de tiempo (ej: `1.2h`) en `parseTimePeriod`.
- **2026-02-02 18:10**: **Consolidación de Estructura de Datos V3**:
    - **Tabla `metrics`**: Normalización de nombres únicos (`UNIQUE KEY name`) y campos de visualización.
    - **Tabla `alert_rules`**: Refinamiento de vínculos por `metric_id` con integridad referencial y soporte para `metric_pattern`.
    - **Tabla `alerts`**: Limpieza de registros huérfanos y estandarización de estados (`active`, `solved`).
- **2026-02-02 17:30**: Independencia total de DB: Remoción de rastro de `checksupport` en lógica de evaluador.
- **2026-02-02 17:35**: Creación de documentación de modelo de datos (`DATABASE_V3_MODEL.md`).
