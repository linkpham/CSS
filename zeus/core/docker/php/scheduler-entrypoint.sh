#!/bin/bash
# Scheduler entrypoint script
# Phase 58: Runs Laravel scheduler every minute to trigger scheduled tasks

set -e

cd /var/www

echo "[Scheduler] Starting initialization..."

# Wait for app container to be ready (indicated by vendor directory being populated)
echo "[Scheduler] Waiting for dependencies to be installed..."
max_wait=120
waited=0
while [ ! -f /var/www/vendor/autoload.php ]; do
    if [ $waited -ge $max_wait ]; then
        echo "[Scheduler] Timeout waiting for vendor/autoload.php"
        exit 1
    fi
    sleep 2
    waited=$((waited + 2))
    echo "[Scheduler] Waiting for vendor/autoload.php... ($waited/$max_wait sec)"
done

echo "[Scheduler] Dependencies found."

# Ensure storage directories exist and are writable
echo "[Scheduler] Ensuring storage directories..."
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/bootstrap/cache

# Wait a bit more for app setup
echo "[Scheduler] Waiting for app setup to complete..."
sleep 15

# Check if config is cached and if collision provider issue exists
if [ -f /var/www/bootstrap/cache/config.php ]; then
    if grep -q "CollisionServiceProvider" /var/www/bootstrap/cache/config.php 2>/dev/null; then
        echo "[Scheduler] Removing stale config cache (contains dev packages)..."
        rm -f /var/www/bootstrap/cache/config.php
        rm -f /var/www/bootstrap/cache/packages.php
        rm -f /var/www/bootstrap/cache/services.php
    fi
fi

echo "[Scheduler] Starting scheduler loop (runs every minute)..."
echo "[Scheduler] Scheduled tasks:"
echo "  - dashboard:refresh-cache every 15 minutes"
echo "  - dashboard:refresh-cache --force daily at 23:30 Vietnam time"

# Run scheduler every minute in a loop
while true; do
    echo "[Scheduler] $(date '+%Y-%m-%d %H:%M:%S') - Running schedule:run..."
    php artisan schedule:run --verbose --no-interaction 2>&1 | while read line; do
        echo "[Scheduler] $line"
    done
    echo "[Scheduler] Sleeping for 60 seconds..."
    sleep 60
done
