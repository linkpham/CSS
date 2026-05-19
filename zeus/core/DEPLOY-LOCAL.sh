#!/bin/bash

# Zeus Dashboard - Local Deployment Script
# Deploy for local development with direct MySQL connection
#
# Usage:
#   ./DEPLOY-LOCAL.sh           # Full installation (first time or major updates)
#   ./DEPLOY-LOCAL.sh upgrade   # Quick update (source code changes only)
#
# Database Strategy:
#   - Zeus Core: Connects to host's MySQL (must be running)
#   - CareSoft cache: Automatically uses SQLite (no Docker MySQL needed)

set -e

# ========================
# Configuration
# ========================
LOCAL_DB_HOST="host.docker.internal"  # Docker's way to access host's MySQL
LOCAL_DB_PORT="3306"
LOCAL_DB_DATABASE="zeus_core"
LOCAL_DB_USERNAME="zeus_core_user"
LOCAL_DB_PASSWORD="fWOUJyw8W*K0d1"

# CareSoft API Configuration (set via environment or update here)
CARESOFT_DOMAIN="${CARESOFT_DOMAIN:-zeusedu}"
CARESOFT_API_TOKEN="${CARESOFT_API_TOKEN:-}"  # Required: set CARESOFT_API_TOKEN env var

# CareSoft cache uses SQLite by default (no Docker MySQL needed)
# This is set automatically - no configuration needed
DASHBOARD_DB_CONNECTION="sqlite"

# LCMS External API Configuration (auto-configured via config/lcms.php default)
LCMS_API_URL="${LCMS_API_URL:-https://lcms.icanwork.vn}"
LCMS_API_KEY="${LCMS_API_KEY:-}"

# Check for upgrade mode
UPGRADE_MODE=false
if [ "$1" = "upgrade" ]; then
    UPGRADE_MODE=true
fi

# ========================
# Functions
# ========================
print_step() {
    echo ""
    echo "🔹 $1"
    echo "----------------------------------------"
}

print_success() {
    echo "✅ $1"
}

print_error() {
    echo "❌ $1"
    exit 1
}

# ========================
# Main Script
# ========================
echo ""
if [ "$UPGRADE_MODE" = true ]; then
    echo "🔄 Zeus Dashboard - Local Upgrade (Source Code Only)"
else
    echo "🚀 Zeus Dashboard - Local Deployment (Full Installation)"
fi
echo "======================================"
echo "Zeus Core DB: $LOCAL_DB_HOST:$LOCAL_DB_PORT/$LOCAL_DB_DATABASE"
echo "CareSoft Cache: SQLite (no Docker MySQL needed)"
echo ""

# Check CareSoft API token
if [ -z "$CARESOFT_API_TOKEN" ]; then
    echo "⚠️  Warning: CARESOFT_API_TOKEN not set. CareSoft sync will not work."
    echo "   Set it with: export CARESOFT_API_TOKEN='your-token'"
    echo ""
fi

# Change to project directory
cd "$(dirname "$0")"

# Check Docker
print_step "Step 1: Checking Docker..."
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker first."
fi
print_success "Docker is running"

# ========================
# UPGRADE MODE: Quick update (source code changes only)
# ========================
if [ "$UPGRADE_MODE" = true ]; then
    print_step "Upgrade Mode: Clearing caches..."
    
    # Clear all caches
    docker exec zeus-dashboard-app php artisan config:clear
    docker exec zeus-dashboard-app php artisan cache:clear
    docker exec zeus-dashboard-app php artisan route:clear
    docker exec zeus-dashboard-app php artisan view:clear
    
    print_success "Caches cleared"
    
    echo ""
    echo "======================================"
    echo "🎉 Local upgrade completed!"
    echo "======================================"
    echo ""
    echo "🌐 Dashboard: http://localhost:8080"
    echo ""
    echo "💡 Tip: Use './DEPLOY-LOCAL.sh' (without upgrade) for full reinstall"
    echo ""
    exit 0
fi

# ========================
# FULL INSTALLATION MODE
# ========================

print_step "Step 2: Creating .env file..."

# Create .env file with local database connection
# CareSoft cache uses SQLite (DASHBOARD_DB_CONNECTION=sqlite)
cat > src/.env << ENVFILE
APP_NAME="Zeus Dashboard"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Default database connection points to Zeus Core (local MySQL)
DB_CONNECTION=mysql
DB_HOST=${LOCAL_DB_HOST}
DB_PORT=${LOCAL_DB_PORT}
DB_DATABASE=${LOCAL_DB_DATABASE}
DB_USERNAME=${LOCAL_DB_USERNAME}
DB_PASSWORD=${LOCAL_DB_PASSWORD}

# Zeus Core Database (alias for consistency)
ZEUS_DB_CONNECTION=mysql
ZEUS_DB_HOST=${LOCAL_DB_HOST}
ZEUS_DB_PORT=${LOCAL_DB_PORT}
ZEUS_DB_DATABASE=${LOCAL_DB_DATABASE}
ZEUS_DB_USERNAME=${LOCAL_DB_USERNAME}
ZEUS_DB_PASSWORD=${LOCAL_DB_PASSWORD}

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=file
SESSION_LIFETIME=480
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# CareSoft API Configuration
CARESOFT_DOMAIN=${CARESOFT_DOMAIN}
CARESOFT_API_TOKEN=${CARESOFT_API_TOKEN}

