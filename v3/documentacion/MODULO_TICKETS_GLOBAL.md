# Módulo de Tickets Global

## Descripción General
El **Módulo de Tickets Global** (`TicketsPage.tsx`) es una vista principal del sistema diseñada para centralizar, monitorear y gestionar todos los tickets originados en la plataforma Salesforce (`rmmsalesforce.sf_cases`). A diferencia de otros módulos (como el Turno 7x7) que restringen la visibilidad según el agente asignado, este módulo recupera **la totalidad** de los tickets creados en el año en curso, sin restricciones de `OwnerId`.

## Arquitectura

### 1. Frontend (`frontend/src/pages/TicketsPage.tsx`)
- **Grid Layout 60/40**: Reutiliza la estética probada del módulo de Turnos. La tabla de tickets ocupa el 60% del espacio principal, mientras que la sección derecha (40%) muestra el panel interactivo y el gráfico estadístico.
- **Gráfico de Dona (Recharts)**: Representación visual dinámica de la carga de tickets distribuida en tiempo real según su estado. Incluye una leyenda en formato de grilla con colores y contadores absolutos de tickets, que no oculta los estados en valor 0 (garantizando visibilidad constante de los SLAs).
- **Tarjetas de Estadísticas (Stats Cards)**: 
  - `TOTAL ANUAL`
  - `WORKING`
  - `SEEKING`
  - `ESCALADO PD`
  - `ESCALADO GT`
  - `CLOSED`
  - `ASIGNADO A S.A.` (Support American Queue)
- **Orden de visualización inteligente**: El motor de la tabla renderiza los tickets priorizando aquellos que se encuentran en estado abierto (Working, Seeking, Assigned, Escalado), desplazando obligatoriamente los tickets en estado `Closed` a la parte inferior del registro para mejorar el enfoque del operador.
- **Paginación del Lado del Servidor**: Construido con React Query. Aliviana el peso del frontend delegando la gestión de memoria y el límite de filas (`limit=30`) al motor MySQL.
- **Portal de Modal (React `createPortal`)**: El modal de vista detallada de tickets (con sus comentarios y resolución) se renderiza fuera de la jerarquía DOM principal. Esto previene recortes visuales o problemas de `overflow-hidden` inherentes a las tablas con `sticky headers`.

### 2. Backend (`api/tickets.php`)
- **Acceso Global**: Fue modificado para abolir el filtro `$in_clause` de `OwnerId`. Extrae la totalidad del registro histórico de `sf_cases` donde `IsDeleted = 0` y la fecha de creación corresponda al año actual.
- **Extracción de Comentarios (N+1 resuelto)**: La captura de comentarios de Salesforce (`sf_case_comments`) se realiza mediante un `LEFT JOIN` secundario sobre el subconjunto de tickets paginados. Esto evita saturar el motor SQL intentando mapear miles de comentarios de forma simultánea.
- **Contadores (Stats)**: La respuesta del servidor (`json_encode`) entrega la sumatoria exacta de cada estado con un formato pre-procesado para evitar cálculos manuales masivos en el cliente React.
- **Seguridad (JWT)**: Todo el consumo del endpoint está protegido por `auth_helper.php`, obligando al frontend a despachar el `Token Bearer` alojado en el `localStorage`.

## Funcionalidades Próximas o Integraciones
- Se espera que el ecosistema se fusione en el futuro con un Bot/Agente (Gemini AI) que permitirá al usuario indagar en el historial global de tickets para detectar resoluciones o "troubleshootings" similares basados en el síntoma de un caso nuevo.
