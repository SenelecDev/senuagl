#!/bin/bash

# 🐳 UAGL Docker Setup Script
# Ce script automatise le démarrage initial

set -e

echo "🚀 UAGL Docker Initialization Script"
echo "===================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Étape 1 : Copier les .env
echo -e "${YELLOW}Step 1: Setting up .env files...${NC}"
if [ ! -f "Server/.env" ]; then
    cp Server/.env.docker Server/.env
    echo -e "${GREEN}✓ Created Server/.env${NC}"
else
    echo -e "${GREEN}✓ Server/.env already exists${NC}"
fi

if [ ! -f "Client/.env" ]; then
    cp Client/.env.docker Client/.env
    echo -e "${GREEN}✓ Created Client/.env${NC}"
else
    echo -e "${GREEN}✓ Client/.env already exists${NC}"
fi

echo ""

# Étape 2 : Générer APP_KEY
echo -e "${YELLOW}Step 2: Generating Laravel APP_KEY...${NC}"
docker-compose up -d server
echo "Waiting for server to be ready..."
sleep 10
docker-compose exec -T server php artisan key:generate
echo -e "${GREEN}✓ APP_KEY generated${NC}"

echo ""

# Étape 3 : Migrations
echo -e "${YELLOW}Step 3: Running migrations...${NC}"
docker-compose exec -T server php artisan migrate --force
echo -e "${GREEN}✓ Migrations complete${NC}"

echo ""

# Étape 4 : Seeders (optionnel)
echo -e "${YELLOW}Step 4: Seeding the database (optional)...${NC}"
read -p "Run seeders? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    docker-compose exec -T server php artisan db:seed
    echo -e "${GREEN}✓ Database seeded${NC}"
fi

echo ""

# Étape 5 : Redémarrer tous les services
echo -e "${YELLOW}Step 5: Restarting all services...${NC}"
docker-compose down
docker-compose up -d
echo -e "${GREEN}✓ All services started${NC}"

echo ""
echo -e "${GREEN}✅ Setup complete!${NC}"
echo ""
echo "📍 Access your application:"
echo "   Frontend: http://localhost:5173"
echo "   Backend:  http://localhost:8000"
echo "   API:      http://localhost:8000/api"
echo ""
echo "📖 For more info, see DOCKER_SETUP.md"
echo ""
