#!/bin/bash
# 1. Login to get token
echo "Logging in..."
TOKEN=$(curl -s -X POST -H "Content-Type: application/json" -d '{"username":"maik","password":"Jigsaw1"}' http://localhost/monitoreoLaboratorio/v3/api/login.php | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')

if [ -z "$TOKEN" ]; then
    echo "Login failed. Output:"
    curl -v -X POST -H "Content-Type: application/json" -d '{"username":"maik","password":"Jigsaw1"}' http://localhost/monitoreoLaboratorio/v3/api/login.php
    exit 1
fi

echo "Got Token: $TOKEN"

# 2. Access Permissions
echo "Accessing Permissions..."
curl -v -H "Authorization: Bearer $TOKEN" http://localhost/monitoreoLaboratorio/v3/api/permissions.php
