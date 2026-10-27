#!/bin/bash
ACTION=$1
PARAM2=$2
SERVICE_NAME="rmm-codelco.service"
PROCESS_PATTERN="rmmcod_agent.py"
SOCKET_DIR="/var/www/monitoreoLaboratorio/v3/sockets"

case $ACTION in
    restart-service)
        echo "Reiniciando servicio $SERVICE_NAME y limpiando bloqueos..."
        pkill -9 -f $PROCESS_PATTERN 2>/dev/null || true
        rm -f /tmp/rmmcod_agent.lock 2>/dev/null || true
        systemctl restart $SERVICE_NAME 2>/dev/null || sudo systemctl restart $SERVICE_NAME 2>/dev/null || true
        echo "Servicio $SERVICE_NAME reiniciado."
        ;;
    restart-site)
        SITE_ID=$PARAM2
        echo "Reiniciando túneles/sockets para sitio ID $SITE_ID..."
        rm -f $SOCKET_DIR/rmmcod_*.sock 2>/dev/null || true
        pkill -f "ssh.*rmmcod" 2>/dev/null || true
        echo "Sockets eliminados para sitio $SITE_ID."
        ;;
    status)
        UPTIME=$(systemctl show $SERVICE_NAME --property=ActiveEnterTimestamp | cut -d'=' -f2)
        STATE=$(systemctl is-active $SERVICE_NAME)
        echo "{\"state\": \"$STATE\", \"since\": \"$UPTIME\"}"
        ;;
    *)
        echo "Usage: $0 {restart-service|restart-site <site_id>|status}"
        exit 1
        ;;
esac
