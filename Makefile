# Docker Compose v2 : `docker compose`. Ancienne CLI : make DC="docker-compose" …
DC ?= docker compose

.PHONY: help up up-build down restart build logs logs-server logs-client logs-db \
	wait-backend init migrate migrate-fresh seed migrate-fresh-seed artisan npm db-shell db-root-shell \
	clean clean-volumes flush status test tinker

help:
	@echo "════════════════════════════════════════════════"
	@echo "  UAGL — commandes Docker (Makefile)"
	@echo "════════════════════════════════════════════════"
	@echo ""
	@echo "  make up              Démarrer les services (images déjà buildées)"
	@echo "  make up-build        Démarrer avec rebuild des images"
	@echo "  make down            Arrêter les services"
	@echo "  make restart         down puis up"
	@echo "  make build           docker compose build --no-cache"
	@echo ""
	@echo "  make logs            Tous les logs (-f)"
	@echo "  make logs-server     Logs backend (Laravel)"
	@echo "  make logs-client     Logs frontend (Vite)"
	@echo "  make logs-db         Logs PostgreSQL"
	@echo ""
	@echo "  make wait-backend    Attend que l API réponde (après composer dans le conteneur server)"
	@echo "  make init            .env, clé, migrations+seed, npm (Composer au démarrage du serveur)"
	@echo "  make migrate         php artisan migrate"
	@echo "  make migrate-fresh   migrate:fresh (destructif)"
	@echo "  make seed            db:seed"
	@echo ""
	@echo "  make artisan CMD=...   Ex. make artisan CMD=\"route:list\""
	@echo "  make npm CMD=...       Ex. make npm CMD=\"install\""
	@echo "  make tinker            php artisan tinker (interactif)"
	@echo ""
	@echo "  make db-shell        psql (user/db par défaut du projet)"
	@echo "  make db-root-shell   psql en superuser postgres (si besoin)"
	@echo ""
	@echo "  make clean           down --remove-orphans"
	@echo "  make clean-volumes   down + suppression des volumes (données effacées)"
	@echo "  make flush           clean-volumes puis up-build puis init applicatif"
	@echo ""
	@echo "  make status          État des conteneurs + test API /api/test"
	@echo "  make test            php artisan test dans le conteneur server"
	@echo ""


# --- Cœur ---

up:
	$(DC) up -d
	@echo "Services démarrés."
	@echo "  Frontend : http://localhost:5173"
	@echo "  Backend  : http://localhost:8000"

up-build:
	$(DC) up -d --build
	@echo "Services démarrés (images rebuild)."
	@echo "  Frontend : http://localhost:5173"
	@echo "  Backend  : http://localhost:8000"

down:
	$(DC) down
	@echo "Services arrêtés."

restart: down up

build:
	$(DC) build --no-cache
	@echo "Images buildées."

# --- Logs ---

logs:
	$(DC) logs -f

logs-server:
	$(DC) logs -f server

logs-client:
	$(DC) logs -f client

logs-db:
	$(DC) logs -f db

# --- Initialisation & base ---

# Attend que l’entrypoint du serveur ait fini composer install et lancé artisan serve
wait-backend:
	@echo "Attente du backend (HTTP 200 sur /api/test). Premier lancement : composer peut prendre plusieurs minutes…"
	@n=1; max=150; \
	while [ $$n -le $$max ]; do \
	  if curl -sf http://127.0.0.1:8000/api/test >/dev/null 2>&1; then echo "Backend prêt."; exit 0; fi; \
	  sleep 3; \
	  n=$$((n + 1)); \
	done; \
	echo "Temps maximal dépassé. Voir : docker compose logs server"; exit 1

# Premier lancement complet (après clone)
init: up-build wait-backend
	$(DC) exec -T server sh -c 'test -f .env || cp .env.example .env'
	$(DC) exec -T server php artisan key:generate --force
	$(DC) exec -T server php artisan migrate --seed --force
	$(DC) exec -T client npm install
	@echo "Initialisation terminée. Compte admin : voir README.md (section Connexion)."

migrate:
	$(DC) exec server php artisan migrate

migrate-fresh:
	$(DC) exec server php artisan migrate:fresh

seed:
	$(DC) exec server php artisan db:seed

migrate-fresh-seed: migrate-fresh seed
	@echo "Base recréée et données de seed importées."

# --- Wrappers ---

artisan:
	@test -n "$(CMD)" || (echo 'Usage: make artisan CMD="ma:commande"' && exit 1)
	$(DC) exec server php artisan $(CMD)

tinker:
	$(DC) exec server php artisan tinker

npm:
	@test -n "$(CMD)" || (echo 'Usage: make npm CMD="install"' && exit 1)
	$(DC) exec client npm $(CMD)

# PostgreSQL (service compose : db)
db-shell:
	$(DC) exec db psql -U $(DB_USERNAME) -d $(DB_DATABASE)

db-root-shell:
	$(DC) exec db psql -U postgres

# --- Nettoyage ---

clean:
	$(DC) down --remove-orphans
	@echo "Conteneurs supprimés."

# Supprime aussi les volumes déclarés dans compose (postgres, vendor, node_modules anonymisés dans le projet)
clean-volumes:
	$(DC) down -v --remove-orphans
	@echo "Conteneurs et volumes du projet supprimés."

# Reset total : données + réinstall
flush: clean-volumes up-build wait-backend
	$(DC) exec -T server sh -c 'test -f .env || cp .env.example .env'
	$(DC) exec -T server php artisan key:generate --force
	$(DC) exec -T server php artisan migrate --seed --force
	$(DC) exec -T client npm install
	@echo "Reset complet terminé."

# --- Utilitaires ---

status:
	@echo "État des services :"
	@$(DC) ps
	@echo ""
	@echo "Test API GET /api/test :"
	@curl -sS http://localhost:8000/api/test || echo "(API non joignable)"

test:
	$(DC) exec server php artisan test
