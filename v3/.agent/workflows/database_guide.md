---
description: Guía de arquitectura de base de datos para agentes
---

# Arquitectura de Monitoreo V3

Este proyecto utiliza una arquitectura de "Métrica-Evaluación-Alerta" desacoplada.

## Flujo de Trabajo

### 1. Recolección
Los agentes remotos (proyectos `monitoreoRemoto`) envían JSONs a `api/metrics.php`.
Ejemplo de carga:
```json
{
  "server_id": 5,
  "system": { "cpu": 1.5, "ram_percent": 80 },
  "app": { "db.connections": 150 }
}
```

### 2. Tablas de Estado vs Historial
- **`server_app_metrics`**: Almacena el estado **vivo**. Si quieres saber si un servidor está en "danger" AHORA, consulta esta tabla.
- **`server_metric_values`**: Almacena el **log**. Úsalo para análisis temporal.

### 3. Sistema de Alertas Proactivo
El `MetricsEvaluator.php` es el cerebro. No consultes las métricas y decidas si algo está mal; consulta las `alert_rules` y deja que el evaluador inserte en la tabla `alerts`.

## Referencia de Tablas
Consulta el archivo `DATABASE_V3_MODEL.md` en la raíz para ver el diagrama Mermaid y el detalle de los campos.
