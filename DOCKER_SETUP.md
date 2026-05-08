# 🐳 UAGL Project - Docker Setup Guide

## 📋 Prérequis
- Docker Desktop (v4.0+)
- Docker Compose (v2.0+)
- Git

### Vérifier l'installation
```bash
docker --version
docker-compose --version
```

---

## 🚀 Démarrage Rapide

### 1️⃣ Initialiser le projet
```bash
# Cloner/aller au répertoire
cd /Users/xalifa/UAGL\ Project

# Copier les fichiers .env
cp Server/.env.docker Server/.env
cp Client/.env.docker Client/.env
```

### 2️⃣ Générer la clé APP_KEY (Backend)
```bash
# Générer une clé Laravel
docker-compose run --rm server php artisan key:generate

# OU manuellement : créer une clé base64
# Remplacer APP_KEY= dans Server/.env par une valeur comme base64:xxxxxxxxxxxx
```

### 3️⃣ Démarrer les conteneurs
```bash
docker-compose up -d
```

✅ Les 3 services vont démarrer :
- **Backend Laravel** : http://localhost:8000
- **Frontend Vue** : http://localhost:5173
- **PostgreSQL** : localhost:5432

### 4️⃣ Initialiser la base de données
```bash
# Exécuter les migrations
docker-compose exec server php artisan migrate

# (Optionnel) Seeder avec des données de test
docker-compose exec server php artisan db:seed
```

### 5️⃣ Tester l'API
```bash
# Depuis votre terminal
curl http://localhost:8000/api/test

# Devrait retourner :
# {"message":"API OK"}
```

### 6️⃣ Accéder à l'application
- **Frontend** : http://localhost:5173
- **API** : http://localhost:8000/api
- **Test de connexion** : http://localhost:8000/api/test

---

## 📋 Commandes Utiles

### Gestion des conteneurs
```bash
# Voir l'état des services
docker-compose ps

# Voir les logs
docker-compose logs -f              # Tous les services
docker-compose logs -f server       # Backend uniquement
docker-compose logs -f client       # Frontend uniquement
docker-compose logs -f database     # Database uniquement

# Arrêter
docker-compose down

# Redémarrer un service
docker-compose restart server
docker-compose restart client

# Arrêter et supprimer les volumes
docker-compose down -v              # ⚠️ Supprime les données !
```

### Développement Backend

```bash
# Exécuter des commandes Artisan
docker-compose exec server php artisan {command}

# Exemples :
docker-compose exec server php artisan tinker
docker-compose exec server php artisan migrate:fresh --seed
docker-compose exec server php artisan make:model AgentModel
docker-compose exec server php artisan queue:work

# Accéder au shell PHP
docker-compose exec server sh
```

### Développement Frontend

```bash
# Les changements se mettent à jour automatiquement (hot reload)

# Exécuter des commandes npm
docker-compose exec client npm run build
docker-compose exec client npm install package-name

# Accéder au shell Node
docker-compose exec client sh
```

### Accès à la base de données

```bash
# Accéder au CLI PostgreSQL
docker-compose exec database psql -U uagl_user -d uagl_db

# Avec l'utilisateur postgres (root)
docker-compose exec database psql -U postgres

# Exporter la base
docker-compose exec database pg_dump -U uagl_user uagl_db > backup.sql

# Importer une base
docker-compose exec -T database psql -U uagl_user uagl_db < backup.sql

# Quelques commandes utiles en PostgreSQL CLI :
# \l - lister les bases
# \dt - lister les tables
# \du - lister les utilisateurs
# \q - quitter
```

---

## 🔧 Configuration

### Modifier les ports

Éditer `docker-compose.yml` :
```yaml
ports:
  - "YOUR_PORT:CONTAINER_PORT"
```

Puis redémarrer : `docker-compose up -d`

### Modifier les variables d'environnement

Éditer :
- `Server/.env` - Configuration Backend
- `Client/.env` - Configuration Frontend

Puis redémarrer : `docker-compose restart`

### Ajouter Redis (Cache/Queue avancée)
Décommenter la section Redis dans `docker-compose.yml` et passer à `QUEUE_CONNECTION=redis`

---

## 🐛 Troubleshooting

### ❌ "Port already in use"
```bash
# Trouver et tuer le processus
lsof -i :8000
lsof -i :5173
lsof -i :3306

# Ou simplement modifier les ports dans docker-compose.yml
```

### ❌ "Cannot connect to database"
```bash
# Vérifier la santé
docker-compose ps

# Voir les logs PostgreSQL
docker-compose logs database

# Redémarrer la DB
docker-compose restart database

# Vérifier la connexion
docker-compose exec database psql -U uagl_user -d uagl_db -c 'SELECT 1'
```

### ❌ "Frontend can't reach API"
- Vérifier que le backend est actif : `curl http://localhost:8000/api/test`
- Vérifier `VITE_API_URL` dans `Client/.env`
- Vérifier les CORS dans `Server/config/cors.php`

### ❌ "Node modules issues"
```bash
# Régénérer
docker-compose exec client rm -rf node_modules
docker-compose up -d client
```

### ❌ "Build fails"
```bash
# Rebuilder les images
docker-compose build --no-cache

# Puis redémarrer
docker-compose up -d
```

---

## 🚀 Production (Optionnel)

Pour déployer en production, utiliser `.env.production` et modifier :
- `APP_DEBUG=false`
- `APP_ENV=production`
- Utiliser une vraie DB externe (pas SQLite)
- Configurer un vrai mailer (SendGrid, Mailgun, etc.)

---

## 📚 Ressources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [Laravel Docker Best Practices](https://laravel.com/docs/docker)
- [Vue 3 Dev Server](https://vitejs.dev/)

---

**Questions ?** Voir les logs ou créer une issue ! 🎯
