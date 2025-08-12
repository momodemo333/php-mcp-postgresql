#!/bin/bash

# Script to stop PostgreSQL test database

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

echo "🛑 Stopping PostgreSQL test database..."

docker-compose -f docker-compose.test.yml down

echo "✅ PostgreSQL test database stopped and removed."