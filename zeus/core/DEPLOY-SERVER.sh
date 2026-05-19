#!/bin/bash

# Zeus Dashboard - Remote Deployment Script
# Deploy from local to server via SSH
#
# Usage:
#   ./DEPLOY-SERVER.sh           # Full installation (first time or major updates)
#   ./DEPLOY-SERVER.sh upgrade   # Quick update (source code changes only)
#
# Database Strategy:
#   - Zeus Core: Connects to AWS Aurora (external MySQL)
#   - CareSoft cache: Automatically uses SQLite (no Docker MySQL needed)

set -e

# ========================
# Configuration
# ========================
SSH_KEY="$HOME/Downloads/zeus/quenn"
SSH_USER="quenn"
SSH_HOST="13.215.57.82"
REMOTE_DIR="/var/www/zeus-dashboard"

# Check for upgrade mode
UPGRADE_MODE=false
if [ "$1" = "upgrade" ]; then
    UPGRADE_MODE=true
fi

# Files and directories to exclude from sync
EXCLUDES=(
    ".git"
    ".env"
    "doc/"
    "HUONGDAN.md"
    ".DS_Store"
    "*.log"
    "docker/mysql/data"
    "src/vendor"
    "src/node_modules"
    "src/storage/logs/*"
    "src/storage/framework/cache/*"
    "src/storage/framework/sessions/*"
    "src/storage/framework/views/*"
    ".x-droid"
    "docker-compose.yml"
)

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
    echo "🔄 Zeus Dashboard - Remote Upgrade (Source Code Only)"
else
    echo "🚀 Zeus Dashboard - Remote Deployment (Full Installation)"
fi
echo "======================================"
echo "Server: $SSH_USER@$SSH_HOST"
echo "Remote: $REMOTE_DIR"
echo ""

# Check if SSH key exists
if [ ! -f "$SSH_KEY" ]; then
    print_error "SSH key not found: $SSH_KEY"
fi

# Fix SSH key permissions
chmod 600 "$SSH_KEY"

# Change to project directory
cd "$(dirname "$0")"

print_step "Step 1: Testing SSH connection..."
if ssh -i "$SSH_KEY" -o ConnectTimeout=10 -o StrictHostKeyChecking=accept-new "$SSH_USER@$SSH_HOST" "echo 'Connection OK'" 2>/dev/null; then
    print_success "SSH connection successful"
else
    print_error "Cannot connect to server. Check SSH key and network."
fi

print_step "Step 2: Ensuring remote directory exists..."
ssh -i "$SSH_KEY" "$SSH_USER@$SSH_HOST" "sudo mkdir -p $REMOTE_DIR && sudo chown -R $SSH_USER:$SSH_USER $REMOTE_DIR"
print_success "Remote directory ready"

print_step "Step 3: Syncing files to server..."

# Build exclude arguments for rsync
EXCLUDE_ARGS=""
for exclude in "${EXCLUDES[@]}"; do
    EXCLUDE_ARGS="$EXCLUDE_ARGS --exclude='$exclude'"
done

# Sync files using rsync
eval rsync -avz --delete \
    $EXCLUDE_ARGS \
    -e "\"ssh -i $SSH_KEY\"" \
    ./ "$SSH_USER@$SSH_HOST:$REMOTE_DIR/"

print_success "Files synced successfully"

# ========================
# UPGRADE MODE: Quick update (source code changes only)
# ========================
if [ "$UPGRADE_MODE" = true ]; then
    print_step "Upgrade Mode: Clearing caches on server..."
    
    ssh -i "$SSH_KEY" "$SSH_USER@$SSH_HOST" << 'UPGRADE_SCRIPT'
cd /var/www/zeus-dashboard

# Determine docker command
DOCKER_CMD="docker"
if ! docker info &> /dev/null 2>&1; then
    if sudo docker info &> /dev/null 2>&1; then
        DOCKER_CMD="sudo docker"
    fi
fi

# Clear all caches
$DOCKER_CMD exec zeus-dashboard-app php artisan config:clear
$DOCKER_CMD exec zeus-dashboard-app php artisan cache:clear
$DOCKER_CMD exec zeus-dashboard-app php artisan route:clear
$DOCKER_CMD exec zeus-dashboard-app php artisan view:clear

# Rebuild caches for production
$DOCKER_CMD exec zeus-dashboard-app php artisan config:cache
$DOCKER_CMD exec zeus-dashboard-app php artisan route:cache
$DOCKER_CMD exec zeus-dashboard-app php artisan view:cache

