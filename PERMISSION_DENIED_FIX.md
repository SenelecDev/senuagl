# 🔧 Permission Denied Error - Solution Fix

## Problem

Erreur lors du démarrage de l'application sur Windows:
```
The stream or file "/app/storage/logs/laravel.log" could not be opened in append mode: 
Failed to open stream: Permission denied
```

## Root Cause

Quand tu montes le dossier `./Server:/app` sur Windows avec Docker, les permissions Unix ne sont pas préservées correctement. Le dossier `storage/` ne peut pas être écrit par le serveur web (utilisateur `www-data`).

## Solution Appliquée

### 1. Script d'Initialisation Docker (`Server/docker-entrypoint.sh`)
Nouveau script qui s'exécute au démarrage du conteneur:
- ✅ Corrige les permissions du dossier `storage/` et `bootstrap/cache/`
- ✅ Génère automatiquement la clé APP_KEY si elle n'existe pas
- ✅ Lance les migrations si la DB est prête
- ✅ Nettoie les caches

### 2. Dockerfile Modifié
- ✅ Utilise le script d'initialisation comme ENTRYPOINT
- ✅ Augmente le start_period du health check à 40s
- ✅ Utilise l'endpoint `/api/health` au lieu de `/api/test`

### 3. Fichiers `.env` Correctement Configurés
- ✅ `Server/.env.docker` avec une clé APP_KEY valide
- ✅ `.env.docker` à la racine du projet
- ✅ Script `setup-env.sh` pour initialiser les .env

## Instructions pour Ton Ami sur Windows

### Étape 1: Préparer les Fichiers d'Environnement

```bash
cd C:\Users\[NomUtilisateur]\projet-uagl

# Exécute le script de setup (ou copie manuellement)
# Sur Windows (PowerShell):
Copy-Item .\.env.docker .\.env
Copy-Item .\Server\.env.docker .\Server\.env

# Sur Windows (CMD):
copy .env.docker .env
copy Server\.env.docker Server\.env
```

### Étape 2: Nettoyer et Reconstruire

```bash
# Arrêter et nettoyer
docker-compose down -v

# Reconstruire et redémarrer
docker-compose up -d --build
```

### Étape 3: Attendre et Vérifier

```bash
# ⏳ Attendre 30-40 secondes

# Vérifier le statut
docker-compose ps

# Devrait montrer:
# uagl-postgres  ... (healthy)
# uagl-server    ... (Up)
# uagl-client    ... (Up)
```

### Étape 4: Vérifier les Logs (si besoin)

```bash
# Voir les logs du serveur
docker-compose logs server --tail=20

# Devrait contenir:
# ✅ Laravel initialization complete!
# INFO spawned: 'php-fpm' with pid ...
# INFO spawned: 'nginx' with pid ...
```

### Étape 5: Tester l'Application

```bash
# Health check
curl http://localhost:8000/api/health

# Devrait retourner:
# {"status":"healthy","timestamp":"2026-05-08T..."}

# Frontend
curl http://localhost:5173
# Devrait retourner du HTML Vue
```

## Changements Détaillés

| Fichier | Changement | Raison |
|---------|-----------|--------|
| `Server/docker-entrypoint.sh` | **Créé** | Initialise les permissions et APP_KEY |
| `Server/Dockerfile` | Ajout ENTRYPOINT | Utilise le script d'init |
| `Server/Dockerfile` | start_period 40s | Plus de temps pour démarrer |
| `Server/.env.docker` | APP_KEY valide | Pas de "encryption key not specified" |
| `.env.docker` | **Créé à la racine** | Variables docker-compose |
| `setup-env.sh` | **Créé** | Helper pour créer les .env |

## Pourquoi C'était Cassé

```
Windows/WSL2 → Docker → Permission issue
  ↓
./Server monté comme volume
  ↓
/app/storage hérité des permissions Windows
  ↓
www-data ne peut pas écrire dans les logs
  ↓
Laravel crash immédiatement
```

## Comment C'est Réparé

```
Container démarrage
  ↓
Script docker-entrypoint.sh exécuté
  ↓
chown -R www-data:www-data /app/storage ✅
chmod -R 775 /app/storage ✅
  ↓
Permissions correctes
  ↓
Laravel peut écrire les logs
  ↓
Application démarre correctement ✅
```

## Problèmes Connus Résolus

1. **Permission denied on laravel.log** → ✅ Fixé par le script d'init
2. **No application encryption key** → ✅ Clé valide dans .env.docker
3. **Health check timeout** → ✅ Augmenté à 40s start_period
4. **Database not ready** → ✅ Script attend la DB avant migrations

## Prochaines Fois

Les prochaines fois que tu redémarres, il te suffit de:
```bash
docker-compose up -d
```

Les données persisteront (volume PostgreSQL), et les permissions seront déjà correctes.

---

**Si tu utilises `docker-compose down -v`** (efface la base de données), réexécute le setup.
