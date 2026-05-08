# Guide de Résolution - PostgreSQL Unhealthy sur Windows

## Résumé du Problème

Quand tu exécutes `docker-compose up -d --build` sur Windows, PostgreSQL dit qu'il n'est pas "healthy" (en bonne santé) et le serveur refuse de démarrer.

**Message d'erreur:**
```
✘ Container uagl-postgres Error
dependency failed to start: container uagl-postgres is unhealthy
```

## Cause Racine

Sur Windows avec Docker Desktop, PostgreSQL prend plus de temps à démarrer que sur macOS/Linux. L'ancien health check donnait seulement 5 secondes et 5 tentatives à PostgreSQL pour être prêt - c'est insuffisant.

## Solution: Modifications Apportées

J'ai modifié le `docker-compose.yml` pour:
- **Augmenter le temps de démarrage** (start_period): 10s → 30s
- **Augmenter le timeout des vérifications**: 5s → 10s  
- **Augmenter le nombre de tentatives**: 5 → 10
- **Utiliser une commande de santé plus fiable**: `pg_isready` explicite

## Étapes à Suivre Sur Windows

### 1️⃣ Ouvrir PowerShell en tant qu'administrateur

Appuie sur `Win + X` et sélectionne **"Windows PowerShell (Admin)"** ou **"Terminal (Admin)"**

### 2️⃣ Naviguer vers le projet

```powershell
cd "C:\Users\[TonNom]\projet-uagl"
```

### 3️⃣ Nettoyer les anciens conteneurs

```powershell
docker-compose down -v
```

Cette commande:
- Arrête tous les conteneurs
- Supprime les volumes (données PostgreSQL)
- Repart de zéro

### 4️⃣ Reconstruire et redémarrer

```powershell
docker-compose up -d --build
```

### 5️⃣ Attendre que PostgreSQL devienne "healthy"

C'est IMPORTANT - PostgreSQL a besoin de 30-40 secondes pour démarrer sur Windows.

Exécute cette commande pour surveiller le statut:
```powershell
docker-compose ps
```

Tu devrais voir:
```
NAME          SERVICE     STATUS
uagl-postgres database    Up 30s (healthy)  ✅
uagl-server   server      Up 15s
uagl-client   client      Up 10s
```

### 6️⃣ Vérifier les logs (si besoin)

```powershell
docker-compose logs database
```

Tu devrais voir à la fin:
```
database | PostgreSQL Database System is ready to accept connections.
```

## Commandes Utiles

```powershell
# Voir le statut de tous les services
docker-compose ps

# Voir les logs PostgreSQL
docker-compose logs database --tail=50

# Redémarrer PostgreSQL seulement
docker-compose restart database

# Arrêter tout
docker-compose down

# Arrêter tout et supprimer les données
docker-compose down -v

# Vérifier la santé de PostgreSQL manuellement
docker-compose exec database pg_isready -U uagl_user
```

## Accéder à l'Application

Une fois que tous les services sont "Up (healthy)":

- **Frontend Vue.js**: http://localhost:5173
- **Backend API**: http://localhost:8000/api/test
- **Health Check**: http://localhost:8000/api/health

## Si Ça Ne Marche Toujours Pas

### Option 1: Reconstruire sans cache

```powershell
docker-compose build --no-cache
docker-compose up -d
```

### Option 2: Nettoyer complètement et recommencer

```powershell
# Arrêter tous les conteneurs
docker-compose down -v

# Supprimer les images
docker rmi projet-uagl-client projet-uagl-server postgres:16-alpine

# Reconstruire complètement
docker-compose up -d --build
```

### Option 3: Vérifier Docker Desktop

1. Ouvre **Docker Desktop** depuis le menu Démarrer
2. Va dans **Settings** → **Resources**
3. Assure-toi qu'au moins **4 GB de RAM** sont alloués
4. Assure-toi que **WSL2** est utilisé (pas Hyper-V)

### Option 4: Vérifier les logs détaillés

```powershell
# Voir tous les logs détaillés
docker-compose logs --tail=100

# Voir les logs PostgreSQL en détail
docker-compose logs database --tail=100

# Voir les erreurs seulement
docker-compose logs | Select-String "error" -Context 3
```

## Temps d'Attente Normal

- PostgreSQL: 30-40 secondes pour démarrer ⏳
- Serveur Laravel: 10-15 secondes après DB ⏳
- Frontend Vue: Immédiat ✅
- **Total**: Environ 1 minute pour que tout soit prêt

C'est normal et c'est attendu sur Windows!

## Questions Fréquentes

**Q: Pourquoi PostgreSQL prend autant de temps sur Windows?**
A: Windows/WSL2 a une surcharge I/O plus importante que macOS/Linux pour les conteneurs Docker.

**Q: Pourquoi le health check est si important?**
A: Docker utilise le health check pour savoir si un service est vraiment prêt. Si PostgreSQL n'est pas prêt, le serveur essaie de se connecter et échoue.

**Q: Est-ce que je dois faire ça à chaque démarrage?**
A: Non, juste la première fois. Les prochaines fois, utilise simplement `docker-compose up -d`.

**Q: Comment je réinitialise la base de données?**
A: Exécute `docker-compose down -v` qui supprime le volume de données.

## Résumé des Changements

| Fichier | Changement |
|---------|-----------|
| `docker-compose.yml` | Health check times plus agressifs, start_period 30s |
| `Server/routes/api.php` | Nouvel endpoint `/api/health` |
| Ce guide | Documentation pour Windows |

Fais-moi savoir si tu rencontres d'autres problèmes! 🚀
