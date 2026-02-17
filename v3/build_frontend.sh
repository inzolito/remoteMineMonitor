#!/bin/bash
cd /var/www/monitoreoLaboratorio/v3/frontend
export NODE_OPTIONS="--max-old-space-size=4096"
npm run build
