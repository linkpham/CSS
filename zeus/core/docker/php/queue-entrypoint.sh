#!/bin/bash
# Queue worker entrypoint script
# Phase 56: Fixes permission denied and missing dev package errors

set -e

cd /var/www

echo "[Queue Worker] Starting initialization..."

# Wait for app container to be ready (indicated by vendor directory being populated)
echo "[Queue Worker] Waiting for dependencies to be installed..."
max_wait=120
waited=0
while [ ! -f /var/www/vendor/autoload.php ]; do
    if [ $waited -ge $max_wait ]; then
        echo "[Queue Worker] Timeout waiting for vendor/autoload.php"
        exit 1
    fi
    sleep 2
    waited=$((waited + 2))
    echo "[Queue Worker] Waiting for vendor/autoload.php... ($waited/$max_wait sec)"
done

echo "[Queue Worker] Dependencies found."

# Ensure storage directories exist and are writable
echo "[Queue Worker] Ensuring storage directories..."
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/bootstrap/cache

# Create log file if it doesn't exist
touch /var/www/storage/logs/laravel.log 2>/dev/null || true

# Wait a bit more for app container to finish setup (composer install, cache clear)
echo "[Queue Worker] Waiting for app setup to complete..."
sleep 10

# Check if config is cached and if collision provider issue exists
if [ -f /var/www/bootstrap/cache/config.php ]; then
    if grep -q "CollisionServiceProvider" /var/www/bootstrap/cache/config.php 2>/dev/null; then
        echo "[Queue Worker] Removing stale config cache (contains dev packages)..."
        rm -f /var/www/bootstrap/cache/config.php
        rm -f /var/www/bootstrap/cache/packages.php
        rm -f /var/www/bootstrap/cache/services.php
    fi
fi

echo "[Queue Worker] Starting queue worker..."
exec php artisan queue:work redis --sleep=3 --tries=1 --timeout=600 --memory=1024
