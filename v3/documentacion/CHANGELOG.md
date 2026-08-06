# Historial de Cambios - Monitoreo V3

- **2026-07-24**: **Estandarización Visual de Estados de Alerta (Danger)**:
    - **Estándar Visual Crítico**: Definición formal en la documentación (`PROCESOS_IMPORTANTES.md`) del estilo `danger`: fondo rojo sólido (`bg-red-600`), texto blanco puro (`text-white`), badging invertido y animación de parpadeo suave (`animate-pulse`).
    - **Refactorización Componente JAMS**: Corrección en `MonitoreoSite.tsx` para hacer dinámico el bloque de Reinicios JAMS y aplicar el estilo `danger` sólido de forma coherente con la UI.
    - **Optimización de Frescura por Métricas Clave**: Refinamiento en `api/metrics.php` para calcular la frescura (`system.freshness`) estrictamente basada en el timestamp de `system.cpu.load` con umbral de 2 minutos, previniendo falsos positivos por métricas virtuales.

- **2026-02-04**: **Refactorización del Sistema de Alertas y Soporte para Secundarios**:
    - **Evaluación en Tiempo de Lectura**: Implementación de re-evaluación dinámica en `api/metrics.php` para que los colores del dashboard reflejen cambios en las reglas al instante.
    - **Soporte para Servidores Secundarios**: Corrección de bug que impedía el disparo de alertas en nodos secundarios mediante la resolución y paso explícito de IDs de métrica al evaluador.
    - **Exclusión Selectiva**: Configuración de reglas para ignorar alertas de CPU en servidores secundarios, enfocando el monitoreo solo en RAM y Disco según requerimiento.
    - **Mejoras en el Alert Manager (Frontend)**:
        - Implementación de estado optimista para evitar la re-aparición de alertas recién marcadas en revisión (race condition fix).
        - Notificaciones inteligentes: Pantalla completa en vistas de monitoreo y toasts no intrusivos en el resto del panel.
        - Silenciado rápido desde notificaciones persistentes.

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
