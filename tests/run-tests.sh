#!/bin/bash

# Script to run all tests with Docker PostgreSQL

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🐘 PostgreSQL MCP Server - Test Suite${NC}"
echo "========================================="
echo ""

# Check if PostgreSQL container is running
if ! docker-compose -f docker-compose.test.yml ps | grep -q "pgsql-mcp-test.*Up"; then
    echo -e "${YELLOW}PostgreSQL test database is not running. Starting it...${NC}"
    ./tests/start-test-db.sh
    echo ""
fi

# Wait for PostgreSQL to be ready
echo "Checking PostgreSQL connection..."
MAX_TRIES=10
COUNTER=0

while [ $COUNTER -lt $MAX_TRIES ]; do
    if docker-compose -f docker-compose.test.yml exec -T postgres pg_isready -U testuser -d testdb &>/dev/null; then
        echo -e "${GREEN}✅ PostgreSQL is ready!${NC}"
        break
    fi
    
    COUNTER=$((COUNTER + 1))
    if [ $COUNTER -eq $MAX_TRIES ]; then
        echo -e "${RED}❌ PostgreSQL is not responding${NC}"
        exit 1
    fi
    
    sleep 1
done

echo ""
echo "Running tests..."
echo "----------------"

# Test results
TOTAL=0
PASSED=0
FAILED=0

# Function to run a test
run_test() {
    local test_name=$1
    local test_file=$2
    
    TOTAL=$((TOTAL + 1))
    echo -n "  $test_name... "
    
    if php "$test_file" > /tmp/test_output.txt 2>&1; then
        echo -e "${GREEN}✅ PASSED${NC}"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}❌ FAILED${NC}"
        FAILED=$((FAILED + 1))
        echo ""
        echo "    Error output:"
        tail -n 20 /tmp/test_output.txt | sed 's/^/    /'
        echo ""
    fi
}

# Run all tests
echo "1. Basic Tests:"
run_test "Connection Test" "tests/test_connection.php"
run_test "MCP Server Test" "tests/test_mcp_server.php"

# Check for PHPUnit tests
if [ -f "vendor/bin/phpunit" ]; then
    echo ""
    echo "2. PHPUnit Tests:"
    echo -n "  Running PHPUnit suite... "
    
    if vendor/bin/phpunit --no-coverage > /tmp/phpunit_output.txt 2>&1; then
        echo -e "${GREEN}✅ PASSED${NC}"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}❌ FAILED${NC}"
        FAILED=$((FAILED + 1))
        echo ""
        echo "    PHPUnit output:"
        tail -n 30 /tmp/phpunit_output.txt | sed 's/^/    /'
    fi
    TOTAL=$((TOTAL + 1))
else
    echo ""
    echo -e "${YELLOW}⚠️  PHPUnit not installed. Skipping unit tests.${NC}"
fi

# Summary
echo ""
echo "========================================="
echo -e "${GREEN}Test Results Summary${NC}"
echo "========================================="
echo "Total Tests: $TOTAL"
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"

if [ $FAILED -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 All tests passed successfully!${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}❌ Some tests failed. Please check the output above.${NC}"
    exit 1
fi