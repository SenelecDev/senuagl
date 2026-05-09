#!/bin/bash
# Setup script to properly configure environment for Docker

set -e

echo "🚀 UAGL Docker Environment Setup"
echo "================================="
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env from .env.docker..."
    cp .env.docker .env
    echo "✅ .env created"
else
    echo "ℹ️  .env already exists, skipping creation"
fi

# Check if Server/.env exists
if [ ! -f Server/.env ]; then
    echo "📝 Creating Server/.env from Server/.env.docker..."
    cp Server/.env.docker Server/.env
    echo "✅ Server/.env created"
else
    echo "ℹ️  Server/.env already exists"
fi

# Check if Client/.env exists  
if [ ! -f Client/.env ]; then
    echo "📝 Creating Client/.env from Client/.env.docker..."
    cp Client/.env.docker Client/.env 2>/dev/null || echo "⚠️  Client/.env.docker not found, skipping"
    echo "✅ Client/.env created"
else
    echo "ℹ️  Client/.env already exists"
fi

echo ""
echo "✅ Environment setup complete!"
echo ""
echo "Next steps:"
echo "  1. Review the generated .env files if needed"
echo "  2. Run: docker-compose up -d --build"
echo "  3. Wait 30-40 seconds for services to be ready"
echo "  4. Check: docker-compose ps"
echo ""
