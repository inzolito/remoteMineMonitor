#!/bin/bash
# Control Script for Salesforce Bot (RMMSF)

ACTION=$1
BASE_DIR="/home/jigsaw/monitoreoRemoto/RMMSF"
DAEMON_SCRIPT="sync_daemon.py"
LOG_FILE="$BASE_DIR/daemon.log"

case $ACTION in
    restart)
        echo "Restarting RMMSF Sync Daemon..."
        pkill -f $DAEMON_SCRIPT
        sleep 2
        cd $BASE_DIR && nohup python3 $DAEMON_SCRIPT > /dev/null 2>&1 &
        echo "Daemon started in background."
        ;;
    status)
        PID=$(pgrep -f $DAEMON_SCRIPT)
        if [ -z "$PID" ]; then
            echo "{\"state\": \"inactive\", \"pid\": null}"
        else
            UPTIME=$(ps -o etime= -p $PID | tr -d ' ')
            echo "{\"state\": \"active\", \"pid\": $PID, \"uptime\": \"$UPTIME\"}"
        fi
        ;;
    *)
        echo "Usage: $0 {restart|status}"
        exit 1
        ;;
esac
