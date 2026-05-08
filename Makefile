.PHONY: help up down restart logs build init migrate seed test clean flush

help:
	@echo "╔════════════════════════════════════════════════╗"
	@echo "║   UAGL Project - Docker Commands               ║"
	@echo "╚════════════════════════════════════════════════╝"
	@echo ""
	@echo "Available commands:"
	@echo ""
	@echo "  make up                  Start all services"
	@echo "  make down                Stop all services"
	@echo "  make restart             Restart all services"
	@echo "  make build               Build Docker images"
	@echo ""
	@echo "  make logs                Show all logs"
	@echo "  make logs-server         Show backend logs"
	@echo "  make logs-client         Show frontend logs"
	@echo "  make logs-database       Show database logs"
	@echo ""
	@echo "  make init                Complete initialization (first run)"
	@echo "  make migrate             Run database migrations"
	@echo "  make seed                Seed database with test data"
	@echo ""
	@echo "  make artisan CMD=...     Run artisan command (e.g., make artisan CMD=tinker)"
	@echo "  make npm CMD=...         Run npm command (e.g., make npm CMD='install package'"
	@echo "  make mysql               Access MySQL CLI"
	@echo ""
	@echo "  make clean               Remove all containers"
	@echo "  make clean-volumes       Remove all data (⚠️  destructive)"
	@echo ""

# Core Commands
up:
	docker-compose up -d
	@echo "✅ Services started!"
	@echo "   Frontend: http://localhost:5173"
	@echo "   Backend:  http://localhost:8000"

down:
	docker-compose down
	@echo "✅ Services stopped!"

restart: down up
	@echo "✅ Services restarted!"

build:
	docker-compose build --no-cache
	@echo "✅ Images built!"

# Logs
logs:
	docker-compose logs -f

logs-server:
	docker-compose logs -f server

logs-client:
	docker-compose logs -f client

logs-database:
	docker-compose logs -f database

# Database
init:
	@echo "🚀 Initializing UAGL Project..."
	@cp Server/.env.docker Server/.env 2>/dev/null || true
	@cp Client/.env.docker Client/.env 2>/dev/null || true
	docker-compose up -d
	@echo "⏳ Waiting for services to be ready..."
	@sleep 5
	docker-compose exec -T server php artisan key:generate
	docker-compose exec -T server php artisan migrate --force
	@echo "✅ Initialization complete!"

migrate:
	docker-compose exec server php artisan migrate

migrate-fresh:
	docker-compose exec server php artisan migrate:fresh

seed:
	docker-compose exec server php artisan db:seed

migrate-fresh-seed: migrate-fresh seed
	@echo "✅ Database reset and seeded!"

# Laravel Commands
artisan:
	docker-compose exec server php artisan $(CMD)

tinker:
	docker-compose exec server php artisan tinker

# Frontend Commands
npm:
	docker-compose exec client npm $(CMD)

# Database Access
mysql:
	docker-compose exec database psql -U uagl_user -d uagl_db

mysql-root:
	docker-compose exec database psql -U postgres

# Cleanup
clean:
	docker-compose down --remove-orphans
	@echo "✅ Containers removed!"

clean-volumes: clean
	docker volume rm uagl_mysql_data 2>/dev/null || true
	@echo "✅ All data removed!"

flush: clean-volumes up migrate seed
	@echo "✅ Complete reset done!"

# Health check
status:
	@echo "📊 Service Status:"
	@docker-compose ps
	@echo ""
	@echo "🔗 API Test:"
	@curl -s http://localhost:8000/api/test | jq . 2>/dev/null || echo "API not responding"
