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

## 📁 Structure du projet

```
UAGL Project/
├── Client/                          # Frontend Vue 3 + Vite
│   ├── src/
│   │   ├── api/                     # Configuration Axios
│   │   ├── assets/                  # Ressources statiques
│   │   ├── components/
│   │   │   ├── budget/              # EstimationBudget, BudgetFilterBar, ProjetInvestissementTable
│   │   │   ├── charts/              # Composants graphiques
│   │   │   ├── common/              # Composants réutilisables
│   │   │   └── layout/              # Header, navigation
│   │   ├── router/                  # Définition des routes Vue Router
│   │   ├── stores/                  # Stores Pinia (état global)
│   │   │   ├── agent.js             # CRUD agents
│   │   │   ├── auth.js              # Authentification Sanctum
│   │   │   ├── avancement.js        # Avancements
│   │   │   ├── budget.js            # Budget (prévisions, engagements, réalisations, estimation)
│   │   │   ├── dashboard.js         # KPI du tableau de bord
│   │   │   ├── promotion.js         # Promotions GF / Avancements NR
│   │   │   ├── statistique.js       # Statistiques RH
│   │   │   └── ...
│   │   ├── utils/                   # Fonctions utilitaires (budgetHierarchy.js, etc.)
│   │   └── views/
│   │       ├── Agents/              # Liste + fiche détaillée agent
│   │       ├── Budget/              # Suivi budgétaire + estimation + investissements
│   │       ├── Postes/              # Organigramme des postes
│   │       ├── Dashboard.vue        # Tableau de bord KPI
│   │       ├── Statistiques.vue     # Graphiques RH
│   │       ├── Promotions.vue       # Gestion des promotions GF
│   │       ├── Avancements.vue      # Gestion des avancements NR
│   │       └── NotesAppreciation.vue
│   └── Dockerfile
│
├── Server/                          # Backend Laravel
│   ├── app/
│   │   ├── Http/Controllers/Api/    # Contrôleurs REST
│   │   ├── Models/                  # Modèles Eloquent
│   │   │   └── Budget/              # Compte, BudgetPrevision, Realisation, Engagement, Investissement
│   │   └── Services/                # Logique métier
│   ├── database/
│   │   ├── migrations/              # Schéma de la base
│   │   └── seeders/                 # Données de démonstration
│   ├── routes/api.php               # Définition des routes API
│   ├── docker-entrypoint.sh         # Script de démarrage (composer install auto)
│   └── Dockerfile
│
├── docker-compose.yml               # Orchestration des 3 services
├── Makefile                         # Raccourcis (make init, make flush, etc.)
└── README.md
```

---

## 🗄️ Architecture de la base de données

### Module RH — Agents

| Table | Description | Clé primaire |
|-------|-------------|-------------|
| `agents` | Fiche agent (identité, poste, GF/NR, direction, département, service, cellule)  | `matricule` (string) |
| `gfs` | Groupes Fonctionnels (échelle de classification) | `id_gf` (string) |
| `nrs` | Niveaux de Rémunération | `id_nr` (string) |
| `unites` | Organigramme hiérarchique (Direction → Département → Service → Cellule) | `id_unite` (string) |
| `postes` | Postes rattachés à une unité, avec tube GF min/max | `id_post` (string) |
| `avancements` | Historique des changements de GF et/ou NR d'un agent | `id` (auto) |
| `notes_appreciation` | Notes annuelles par agent (note + commentaire) | `id` (auto), unique `(matricule, annee)` |

### Module Budget

| Table | Description | Clé primaire |
|-------|-------------|-------------|
| `comptes` | Plan comptable hiérarchique (comptes-section `SECTION-*` → comptes détail) | `id` (auto), unique `numero` |
| `budget_previsions` | Montant prévu par compte, année et mois | `id` (auto) |
| `engagements` | Dépenses engagées (commandes passées) avec date et observation | `id` (auto) |
| `realisations` | Dépenses réalisées (factures réglées) avec date et observation | `id` (auto) |
| `projet_investissements` | Projets travaux avec coût, bailleur, financement propre/extérieur | `id` (auto), unique `(code_projet, annee)` |
| `investissements` | Résultats de calculs financiers (VAN, TRI, DRCI) | `id` (auto) |

### Relations principales

```
unites (hiérarchie parent/enfant)
   └── postes
        └── agents ──→ gfs (GF actuel)
             │         └── nrs (NR actuel)
             ├── avancements (historique GF/NR)
             └── notes_appreciation

comptes (hiérarchie parent/enfant : SECTION-* → comptes détail)
   ├── budget_previsions
   ├── engagements
   └── realisations
```

