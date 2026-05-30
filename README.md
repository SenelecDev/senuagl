# Application UAGL (Unité Administration Gestion Logistique)

L'application **UAGL** est une solution complète développée pour l'Unité Administration Gestion Logistique. 
Elle permet d'avoir une vision globale et détaillée sur la gestion des ressources humaines et le suivi budgétaire de la DSI.

## 🌟 Fonctionnalités Principales

- **Gestion des Agents de la DSI :** 
  - Suivi détaillé des agents (répartition par direction, poste, sexe).
  - Identification et suivi des personnes faisant valoir leurs droits à la retraite.
  - Gestion centralisée des avancements et des promotions.
- **Statistiques et Tableaux de bord :** Visualisation dynamique des indicateurs clés (KPI) et statistiques RH de la DSI.
- **Suivi Budgétaire :**
  - **Budget d'Exploitation :** Suivi rigoureux des prévisions, engagements et réalisations avec calcul automatique du taux d'exécution et du disponible.
  - **Budget d'Investissement :** Suivi des projets et investissements avec des outils d'analyse financière poussés (calcul automatique de la **VAN** et du **TRI**).
- **Interface Master-Detail :** Exploration fine (drill-down) de l'historique budgétaire compte par compte.

## 🛠 Stack Technique

Application **full stack** :
- **Backend (API) :** Laravel (`Server/`)
- **Frontend (Interface) :** Vue 3 + Vite + Pinia (`Client/`)
- **Base de données :** PostgreSQL
- **Environnement de développement :** Docker Compose

---

## 🚀 Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (ou Docker Engine + plugin Compose)
- Optionnel : `make` pour utiliser les raccourcis du `Makefile`

---

## ⚙️ Démarrage rapide

### 1. Configurer l'environnement

Après le clone, créer le fichier `Server/.env` à partir du template :

```bash
cp Server/.env.example Server/.env
```

Les valeurs par défaut (base de données, ports, etc.) sont déjà configurées pour fonctionner avec Docker Compose. Aucune modification n'est nécessaire pour un usage standard.

### 2. Construire et démarrer les services

```bash
docker compose up -d --build
```

Le **premier build** peut prendre plusieurs minutes (compilation des extensions PHP, téléchargement des images). Le conteneur `server` exécute automatiquement `composer install` au démarrage — le port 8000 ne répond qu'**après**.

Pour suivre la progression :

```bash
docker compose logs -f server
```

### 3. Initialiser l'application

Une fois que le backend est prêt (le log affiche `Starting Laravel development server`), dans un autre terminal :

```bash
# Générer la clé d'application Laravel
docker compose exec server php artisan key:generate

# Créer les tables et insérer les données de démonstration
docker compose exec server php artisan migrate --seed

# Installer les dépendances du client
docker compose exec client npm install
```

### 4. Accéder à l'application

| Service   | URL                              |
|-----------|----------------------------------|
| Frontend  | http://localhost:5173            |
| API       | http://localhost:8000            |
| Test API  | http://localhost:8000/api/test   |

---

### 🚀 Raccourci avec Make (optionnel)

Si `make` est installé, toutes les étapes ci-dessus se résument à **une seule commande** :

```bash
make init
```

Cette commande :
1. Crée `Server/.env` à partir de `.env.example` (si absent)
2. Build et démarre les conteneurs
3. Attend que le backend soit prêt
4. Génère la clé Laravel, exécute les migrations et le seed
5. Installe les dépendances npm du client

La commande utilisée par défaut est `docker compose`. Si votre installation ne fournit que l'ancienne CLI :

```bash
make DC="docker-compose" init
```

Voir `make help` pour la liste complète des raccourcis disponibles.

---

## 🐳 Services Docker

Définis dans `docker-compose.yml` :

| Service   | Rôle |
|----------|------|
| `db`     | PostgreSQL 16 |
| `server` | Laravel (`php artisan serve` sur le port 8000) |
| `client` | Vite (`npm run dev` sur le port 5173) |

Le réseau interne expose aussi l'alias DNS **`database`** vers le même conteneur que `db` (compatibilité avec d'anciennes configs).

### Variables de base de données

Dans `Server/.env`, pour coller à Compose :

- `DB_CONNECTION=pgsql`
- `DB_HOST=db`  
  (ou `database` grâce à l'alias réseau)
- `DB_PORT=5432`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` doivent correspondre à ceux du service `db`.

Le service `server` charge explicitement `Server/.env` via `env_file` dans `docker-compose.yml`. C'est bien ce fichier qui fait foi.

Si vous avez mis un **config cache** (`php artisan config:cache`) avec d'anciennes valeurs :

```bash
docker compose exec server php artisan config:clear
```

---

## 🔐 Connexion après seed

Le fichier `Server/database/seeders/DatabaseSeeder.php` définit un compte administrateur de test :

- **Email :** `admin@uagl.local`  
- **Mot de passe :** `password`

---

## 🚑 Dépannage rapide

- **Après clone, le conteneur `server` ne démarre pas ou reste longtemps en Starting / Restarting**  
  Vérifier les logs avec `docker compose logs -f server`. Le `composer install` est exécuté automatiquement au démarrage du backend. Après mise à jour du dépôt, refaire au besoin `docker compose up -d --build`.

- **Impossible de joindre Postgres (`could not translate host name "database"`…)**  
  Mettre `DB_HOST=db` dans `Server/.env` (ou utiliser l'alias `database` déjà prévu dans Compose), puis `config:clear` si besoin.

- **Port déjà utilisé**  
  Changer les mappings `ports:` dans `docker-compose.yml` ou libérer les ports 5432 / 8000 / 5173.

- **Vider complètement les données locales Docker**

```bash
docker compose down -v --remove-orphans
```

Puis relancer les étapes de démarrage rapide ou `make flush`.