# CareSoft cache database - uses SQLite (no Docker MySQL needed)
# SQLite file will be created at database/caresoft.sqlite
DASHBOARD_DB_CONNECTION=sqlite

# LCMS External API Configuration
LCMS_API_URL=${LCMS_API_URL}
LCMS_API_KEY=${LCMS_API_KEY}

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="Zeus Dashboard"
ENVFILE

print_success ".env file created with SQLite for CareSoft cache"

print_step "Step 3: Building Docker containers (without MySQL)..."
docker-compose down 2>/dev/null || true

# Use SQLite docker-compose (no MySQL container)
if [ -f docker-compose.sqlite.yml ]; then
    echo "📦 Using SQLite docker-compose (no Docker MySQL)..."
    cp docker-compose.sqlite.yml docker-compose.yml
fi

docker-compose up -d --build

print_step "Step 4: Waiting for containers to start..."
sleep 10

print_step "Step 5: Setting up Laravel application..."

# Create vendor directory with proper permissions
docker exec zeus-dashboard-app mkdir -p /var/www/vendor 2>/dev/null || true

# Create SQLite database file for CareSoft cache
docker exec zeus-dashboard-app mkdir -p /var/www/database
docker exec zeus-dashboard-app touch /var/www/database/caresoft.sqlite
docker exec zeus-dashboard-app chmod 666 /var/www/database/caresoft.sqlite

# Fix storage and cache permissions
docker exec zeus-dashboard-app mkdir -p /var/www/storage/logs
docker exec zeus-dashboard-app mkdir -p /var/www/storage/framework/cache/data
docker exec zeus-dashboard-app mkdir -p /var/www/storage/framework/sessions
docker exec zeus-dashboard-app mkdir -p /var/www/storage/framework/views
docker exec zeus-dashboard-app mkdir -p /var/www/bootstrap/cache
docker exec zeus-dashboard-app chmod -R 777 /var/www/storage 2>/dev/null || true
docker exec zeus-dashboard-app chmod -R 777 /var/www/bootstrap/cache 2>/dev/null || true

# Clear any cached config
docker exec zeus-dashboard-app rm -f /var/www/bootstrap/cache/config.php 2>/dev/null || true
docker exec zeus-dashboard-app rm -f /var/www/bootstrap/cache/packages.php 2>/dev/null || true
docker exec zeus-dashboard-app rm -f /var/www/bootstrap/cache/services.php 2>/dev/null || true

# Install dependencies
docker exec zeus-dashboard-app composer install

# Generate APP_KEY if not set
if ! grep -q "APP_KEY=base64:" src/.env; then
    echo "🔑 Generating APP_KEY..."
    docker exec zeus-dashboard-app php artisan key:generate
fi

# Cache config and create storage link
docker exec zeus-dashboard-app php artisan config:clear
docker exec zeus-dashboard-app php artisan storage:link 2>/dev/null || true

print_step "Step 6: Testing database connections..."

# Test Zeus Core MySQL connection
echo "🔍 Testing Zeus Core MySQL connection..."
if docker exec zeus-dashboard-app php artisan tinker --execute="try { \Illuminate\Support\Facades\DB::connection()->getPdo(); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: ' . \$e->getMessage(); }" 2>&1 | grep -q "OK"; then
    print_success "Zeus Core MySQL connection successful"
else
    echo "⚠️  Zeus Core MySQL connection test result:"
    docker exec zeus-dashboard-app php artisan tinker --execute="try { \Illuminate\Support\Facades\DB::connection()->getPdo(); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: ' . \$e->getMessage(); }" 2>&1 || true
    echo ""
    echo "💡 Make sure your local MySQL server is running on port 3306"
    echo "   and the database '$LOCAL_DB_DATABASE' exists with user '$LOCAL_DB_USERNAME'"
fi

# Test CareSoft SQLite connection
echo "🔍 Testing CareSoft SQLite connection..."
if docker exec zeus-dashboard-app php artisan tinker --execute="try { \Illuminate\Support\Facades\DB::connection('caresoft')->getPdo(); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: ' . \$e->getMessage(); }" 2>&1 | grep -q "OK"; then
    print_success "CareSoft SQLite connection successful"
else
    echo "⚠️  CareSoft SQLite connection test failed"
    docker exec zeus-dashboard-app php artisan tinker --execute="try { \Illuminate\Support\Facades\DB::connection('caresoft')->getPdo(); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: ' . \$e->getMessage(); }" 2>&1 || true
fi

print_step "Step 7: Refreshing dashboard cache..."
# Use -t flag for TTY to enable progress bar display
docker exec -t zeus-dashboard-app php artisan dashboard:refresh-cache || echo "⚠️  Cache refresh command not available (run after initial setup)"
print_success "Cache refresh completed"

echo ""
echo "======================================"
echo "🎉 Local deployment completed!"
echo "======================================"
echo ""
echo "🌐 Dashboard: http://localhost:8080"
echo ""
echo "📝 Docker commands:"
echo "   docker-compose logs -f        # View logs"
echo "   docker-compose down           # Stop containers"
echo "   docker-compose up -d          # Start containers"
echo ""
echo "📊 Database Configuration:"
echo "   Zeus Core:      MySQL @ $LOCAL_DB_HOST:$LOCAL_DB_PORT/$LOCAL_DB_DATABASE"
echo "   CareSoft Cache: SQLite @ database/caresoft.sqlite"
echo ""
echo "💡 Note: No Docker MySQL container is needed!"
echo "   CareSoft cache tables are stored in SQLite."
echo ""
