# Projet UAGL

Application **full stack** : API **Laravel** (`Server/`), interface **Vue 3 + Vite** (`Client/`), base **PostgreSQL**. L’environnement de développement est fourni via **Docker Compose**.

---

## Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (ou Docker Engine + plugin Compose)
- Optionnel : `make` pour utiliser le `Makefile` à la racine

La commande utilisée par défaut est `docker compose`. Si ton installation ne fournit que l’ancienne CLI, utilise par exemple :

```bash
make DC="docker-compose" up
```

(À adapter partout ou définir un alias.)

---

## Démarrage rapide

À la racine du dépôt :

```bash
docker compose up -d --build
```

Puis **initialisation une seule fois** (dépendances PHP, `.env`, clé d’application, migrations + seed, dépendances npm) :

```bash
make init
```

Ou à la main :

```bash
docker compose exec server composer install
docker compose exec server sh -c 'test -f .env || cp .env.example .env'
docker compose exec server php artisan key:generate
docker compose exec server php artisan migrate --seed
docker compose exec client npm install
```

**URLs :**

| Service    | URL                        |
|-----------|----------------------------|
| Frontend  | http://localhost:5173      |
| API       | http://localhost:8000      |
| Health    | http://localhost:8000/api/health |

---

## Makefile (`make`)

Résumé des cibles utiles :

| Commande              | Rôle |
|----------------------|------|
| `make up`            | Démarrer les conteneurs |
| `make up-build`      | Build des images + démarrage |
| `make down`          | Arrêter |
| `make init`          | Première installation complète dans les conteneurs |
| `make migrate`       | Migrations |
| `make seed`          | Seed seul |
| `make logs-db`       | Logs Postgres (service `db`) |
| `make db-shell`      | Shell `psql` (utilisateur / base par défaut du projet) |
| `make clean-volumes` | Supprime conteneurs **et** volumes (données effacées) |
| `make flush`         | Comme un reset : volumes + rebuild + `init` applicatif |
| `make artisan CMD="…"` | Exécute `php artisan …` dans `server` |
| `make npm CMD="…"`   | Exécute `npm …` dans `client` |

Voir `make help` pour la liste complète.

---

## Services Docker

Définis dans `docker-compose.yml` :

| Service   | Rôle |
|----------|------|
| `db`     | PostgreSQL 16 |
| `server` | Laravel (`php artisan serve` sur le port 8000) |
| `client` | Vite (`npm run dev` sur le port 5173) |

Le réseau interne expose aussi l’alias DNS **`database`** vers le même conteneur que `db` (compatibilité avec d’anciennes configs).

### Variables de base de données (recommandé)

Dans `Server/.env`, pour coller à Compose :

- `DB_CONNECTION=pgsql`
- `DB_HOST=db`  
  (ou `database` grâce à l’alias réseau)
- `DB_PORT=5432`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` alignés sur ce que tu as mis dans `docker-compose.yml` / variables d’environnement.

Compose injecte déjà `DB_HOST=db` pour le service `server` ; si tu as mis un **config cache** (`php artisan config:cache`) avec d’anciennes valeurs :

```bash
docker compose exec server php artisan config:clear
```

---

## Connexion après seed

Le fichier `Server/database/seeders/DatabaseSeeder.php` :

- **Email :** `admin@uagl.local`  
- **Mot de passe :** `password`



---

## Dépannage rapide

- **Impossible de joindre Postgres (`could not translate host name "database"`…)**  
  Mets `DB_HOST=db` dans `Server/.env` ou utilise l’alias `database` (déjà prévu dans Compose), puis `config:clear` si besoin.

- **Port déjà utilisé**  
  Change les mappings `ports:` dans `docker-compose.yml` ou libère les ports 5432 / 8000 / 5173.

- **Vider complètement les données locales Docker**

```bash
make clean-volumes
```

Puis relance `make init` ou `make flush`.

---

## Dossiers applicatifs

- **Backend Laravel :** voir `Server/README.md`
- **Frontend Vue / Vite :** voir `Client/README.md`

---

## Licence


