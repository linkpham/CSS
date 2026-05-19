#!/bin/bash

# Zeus Dashboard - Database Import Script
# Imports zeus_core.sql into the MySQL Docker container

set -e

echo "🔄 Zeus Dashboard - Database Import"
echo "===================================="

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Check if container is running
if ! docker ps | grep -q zeus-dashboard-mysql; then
    echo "❌ MySQL container is not running."
    echo "Run: docker-compose up -d"
    exit 1
fi

SQL_FILE="/Users/que/Downloads/zeus/zeus_core.sql"

# Check if SQL file exists
if [ ! -f "$SQL_FILE" ]; then
    echo "❌ SQL file not found: $SQL_FILE"
    exit 1
fi

echo "📁 SQL file size: $(du -h "$SQL_FILE" | cut -f1)"
echo "⏳ Copying SQL file to container..."

# Copy SQL file to container
docker cp "$SQL_FILE" zeus-dashboard-mysql:/tmp/zeus_core.sql

echo "⏳ Importing database (this may take a while)..."

# Import SQL file
docker exec -i zeus-dashboard-mysql mysql -uroot -psecret zeus_dashboard < "$SQL_FILE"

echo "✅ Database import completed successfully!"
echo ""
echo "🌐 Access dashboard at: http://localhost:8080"
