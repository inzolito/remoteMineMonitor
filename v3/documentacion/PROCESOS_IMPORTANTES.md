# Procesos Importantes y Arquitectura Lógica - Monitoreo V3

Este documento centraliza las explicaciones de lógicas críticas que deben mantenerse estrictas para asegurar el funcionamiento dinámico y escalable del sistema.

---

> [!CAUTION]
> **ADVERTENCIA CRÍTICA: RE-EVALUACIÓN MASIVA**
> JAMÁS ejecutar `re_evaluate_metrics.php` o cualquier script de re-análisis masivo de métricas sin filtros estrictos de sitios activos (`status = 1`).
> Ejecutar esto de forma global dispara alertas para cientos de servidores (incluyendo faenas deshabilitadas o en standby), causando tormentas de notificaciones y alarmas sonoras en la oficina de monitoreo.

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

## 2. Detección de Servidores Caídos (Métrica Virtual)

### Objetivo
Identificar de forma robusta cuando un servidor deja de reportar datos (se queda "sin lectura") y disparar una alerta automáticamente, sin depender de procesos o scripts externos (CRONs).

### El Flujo de Datos

1.  **Registro Centralizado (`servers.updated_at`)**:
    *   Cada vez que `api/metrics.php` recibe *cualquier* dato de un servidor, actualiza la columna `updated_at` de ese servidor en la tabla `servers`. Este es el indicador más fiable de actividad.

2.  **Inyección Dinámica en Tiempo de Lectura**:
    *   Durante la carga del Dashboard (`api/metrics.php` método GET), el sistema itera sobre los servidores activos.
    *   Para cada servidor, extrae la fecha `updated_at`, la formatea (`Y-m-d_H-i-s`) e inyecta una **métrica virtual** llamada `system.freshness`.

3.  **Evaluación (`MetricsEvaluator`)**:
    *   El evaluador procesa esta métrica virtual contra la regla definida en `alert_rules` (por defecto: patrón `system.freshness`, operador `AGE_GREATER_THAN`, límite `5m`).
    *   Si el tiempo transcurrido desde la última lectura supera los 5 minutos, el evaluador genera e inserta automáticamente una alerta en estado `active` en la base de datos.
    *   Cuando el servidor vuelve a conectarse, la fecha se actualiza, la evaluación vuelve a ser `ok`, y el evaluador auto-resuelve la alerta (cambiándola a `solved`).

### Reglas de Oro para este Proceso
*   **NO USAR CRONs**: Toda la gestión del estado "offline" debe canalizarse a través de este evaluador dinámico, evitando dispersar la lógica de alertas en scripts secundarios de sistema operativo.
*   **Tolerancia Centralizada**: El tiempo para considerar un servidor como caído se ajusta editando la regla en `alert_rules`, no modificando el código.

---

---

## 3. Estándar Visual de UI para Estados de Alerta (Danger / Warning / OK)

### Objetivo
Garantizar una experiencia visual consistente e inconfundible en todo el Dashboard cuando un componente entra en estado crítico o de advertencia.

### Reglas de Estilo por Estado

#### 1. Estado Crítico (`danger`)
*   **Fondo**: Rojo sólido prioritario (`bg-red-600` en tema claro, `dark:bg-red-700` en tema oscuro).
*   **Texto**: Texto principal y etiquetas secundarias en blanco puro o rojo extremadamente claro (`text-white`, `text-red-100`).
*   **Badges / Píldoras de Conteo**: Fondo blanco con texto en rojo oscuro (`bg-white text-red-700`).
*   **Animación**: Efecto de parpadeo suave activo (`animate-pulse`).
*   **Bordes**: Tono rojo sólido acorde (`border-red-700` / `dark:border-red-800`).

#### 2. Estado Advertencia (`warning`)
*   **Fondo**: Tono ámbar/amarillo suave (`bg-amber-50` / `dark:bg-amber-950/20`).
*   **Texto y Bordes**: Tono ámbar/naranja legibles (`text-amber-700`, `border-amber-200`).

#### 3. Estado Normal (`ok` / `success`)
*   **Fondo**: Esmeralda/Verde relajado (`bg-[#f0fdf4]` / `dark:bg-emerald-950/20`).
*   **Texto y Bordes**: Verde corporativo (`text-[#15803d]`, `border-[#dcfce7]`).

---

## 4. Gestión de Traspasos de Sub-Turno

> [!IMPORTANT]
> **REGLA DE SEGURIDAD: BOTÓN "CONFIRMAR TRASPASO"**
> El botón de "Confirmar Traspaso" en el modal de traspaso de sub-turno (`SubShiftHandoff.tsx`) debe permanecer **inhabilitado**.
> El agente de IA **JAMÁS** debe habilitar o modificar este botón ni su lógica asociada en la base de datos sin preguntar previamente al usuario y recibir una **confirmación explícita por escrito**.

---

*(Más procesos se agregarán aquí en el futuro)*