echo "✅ Caches cleared and rebuilt"

# Phase 46: Clear stuck/failed queue jobs from Redis before restarting
# This fixes "has been attempted too many times" errors from old failed jobs
echo "🗑️  Clearing stuck queue jobs..."
$DOCKER_CMD exec zeus-dashboard-app php artisan queue:clear redis --force 2>/dev/null || echo "   (queue cleared)"
echo "✅ Queue jobs cleared"

# Restart queue worker to pick up new PHP config (memory_limit changes)
# Phase 46: Required when local.ini changes (e.g., memory_limit increase)
echo "🔄 Restarting queue worker container..."
if $DOCKER_CMD ps --format '{{.Names}}' | grep -q 'zeus-dashboard-queue'; then
    $DOCKER_CMD restart zeus-dashboard-queue
    echo "✅ Queue worker container restarted"
else
    echo "⚠️  Queue worker container not found (will start on next full deploy)"
fi

# Pre-cache dashboard data for faster page loads
echo "📊 Refreshing dashboard cache..."
$DOCKER_CMD exec -t zeus-dashboard-app php artisan dashboard:refresh-cache || echo "⚠️  Cache refresh skipped (command may not exist yet)"
echo "✅ Dashboard cache refresh completed"

# Verify/Setup Laravel Scheduler Cronjob
echo "⏰ Verifying Laravel Scheduler cronjob..."
CRON_CMD="* * * * * cd /var/www/zeus-dashboard && $DOCKER_CMD exec zeus-dashboard-app php artisan schedule:run >> /dev/null 2>&1"

if crontab -l 2>/dev/null | grep -q "zeus-dashboard-app php artisan schedule:run"; then
    echo "✅ Laravel Scheduler cronjob is active"
else
    # Add cronjob if missing
    (crontab -l 2>/dev/null || true; echo "$CRON_CMD") | crontab -
    echo "✅ Laravel Scheduler cronjob added"
fi
UPGRADE_SCRIPT
    
    print_success "Remote upgrade completed"
    
    echo ""
    echo "======================================"
    echo "🎉 Upgrade completed successfully!"
    echo "======================================"
    echo ""
    echo "🌐 Dashboard should be available at:"
    echo "   http://$SSH_HOST"
    echo ""
    echo "💡 Tip: Use './DEPLOY-SERVER.sh' (without upgrade) for full reinstall"
    echo ""
    exit 0
fi

# ========================
# FULL INSTALLATION MODE
# ========================

print_step "Step 4: Creating .env file on server..."

# Environment variables for production (set these before running or via CI/CD secrets)
# These can be overridden by exporting them before running the script:
#   export ZEUS_DB_HOST="your-host"
#   export ZEUS_DB_PASSWORD="your-password"
#   ./DEPLOY-SERVER.sh

ZEUS_DB_HOST_VAL="${ZEUS_DB_HOST:-zeus-aurora-replica-3-prod.csrn8dqqphhg.ap-southeast-1.rds.amazonaws.com}"
ZEUS_DB_DATABASE_VAL="${ZEUS_DB_DATABASE:-zeus_core}"
ZEUS_DB_USERNAME_VAL="${ZEUS_DB_USERNAME:-zeus_core_user}"
ZEUS_DB_PASSWORD_VAL="${ZEUS_DB_PASSWORD:-}"

# CareSoft API Configuration (set via environment)
CARESOFT_DOMAIN_VAL="${CARESOFT_DOMAIN:-zeusedu}"
CARESOFT_API_TOKEN_VAL="${CARESOFT_API_TOKEN:-}"  # Required: set CARESOFT_API_TOKEN env var

# CareSoft cache uses SQLite by default (no Docker MySQL needed)
# This is set automatically - no configuration needed
DASHBOARD_DB_CONNECTION_VAL="sqlite"

# LCMS External API Configuration (auto-configured via config/lcms.php default)
LCMS_API_URL_VAL="${LCMS_API_URL:-https://lcms.icanwork.vn}"
LCMS_API_KEY_VAL="${LCMS_API_KEY:-}"

if [ -z "$ZEUS_DB_PASSWORD_VAL" ]; then
    echo "⚠️  Warning: ZEUS_DB_PASSWORD environment variable not set"
    echo "   Set it with: export ZEUS_DB_PASSWORD='your-password'"
    read -p "   Enter ZEUS_DB_PASSWORD (or press Enter to skip): " ZEUS_DB_PASSWORD_VAL
