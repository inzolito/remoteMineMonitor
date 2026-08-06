#!/bin/bash
ACTION=$1
SERVICE_NAME="rmm-monitor-v2.service"
SUBPROCESS_PATTERN="RMMEyeCatLaboratory_v3"

case $ACTION in
    restart-service)
        echo "Restarting main service: $SERVICE_NAME"
        sudo systemctl restart $SERVICE_NAME
        ;;
    restart-subs)
        echo "Killing subprocesses with pattern: $SUBPROCESS_PATTERN"
        sudo pkill -f $SUBPROCESS_PATTERN
        ;;
    status)
        UPTIME=$(systemctl show $SERVICE_NAME --property=ActiveEnterTimestamp | cut -d'=' -f2)
        STATE=$(systemctl is-active $SERVICE_NAME)
        echo "{\"state\": \"$STATE\", \"since\": \"$UPTIME\"}"
        ;;
    *)
        echo "Usage: $0 {restart-service|restart-subs|status}"
        exit 1
        ;;
esac
