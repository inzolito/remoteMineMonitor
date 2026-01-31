#!/bin/bash

# setup_git.sh - remoteMineMonitor Git Automation
# Purpose: Initialize repository, configure remote, and push initial commit to GitHub.

PROJECT_ROOT="/var/www/monitoreoLaboratorio"
REMOTE_URL="https://github.com/inzolito/remoteMineMonitor.git"

echo "🚀 Iniciando configuración de Git para remoteMineMonitor en: $PROJECT_ROOT"

# 1. Crear .gitignore Profesional
# Protege archivos sensibles y evita leaks de configuración local de métricas/alarmas
echo "📝 Generando .gitignore..."
cat <<EOF > "$PROJECT_ROOT/.gitignore"
# --- Build & Output ---
dist/
build/
bin/
obj/
out/
.jekyll-cache/
.jekyll-metadata
v3/frontend/dist/

# --- Dependencies ---
node_modules/
bower_components/
vendor/
plugins/
.bundle/
v3/frontend/node_modules/
v3/api/vendor/

# --- Logs & Temp ---
*.log
traffic.log
raw_data.log
v3/api/*.log
tmp/
temp/
debug_*.json
debug_*.txt
debug_*.js
debug_*.php

# --- Backups & Archives ---
backup/
backups/
backup-*/
*.zip
*.tar.gz
*.rar
*.7z
*.bak
*.bakup
archivo
backup-13-9-23
backup-13-9-23.zip
backup.zip
mi_archivo

# --- Sensitive Data & Local Config ---
# Protegemos la conexión a la base de datos y credenciales individuales
.env
config.php
db.php
auth.json
*.pem
*.key
v3/api/db.php
v3/api/.env

# --- IDE & System ---
.vscode/
.idea/
.vs/
.DS_Store
Thumbs.db
EOF

# 2. Crear README.md Profesional
echo "📖 Generando README.md..."
cat <<EOF > "$PROJECT_ROOT/README.md"
# remoteMineMonitor - Sistema de Monitoreo de Servidores

**remoteMineMonitor** es una plataforma avanzada diseñada para gestionar métricas de servidores y alertas escalables en entornos industriales. Utiliza una arquitectura moderna híbrida (Legacy + V3) para asegurar la continuidad del servicio durante la migración tecnológica.

## 🚀 Arquitectura y Capacidades
- **Gestión de Métricas**: Motor dinámico en PHP (\`MetricsEvaluator\`) que procesa métricas de sistema y aplicación en tiempo real.
- **Alertas Escalables**: Sistema de reglas configurables por base de datos para notificaciones de criticidad múltiple.
- **Visualización V3**: Dashboard interactivo basado en React, Vite y Tailwind CSS, con gráficos de CPU con micro-jitter para una monitorización fluida.
- **Fallback Inteligente**: Lógica de respaldo para asegurar que los servidores secundarios reporten datos incluso ante fallos de caché.

## 🌿 Estructura de Ramas
- **\`main\`**: Rama estable comprometida con producción.
- **\`testing\`**: Validación de nuevas reglas de métricas y alarmas.
- **\`dev\`**: Desarrollo activo de nuevas capacidades de monitoreo.

## 🛠️ Configuración e Instalación
1. Clonar: \`git clone $REMOTE_URL\`
2. Backend: Configurar \`v3/api/.env\` basado en \`.env.example\`.
3. Frontend: \`cd v3/frontend && npm install && npm run build\`.

---
*Diseñado por Antigravity para Advanced Agentic Coding.*
EOF

# 3. Inicializar Git y configurar Remoto
echo "🔧 Inicializando repositorio..."
cd "$PROJECT_ROOT"

# Asegurar que no hay repositorios anidados
find . -mindepth 2 -name ".git" -type d -exec rm -rf {} + 2>/dev/null

git init
git remote add origin "$REMOTE_URL" || git remote set-url origin "$REMOTE_URL"

# 4. Primer Commit y Push
echo "📦 Realizando primer commit..."
git add .
git commit -m "feat: initial commit for remoteMineMonitor with scalable metrics architecture"

# Crear ramas estándar
git branch -M main
git checkout -b dev
git checkout -b testing
git checkout main

echo "📤 Enviando a GitHub (push)..."
git push -u origin main

echo "✅ ¡Configuración completada!"
echo "📍 Repositorio: $REMOTE_URL"
echo "📂 Archivos protegidos: db.php, .env y logs locales."