fi

# Create production .env file on the server
ssh -i "$SSH_KEY" "$SSH_USER@$SSH_HOST" "cat > /var/www/zeus-dashboard/src/.env << 'ENVFILE'
APP_NAME=\"Zeus Dashboard\"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://${SSH_HOST}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Default database connection points to Zeus Core (all models use 'mysql' connection)
DB_CONNECTION=mysql
DB_HOST=${ZEUS_DB_HOST_VAL}
DB_PORT=3306
DB_DATABASE=${ZEUS_DB_DATABASE_VAL}
DB_USERNAME=${ZEUS_DB_USERNAME_VAL}
DB_PASSWORD=${ZEUS_DB_PASSWORD_VAL}

# Zeus Core Database (alias for consistency with local dev setup)
ZEUS_DB_CONNECTION=mysql
ZEUS_DB_HOST=${ZEUS_DB_HOST_VAL}
ZEUS_DB_PORT=3306
ZEUS_DB_DATABASE=${ZEUS_DB_DATABASE_VAL}
ZEUS_DB_USERNAME=${ZEUS_DB_USERNAME_VAL}
ZEUS_DB_PASSWORD=${ZEUS_DB_PASSWORD_VAL}

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=null
SESSION_DRIVER=file
SESSION_LIFETIME=480
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# CareSoft API Configuration
CARESOFT_DOMAIN=${CARESOFT_DOMAIN_VAL}
CARESOFT_API_TOKEN=${CARESOFT_API_TOKEN_VAL}

# CareSoft cache database - uses SQLite (no Docker MySQL needed)
# SQLite file will be created at database/caresoft.sqlite
DASHBOARD_DB_CONNECTION=${DASHBOARD_DB_CONNECTION_VAL}

# LCMS External API Configuration
LCMS_API_URL=${LCMS_API_URL_VAL}
LCMS_API_KEY=${LCMS_API_KEY_VAL}

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=\"hello@example.com\"
MAIL_FROM_NAME=\"Zeus Dashboard\"
ENVFILE"

print_success ".env file created on server"

print_step "Step 5: Running remote setup..."

ssh -i "$SSH_KEY" "$SSH_USER@$SSH_HOST" << 'REMOTE_SCRIPT'
cd /var/www/zeus-dashboard

echo "📦 Setting up on server..."

# Use production SQLite docker-compose (port 80, no MySQL container)
if [ -f docker-compose.prod.sqlite.yml ]; then
    echo "📦 Using production SQLite docker-compose (port 80, no Docker MySQL)..."
    cp docker-compose.prod.sqlite.yml docker-compose.yml
elif [ -f docker-compose.prod.yml ]; then
    echo "📦 Using production docker-compose (port 80)..."
    cp docker-compose.prod.yml docker-compose.yml
fi

# Set permissions
chmod -R 775 src/storage src/bootstrap/cache 2>/dev/null || true

# Function to install Docker on Ubuntu
install_docker() {
    echo "🐳 Docker not found. Installing Docker..."
    
    # Update package index
    sudo apt-get update -y
    
    # Install prerequisites
    sudo apt-get install -y ca-certificates curl gnupg
    
    # Add Docker's official GPG key
    sudo install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    sudo chmod a+r /etc/apt/keyrings/docker.gpg
    
    # Add Docker repository
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
      $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
      sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    
    # Install Docker Engine
    sudo apt-get update -y
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    
    # Add current user to docker group
    sudo usermod -aG docker $USER
    
    # Start Docker service
    sudo systemctl start docker
    sudo systemctl enable docker
    
    echo "✅ Docker installed successfully"
}

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    install_docker
fi

# Determine if we need sudo for docker commands
DOCKER_CMD="docker"
COMPOSE_CMD=""

# Check for docker compose (v2) or docker-compose (v1)
if command -v docker &> /dev/null; then
    if docker compose version &> /dev/null 2>&1; then
        COMPOSE_CMD="docker compose"
    elif sudo docker compose version &> /dev/null 2>&1; then
        COMPOSE_CMD="sudo docker compose"
    elif command -v docker-compose &> /dev/null; then
        COMPOSE_CMD="docker-compose"
    fi
fi

# Check if docker requires sudo
if ! docker info &> /dev/null 2>&1; then
    if sudo docker info &> /dev/null 2>&1; then
        DOCKER_CMD="sudo docker"
        if [ -n "$COMPOSE_CMD" ] && [[ ! "$COMPOSE_CMD" == sudo* ]]; then
            COMPOSE_CMD="sudo $COMPOSE_CMD"
        fi
    fi