---

## 🔌 Routes API

Toutes les routes (sauf `/test`, `/health` et `login`) nécessitent une authentification via **Laravel Sanctum** (header `Authorization: Bearer <token>`).

### Authentification

| Méthode | Route | Description |
|---------|-------|-------------|
| `POST` | `/api/login` | Connexion (email + password) → retourne un token |
| `GET` | `/api/me` | Utilisateur connecté |
| `POST` | `/api/logout` | Déconnexion |

### Agents

| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/api/agents` | Liste des agents |
| `POST` | `/api/agents` | Créer un agent |
| `GET` | `/api/agents/{id}` | Détail d'un agent |
| `PUT` | `/api/agents/{id}` | Modifier un agent |
| `DELETE` | `/api/agents/{id}` | Supprimer un agent |
| `GET` | `/api/agents/{matricule}/avancements` | Historique avancements d'un agent |
| `POST` | `/api/agents/{agent}/photo` | Upload photo agent |
| `POST` | `/api/agents/{agent}/documents` | Upload document agent |

### Organisation

| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/api/unites` | Liste des unités |
| `GET` | `/api/postes` | Liste des postes |
| `GET` | `/api/postes-vacants` | Postes vacants |
| `GET` | `/api/postes-arbre` | Arbre hiérarchique des postes |
| `GET` | `/api/gfs` | Liste des Groupes Fonctionnels |
| `GET` | `/api/nrs` | Liste des Niveaux de Rémunération |

### Promotions & Avancements

| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/api/promotions/liste-priorite-gf` | Liste de priorité GF (toutes directions) |
| `GET` | `/api/promotions/liste-priorite-gf/{directionId}/{annee}` | Liste de priorité GF par direction |
| `POST` | `/api/promotions/promouvoir` | Exécuter une promotion GF |
| `GET` | `/api/avancements/liste-priorite-nr` | Liste de priorité NR (toutes directions) |
| `GET` | `/api/avancements/liste-priorite-nr/{directionId}/{annee}` | Liste de priorité NR par direction |
| `POST` | `/api/avancements/avancer` | Exécuter un avancement NR |
| `GET/POST/PUT/DELETE` | `/api/avancements` | CRUD avancements |

### Notes d'appréciation

| Méthode | Route | Description |
|---------|-------|-------------|
| `GET/POST/PUT/DELETE` | `/api/notes-appreciation` | CRUD notes |
| `GET` | `/api/notes-appreciation/agent/{matricule}` | Notes d'un agent |
| `GET` | `/api/notes-appreciation/annee/{annee}` | Notes d'une année |

### Statistiques & Dashboard

| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/api/dashboard/kpi` | KPI du tableau de bord |
| `GET` | `/api/statistiques/pyramide-ages` | Pyramide des âges |
| `GET` | `/api/statistiques/repartition-hf` | Répartition H/F |
| `GET` | `/api/statistiques/repartition-hf-par-service` | Répartition H/F par service |
| `GET` | `/api/statistiques/departs-retraite` | Départs en retraite |
| `GET` | `/api/statistiques/plafonnement-anomalies` | Anomalies de plafonnement |
| `GET` | `/api/statistiques/agents-plafonnes` | Agents plafonnés |

### Budget

| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/api/budget` | Prévisions, engagements et réalisations (`?annee=2026`) |
| `POST` | `/api/budget` | Créer une prévision, engagement ou réalisation (`type` dans le body) |
| `PUT` | `/api/budget/{type}/{id}` | Modifier (`type` = prevision, realisation ou engagement) |
| `DELETE` | `/api/budget/{type}/{id}` | Supprimer |
| `GET` | `/api/budget/referentiels` | Liste des comptes |
| `POST` | `/api/budget/comptes` | Créer un nouveau compte |
| `GET` | `/api/budget/estimation` | Estimation budgétaire par extrapolation (`?annee=2026`) |

### Investissements

| Méthode | Route | Description |
|---------|-------|-------------|
| `GET/POST/PUT/DELETE` | `/api/investissements` | CRUD investissements |
| `POST` | `/api/investissements/calculate` | Calculer VAN, TRI, DRCI |
| `GET/POST/PUT/DELETE` | `/api/projet-investissements` | CRUD projets travaux |

---

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

Le service `server` charge `Server/.env` via `env_file` dans `docker-compose.yml`.

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
