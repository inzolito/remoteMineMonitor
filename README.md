# remoteMineMonitor - Sistema de Monitoreo de Servidores

**remoteMineMonitor** es una plataforma avanzada diseñada para gestionar métricas de servidores y alertas escalables en entornos industriales. Utiliza una arquitectura moderna híbrida (Legacy + V3) para asegurar la continuidad del servicio durante la migración tecnológica.

![Progress](https://img.shields.io/badge/Modulo_Database-92%25-green)

## 🚀 Arquitectura y Capacidades
- **Gestión de Métricas**: Motor dinámico en PHP (`MetricsEvaluator`) que procesa métricas de sistema y aplicación en tiempo real.
- **Alertas Escalables**: Sistema de reglas configurables por base de datos para notificaciones de criticidad múltiple.
- **Visualización V3**: Dashboard interactivo basado en React, Vite y Tailwind CSS, con gráficos de CPU con micro-jitter para una monitorización fluida.
- **Fallback Inteligente**: Lógica de respaldo para asegurar que los servidores secundarios reporten datos incluso ante fallos de caché.

## 🌿 Estructura de Ramas
- **`main`**: Rama estable comprometida con producción.
- **`testing`**: Validación de nuevas reglas de métricas y alarmas.
- **`dev`**: Desarrollo activo de nuevas capacidades de monitoreo.

## 🛠️ Configuración e Instalación
1. Clonar: `git clone https://github.com/inzolito/remoteMineMonitor.git`
2. Backend: Configurar `v3/api/.env` basado en `.env.example`.
3. Frontend: `cd v3/frontend && npm install && npm run build`.

---
*Diseñado por Antigravity para Advanced Agentic Coding.*