fi

# Build and start containers
if [ -n "$COMPOSE_CMD" ]; then
    echo "🔄 Starting Docker containers..."
    
    # Phase 54: Force stop and remove ALL zeus-dashboard containers to avoid OCI cgroup errors
    # OCI error occurs when container ID becomes stale after daemon restart
    echo "🛑 Stopping and removing old containers..."
    $COMPOSE_CMD down --remove-orphans 2>/dev/null || true
    
    # Force remove all zeus-dashboard containers (handles stale container IDs)
    for container in zeus-dashboard-app zeus-dashboard-nginx zeus-dashboard-redis zeus-dashboard-queue zeus-dashboard-mysql; do
        $DOCKER_CMD stop "$container" 2>/dev/null || true
        $DOCKER_CMD rm -f "$container" 2>/dev/null || true
    done
    
    # Phase 55: Clear Docker BuildKit cache to fix snapshot corruption errors
    # Error: "parent snapshot sha256:xxx does not exist: not found"
    # This happens when BuildKit's internal cache gets corrupted
    echo "🧹 Clearing Docker build cache to fix snapshot errors..."
    $DOCKER_CMD builder prune -f 2>/dev/null || true
    
    # Wait a moment for Docker daemon to clean up
    sleep 3
    
    # Free up port 80 if something else is using it (e.g., apache2, nginx standalone)
    echo "🔓 Checking if port 80 is in use..."
    if sudo lsof -i :80 -t 2>/dev/null; then
        echo "⚠️  Port 80 is in use. Attempting to stop conflicting services..."
        sudo systemctl stop apache2 2>/dev/null || true
        sudo systemctl stop nginx 2>/dev/null || true
        # Kill any remaining processes on port 80
        sudo fuser -k 80/tcp 2>/dev/null || true
    fi
    
    # Build and start fresh containers
    echo "🔨 Building and starting containers..."
    $COMPOSE_CMD up -d --build --force-recreate
    
    echo "⏳ Waiting for containers to be healthy..."
    
    # Phase 54: Improved container wait function with OCI cgroup error handling
    # Fixes: OCI runtime exec failed: ... cgroup.procs: no such file or directory
    wait_for_container() {
        local container_name=$1
        local max_attempts=60  # 60 attempts x 2 seconds = 120 seconds max
        local attempt=1
        
        echo "   Waiting for $container_name..."
        while [ $attempt -le $max_attempts ]; do
            # Check if container exists first
            if ! $DOCKER_CMD inspect "$container_name" &>/dev/null; then
                echo "   ⚠️  Container $container_name not found"
                return 1
            fi
            
            local status=$($DOCKER_CMD inspect -f '{{.State.Status}}' "$container_name" 2>/dev/null || echo "not_found")
            local restarting=$($DOCKER_CMD inspect -f '{{.State.Restarting}}' "$container_name" 2>/dev/null || echo "true")
            local exit_code=$($DOCKER_CMD inspect -f '{{.State.ExitCode}}' "$container_name" 2>/dev/null || echo "1")
            
            if [ "$status" = "running" ] && [ "$restarting" = "false" ]; then
                # Additional check: try a simple exec to verify container is truly responsive
                if $DOCKER_CMD exec "$container_name" echo "ping" &>/dev/null; then
                    echo "   ✅ $container_name is ready (verified with exec)"
                    return 0
                else
                    echo "   ⏳ $container_name running but exec failed, waiting..."
                fi
            fi
            
            if [ "$status" = "exited" ] && [ "$exit_code" != "0" ]; then
                echo "   ❌ $container_name exited with code $exit_code. Checking logs..."
                $DOCKER_CMD logs --tail 30 "$container_name" 2>&1
                return 1
            fi
            
            sleep 2
            attempt=$((attempt + 1))
        done
        
        echo "   ⚠️  Timeout waiting for $container_name (status: $status, restarting: $restarting)"
        echo "   Container logs:"
        $DOCKER_CMD logs --tail 50 "$container_name" 2>&1
        return 1
    }
    
    # Phase 54: Function to execute docker command with retry (handles transient OCI errors)
    docker_exec_retry() {
        local container_name=$1
        shift
        local max_retries=5
        local retry=0
        
        while [ $retry -lt $max_retries ]; do
            if $DOCKER_CMD exec "$container_name" "$@" 2>&1; then
                return 0
            fi
            
            local error=$($DOCKER_CMD exec "$container_name" "$@" 2>&1 || true)
            if echo "$error" | grep -q "is restarting"; then
                echo "   ⏳ Container restarting, waiting... (attempt $((retry+1))/$max_retries)"
                sleep 3
                retry=$((retry+1))
            elif echo "$error" | grep -q "OCI runtime"; then
                echo "   ⏳ OCI error, waiting for container stability... (attempt $((retry+1))/$max_retries)"
                sleep 5
                retry=$((retry+1))
            else
                # Non-transient error, fail immediately
                return 1
            fi
        done
        
        echo "   ❌ Max retries reached for docker exec"
        return 1
    }
    
    # Wait for main app container first (required for exec commands)
    if ! wait_for_container "zeus-dashboard-app"; then
        echo "❌ App container failed to start. Cannot proceed."
        exit 1
    fi
    
    # Wait for other containers (non-blocking, just log status)
    wait_for_container "zeus-dashboard-nginx" || echo "   (nginx container issue - may need investigation)"
    wait_for_container "zeus-dashboard-redis" || echo "   (redis container issue - may need investigation)"
    wait_for_container "zeus-dashboard-queue" || echo "   (queue container issue - may need investigation)"
    
    # Create vendor directory with proper permissions (run as root)
    # This fixes the "/var/www/vendor does not exist and could not be created" error
    $DOCKER_CMD exec -u root zeus-dashboard-app mkdir -p /var/www/vendor
    $DOCKER_CMD exec -u root zeus-dashboard-app chown -R www:www /var/www/vendor
    
    # Create SQLite database file for CareSoft cache
    echo "🗃️  Creating SQLite database for CareSoft cache..."
    $DOCKER_CMD exec -u root zeus-dashboard-app mkdir -p /var/www/database
    $DOCKER_CMD exec -u root zeus-dashboard-app touch /var/www/database/caresoft.sqlite
    $DOCKER_CMD exec -u root zeus-dashboard-app chown www:www /var/www/database/caresoft.sqlite
    $DOCKER_CMD exec -u root zeus-dashboard-app chmod 666 /var/www/database/caresoft.sqlite
    
    # Fix storage and cache permissions (run as root)
    # This fixes the "Permission denied" error for laravel.log
    $DOCKER_CMD exec -u root zeus-dashboard-app mkdir -p /var/www/storage/logs
    $DOCKER_CMD exec -u root zeus-dashboard-app mkdir -p /var/www/storage/framework/cache/data
    $DOCKER_CMD exec -u root zeus-dashboard-app mkdir -p /var/www/storage/framework/sessions
    $DOCKER_CMD exec -u root zeus-dashboard-app mkdir -p /var/www/storage/framework/views
    $DOCKER_CMD exec -u root zeus-dashboard-app mkdir -p /var/www/bootstrap/cache
    $DOCKER_CMD exec -u root zeus-dashboard-app chown -R www:www /var/www/storage
    $DOCKER_CMD exec -u root zeus-dashboard-app chown -R www:www /var/www/bootstrap/cache
    $DOCKER_CMD exec -u root zeus-dashboard-app chmod -R 775 /var/www/storage
    $DOCKER_CMD exec -u root zeus-dashboard-app chmod -R 775 /var/www/bootstrap/cache
    
    # Clear any cached config from local development (which may reference dev packages)
    # This fixes the "CollisionServiceProvider not found" error
    $DOCKER_CMD exec zeus-dashboard-app rm -f /var/www/bootstrap/cache/config.php 2>/dev/null || true
    $DOCKER_CMD exec zeus-dashboard-app rm -f /var/www/bootstrap/cache/packages.php 2>/dev/null || true
    $DOCKER_CMD exec zeus-dashboard-app rm -f /var/www/bootstrap/cache/services.php 2>/dev/null || true
    
    # Install dependencies and setup
    $DOCKER_CMD exec zeus-dashboard-app composer install --no-dev --optimize-autoloader
    
    # Fix .env file ownership so www user can write to it
    # This fixes "Permission denied" error when running key:generate
    $DOCKER_CMD exec -u root zeus-dashboard-app chown www:www /var/www/.env
    $DOCKER_CMD exec -u root zeus-dashboard-app chmod 664 /var/www/.env
    
    # Generate APP_KEY if not set
    if ! grep -q "APP_KEY=base64:" /var/www/zeus-dashboard/src/.env; then
        echo "🔑 Generating APP_KEY..."
        $DOCKER_CMD exec zeus-dashboard-app php artisan key:generate --force
    fi
    
    # Clear all caches first to ensure clean state (fixes 419 errors)
    $DOCKER_CMD exec zeus-dashboard-app php artisan config:clear
    $DOCKER_CMD exec zeus-dashboard-app php artisan cache:clear
    $DOCKER_CMD exec zeus-dashboard-app php artisan route:clear
    $DOCKER_CMD exec zeus-dashboard-app php artisan view:clear
    
    # Rebuild caches for production
    $DOCKER_CMD exec zeus-dashboard-app php artisan config:cache
    $DOCKER_CMD exec zeus-dashboard-app php artisan route:cache
    $DOCKER_CMD exec zeus-dashboard-app php artisan view:cache
    $DOCKER_CMD exec zeus-dashboard-app php artisan storage:link 2>/dev/null || true
    
    # Ensure session directory has correct permissions (fixes 419 errors after login)
    $DOCKER_CMD exec -u root zeus-dashboard-app chmod -R 777 /var/www/storage/framework/sessions
    
    # Test database connection to Zeus Core (Aurora)
    echo "🔍 Testing MySQL connection to Zeus Core..."
    if $DOCKER_CMD exec zeus-dashboard-app php artisan tinker --execute="try { \Illuminate\Support\Facades\DB::connection('zeus_core')->getPdo(); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: ' . \$e->getMessage(); }" 2>&1 | grep -q "OK"; then
        echo "✅ MySQL connection to Zeus Core successful"
    else
        echo "⚠️  MySQL connection test result:"
        $DOCKER_CMD exec zeus-dashboard-app php artisan tinker --execute="try { \Illuminate\Support\Facades\DB::connection('zeus_core')->getPdo(); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: ' . \$e->getMessage(); }" 2>&1 || true
    fi
    
    # Test CareSoft SQLite connection
    echo "🔍 Testing CareSoft SQLite connection..."
    if $DOCKER_CMD exec zeus-dashboard-app php artisan tinker --execute="try { \Illuminate\Support\Facades\DB::connection('caresoft')->getPdo(); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: ' . \$e->getMessage(); }" 2>&1 | grep -q "OK"; then
        echo "✅ CareSoft SQLite connection successful"
    else
        echo "⚠️  CareSoft SQLite connection test failed"
        $DOCKER_CMD exec zeus-dashboard-app php artisan tinker --execute="try { \Illuminate\Support\Facades\DB::connection('caresoft')->getPdo(); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: ' . \$e->getMessage(); }" 2>&1 || true
    fi
    
    # Setup Laravel Scheduler Cronjob
    echo "⏰ Setting up Laravel Scheduler cronjob..."
    CRON_CMD="* * * * * cd /var/www/zeus-dashboard && $DOCKER_CMD exec zeus-dashboard-app php artisan schedule:run >> /dev/null 2>&1"
    
    # Check if cronjob already exists
    if crontab -l 2>/dev/null | grep -q "zeus-dashboard-app php artisan schedule:run"; then
        echo "✅ Laravel Scheduler cronjob already exists"
    else
        # Add cronjob
        (crontab -l 2>/dev/null || true; echo "$CRON_CMD") | crontab -
        echo "✅ Laravel Scheduler cronjob added successfully"
    fi
    
    # Verify cronjob
    echo "📋 Current scheduler cronjob:"
    crontab -l 2>/dev/null | grep "schedule:run" || echo "   (none found)"
    
    # Pre-cache dashboard data for faster page loads
    echo "📊 Refreshing dashboard cache..."
    $DOCKER_CMD exec -t zeus-dashboard-app php artisan dashboard:refresh-cache || echo "⚠️  Cache refresh skipped (command may not exist yet)"
    echo "✅ Dashboard cache refresh completed"
else
    echo "❌ Docker compose not available. Please check Docker installation."
fi
REMOTE_SCRIPT

print_success "Remote setup completed"

echo ""
echo "======================================"
echo "🎉 Deployment completed successfully!"
echo "======================================"
echo ""
echo "🌐 Dashboard should be available at:"
echo "   http://$SSH_HOST"
echo ""
echo "📊 Database Configuration:"
echo "   Zeus Core:      MySQL @ AWS Aurora (external)"
echo "   CareSoft Cache: SQLite @ database/caresoft.sqlite"
echo ""
echo "💡 Note: No Docker MySQL container is needed!"
echo "   CareSoft cache tables are stored in SQLite."
echo ""
