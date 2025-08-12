#!/bin/bash

# Script to start PostgreSQL test database with Docker

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

echo "🐘 Starting PostgreSQL test database..."

# Stop and remove existing container if running
echo "Cleaning up existing containers..."
docker-compose -f docker-compose.test.yml down 2>/dev/null || true

# Start PostgreSQL container
echo "Starting PostgreSQL container..."
docker-compose -f docker-compose.test.yml up -d postgres

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL to be ready..."
MAX_TRIES=30
COUNTER=0

while [ $COUNTER -lt $MAX_TRIES ]; do
    if docker-compose -f docker-compose.test.yml exec -T postgres pg_isready -U testuser -d testdb &>/dev/null; then
        echo "✅ PostgreSQL is ready!"
        break
    fi
    
    COUNTER=$((COUNTER + 1))
    if [ $COUNTER -eq $MAX_TRIES ]; then
        echo "❌ PostgreSQL failed to start within ${MAX_TRIES} seconds"
        docker-compose -f docker-compose.test.yml logs postgres
        exit 1
    fi
    
    echo "Waiting for PostgreSQL... (${COUNTER}/${MAX_TRIES})"
    sleep 1
done

# Display connection info
echo ""
echo "📋 PostgreSQL Test Database Information:"
echo "----------------------------------------"
echo "Host:     localhost"
echo "Port:     54320"
echo "Database: testdb"
echo "Username: testuser"
echo "Password: testpass"
echo ""
echo "Connection string:"
echo "postgresql://testuser:testpass@localhost:54320/testdb"
echo ""
echo "To connect with psql:"
echo "PGPASSWORD=testpass psql -h localhost -p 54320 -U testuser -d testdb"
echo ""
echo "To stop the database:"
echo "./tests/stop-test-db.sh"
echo ""

# Optional: Start pgAdmin if debug profile is requested
if [ "$1" == "--debug" ] || [ "$1" == "--pgadmin" ]; then
    echo "Starting pgAdmin..."
    docker-compose -f docker-compose.test.yml --profile debug up -d pgadmin
    echo "pgAdmin available at: http://localhost:5050"
    echo "Login: admin@test.com / admin"
fi

echo "✅ Test environment ready!"