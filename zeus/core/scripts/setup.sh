#!/bin/bash

# Zeus Dashboard - Setup Script
# Complete setup for development environment

set -e

echo "🚀 Zeus Dashboard - Setup"
echo "========================="

cd "$(dirname "$0")/.."

# Check Docker
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

echo "📦 Building Docker containers..."
docker-compose up -d --build

echo "⏳ Waiting for containers to start..."
sleep 10

echo "📚 Installing PHP dependencies..."
docker exec -it zeus-dashboard-app composer install

echo "🔑 Generating application key..."
docker exec -it zeus-dashboard-app php artisan key:generate

echo "🗄️ Setting up storage..."
docker exec -it zeus-dashboard-app php artisan storage:link 2>/dev/null || true

echo ""
echo "✅ Setup completed!"
echo ""
echo "🌐 Dashboard: http://localhost:8080"
echo ""
echo "📊 To import Zeus Core database:"
echo "   ./scripts/import-db.sh"
echo ""
echo "📝 Docker commands:"
echo "   docker-compose logs -f        # View logs"
echo "   docker-compose down           # Stop containers"
echo "   docker-compose up -d          # Start containers"
