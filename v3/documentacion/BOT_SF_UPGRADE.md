# Resumen Técnico: Upgrade Panel de Administración Salesforce (BOT SF)

Este documento resume la implementación y evolución del módulo **BOT SF** dentro del panel `SuperAdmin` para el próximo agente/desarrollador.

## 1. Contexto del Servicio
- **Propósito**: Monitoreo y gestión de tickets sincronizados desde Salesforce.
- **Daemon de Sincronización**: `/home/jigsaw/monitoreoRemoto/RMMSF/sync_daemon.py`.
- **Control del Servicio**: `/var/www/monitoreoLaboratorio/v3/scripts/bot-sf-control.sh`.
- **Estado**: Producción.

## 2. Arquitectura de Datos
El bot utiliza una base de datos dedicada para evitar colisiones con el sistema principal.
- **DB**: `rmmsalesforce` (Usuario: `jigsaw` / Pass: `Jigsaw1`).
- **Tablas Clave**:
    - `sf_users`: Lista de usuarios (dueños de tickets). Campo crítico: `is_active_stats` (booleano para habilitar monitoreo).
    - `sf_cases`: Tabla maestra de tickets (vinculada por `OwnerId` y `AccountId`).
    - `sf_accounts`: Información de empresas/faenas. Campo clave: `internal_faena_alias`.
    - `sf_case_comments`: Historial de comentarios vinculado a `ParentId` (CaseId).

## 3. Backend (api/admin.php)
Se implementaron/modificaron los siguientes endpoints:
- `bot_sf_status`: Estado del daemon y métricas globales de sincronización.
- `bot_sf_users`: Lista de usuarios + la cola especial **South American Support Q**.
- `bot_sf_user_tickets`: Tickets de un usuario/cola con `LEFT JOIN` a `sf_accounts`.
- `bot_sf_ticket_comments`: Recuperación de hilos de conversación para el modal.
- `bot_sf_user_toggle`: Activación/Desactivación granular de usuarios.

## 4. Frontend (frontend/src/pages/SuperAdmin.tsx)
Arquitectura **Maestro-Detalle** con las siguientes características de UX:
- **Sidebar de Usuarios (w-64)**:
    - Cola **South American Support Q** anclada al tope (inamovible, con alerta roja pulsante si hay tickets).
    - Buscador de usuarios activos.
    - Sección colapsable para usuarios deshabilitados.
- **Tabla de Tickets**:
    - **Time**: Cálculo relativo (ej: `23D` si >1 día, `5H` si <1 día) + Fecha/Hora exacta en subtítulo.
    - **Estilos**: Filas verdes para `WORKING`, grises (opacity 50%) para `CLOSED`.
    - **Enlace Externo**: Los números de ticket llevan directamente a Salesforce (`lightning.force.com`).
- **Modal de Detalle**: Vista expandida (`max-w-4xl`) que muestra la descripción completa y el historial de comentarios.

## 5. Notas de Mantenimiento
- Para desplegar cambios en el frontend, ejecutar: `npm run build` en la carpeta `frontend`.
- El sistema de polling refresca el estado y la cola de prioridad cada 10 segundos.

---
*Generado por Antigravity - 2026-05-15*
