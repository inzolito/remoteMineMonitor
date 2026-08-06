#!/bin/bash
ACTION=$1
SERVICE_NAME="rmm-codelco.service"
PROCESS_PATTERN="rmmcod_agent.py"

case $ACTION in
    restart-service)
        echo "Restarting service by killing process: $PROCESS_PATTERN"
        sudo /usr/bin/pkill -f $PROCESS_PATTERN
        ;;
    status)
        UPTIME=$(systemctl show $SERVICE_NAME --property=ActiveEnterTimestamp | cut -d'=' -f2)
        STATE=$(systemctl is-active $SERVICE_NAME)
        echo "{\"state\": \"$STATE\", \"since\": \"$UPTIME\"}"
        ;;
    *)
        echo "Usage: $0 {restart-service|status}"
        exit 1
        ;;
esac
