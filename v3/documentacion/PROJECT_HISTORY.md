# Historial de Cambios y Arquitectura de Alertas (V3.3)

## Fecha: 2026-02-02

### 1. Refactorización de Reglas a Multinivel (Avanzado)
Se ha rediseñado completamente la lógica de evaluación para permitir una granularidad infinita.
*   **Nueva Estructura**: La tabla `alert_rules` ahora es una lista de condiciones individuales en lugar de una fila con dos umbrales.
*   **Campos Clave**: `metric_id`, `rule_type`, `threshold_value`, `alert_category` y `description`.
*   **Capacidad**: Ahora se pueden definir múltiples estados para una misma métrica (ej. un `warning` al 70%, un `danger` al 85% y un `critical` al 95%), cada uno con su propia descripción personalizada que se guardará en la tabla de alertas.
*   **Escalabilidad**: Se introdujo un sistema de pesos/severidad (`ok < warning < danger < critical < fatal`) para que el motor siempre reporte el estado más grave detectado entre todas las reglas que coincidan.

### 2. Principio Fundamental de Diseño (CRÍTICO)
Se ha establecido y documentado que **toda la lógica de alertas y evaluación de métricas reside exclusivamente en el Backend y la Base de Datos**.
*   **PROHIBIDO**: Añadir lógica de detección de estados "Danger" o "Warning" en el código del Frontend (React/TSX).
*   **PROHIBIDO**: Generar alertas locales (con IDs negativos o temporales) en el cliente.
*   **FUENTE DE VERDAD**: El sistema solo debe reaccionar a los registros presentes en la tabla `alerts`. Si una métrica está en rojo visualmente pero no existe un registro `active` en `alerts`, la pantalla completa **no debe mostrarse**.

### 3. Ciclo de Vida de las Alertas
1.  **Detección (Backend)**: El archivo `api/metrics.php` y la clase `MetricsEvaluator.php` procesan los datos entrantes y comparan contra `alert_rules`. Si hay una anomalía, se inserta en la tabla `alerts` con `status = 'active'`.
2.  **Notificación FS (Frontend)**: El `AlertManager.tsx` consulta periódicamente la API. Solo si encuentra una alerta con `status = 'active'`, despliega la superposición de pantalla completa.
3.  **Revisión (Usuario)**: Cuando el usuario presiona "MARCAR EN REVISIÓN":
    *   Se envía el `id` de la alerta y el `user_id` del usuario actual al backend (`api/alerts.php`).
    *   La base de datos actualiza el registro a `status = 'acknowledged'` y guarda el `acknowledged_at`.
4.  **Persistencia y Visibilidad**:
    *   Una vez marcada como `'acknowledged'`, la alerta **debe desaparecer** automáticamente de la pantalla completa (FS Overlay) para permitir el uso del sistema.
    *   La alerta **debe permanecer visible** en la campana de notificaciones (`NotificationBell.tsx`) para trazabilidad, mostrando quién la revisó, hasta que sea marcada como `solved`.

### 3. Cambios Realizados en esta Sesión
*   **Reversión de Detección Local**: Se eliminó el bloque de código en `MonitoreoSite.tsx` que intentaba calcular riesgos de "Idle Queries", "Max ID" y "Backup Diff" localmente.
*   **Limpieza de AlertManager**: Se simplificó el componente para que sea un consumidor puro de la API, eliminando estados internos como `acknowledgedKeys` que causaban inconsistencias al refrescar.
*   **Sincronización de Base de Datos**: Se ejecutó una limpieza manual de alertas huérfanas o erróneas causadas por las pruebas de lógica local (específicamente `db.idle_queries`).
*   **Despliegue**: Se regeneró el build de producción (`npm run build`) para asegurar que el cliente no mantenga lógica residual en caché.

### 4. Estructura de Datos de Alertas (Referencia)
| Campo | Propósito |
| :--- | :--- |
| `status` | `'active'` (Bloquea pantalla), `'acknowledged'` (Solo en campana), `'solved'` (Histórico) |
| `user_id` | ID del usuario que presionó el botón de revisión. |
| `acknowledged_at` | Timestamp del momento exacto del clic. |
| `metric_key` | Clave que vincula la alerta con la métrica que la originó. |
