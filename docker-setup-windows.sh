#!/bin/bash
# Windows setup helper script
# This script properly initializes Docker for Windows

echo "🐳 UAGL Docker Setup for Windows"
echo "=================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Docker is running
echo "Checking Docker status..."
if ! docker ps > /dev/null 2>&1; then
    echo -e "${RED}✗ Docker is not running${NC}"
    echo "Please start Docker Desktop on Windows"
    exit 1
fi
echo -e "${GREEN}✓ Docker is running${NC}"
echo ""

# Clean up old containers
echo -e "${YELLOW}Step 1: Cleaning up old containers and volumes...${NC}"
docker-compose down -v 2>/dev/null || true
echo -e "${GREEN}✓ Cleanup complete${NC}"
echo ""

# Build fresh images
echo -e "${YELLOW}Step 2: Building Docker images...${NC}"
docker-compose build --no-cache
if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Build failed${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Build complete${NC}"
echo ""

# Start services
echo -e "${YELLOW}Step 3: Starting Docker services...${NC}"
docker-compose up -d
if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Failed to start services${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Services started${NC}"
echo ""

# Wait for PostgreSQL to be healthy
echo -e "${YELLOW}Step 4: Waiting for PostgreSQL to be healthy (this takes ~30 seconds)...${NC}"
for i in {1..60}; do
    STATUS=$(docker-compose ps database 2>/dev/null | grep "healthy" || echo "")
    if [ ! -z "$STATUS" ]; then
        echo -e "${GREEN}✓ PostgreSQL is healthy${NC}"
        break
    fi
    echo -n "."
    sleep 1
done

# Check if database is still not healthy
if [ -z "$STATUS" ]; then
    echo -e "${RED}✗ PostgreSQL failed to become healthy${NC}"
    echo ""
    echo "Checking PostgreSQL logs:"
    docker-compose logs database --tail=20
    exit 1
fi
echo ""

# Run migrations
echo -e "${YELLOW}Step 5: Running database migrations...${NC}"
docker-compose exec -T server php artisan migrate --force
echo ""

# Display status
echo -e "${GREEN}✓ Setup complete!${NC}"
echo ""
echo "Services status:"
docker-compose ps
echo ""
echo "Access your application:"
echo "  Frontend: http://localhost:5173"
echo "  Backend:  http://localhost:8000/api/test"
echo "  API Docs: http://localhost:8000/api/health"
echo ""
