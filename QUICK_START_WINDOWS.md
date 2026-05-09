# 🚀 UAGL Project - Windows Quick Start

## ⚡ Super Rapide (2 minutes)

### 1️⃣ Copie les fichiers .env

**PowerShell (Admin):**
```powershell
cd C:\Users\[TonNom]\projet-uagl
Copy-Item .\.env.docker .\.env
Copy-Item .\Server\.env.docker .\Server\.env
```

**Ou CMD:**
```cmd
cd C:\Users\[TonNom]\projet-uagl
copy .env.docker .env
copy Server\.env.docker Server\.env
```

### 2️⃣ Lance le projet

```bash
docker-compose down -v
docker-compose up -d --build
```

### 3️⃣ Attends et Vérifie

Attendre **30-40 secondes** (c'est NORMAL sur Windows!)

```bash
docker-compose ps
```

Tu devrais voir tous les services `Up`:
```
uagl-postgres    ... Up ... (healthy)
uagl-server      ... Up ...
uagl-client      ... Up ...
```

### 4️⃣ Accès à l'Application

- **Frontend**: http://localhost:5173 🎨
- **Backend API**: http://localhost:8000/api/test ⚙️
- **Health Check**: http://localhost:8000/api/health 💚

## 📋 Si Ça Ne Marche Pas

### Erreur: "Permission denied on laravel.log"
**Solution**: C'est corrigé! Les fichiers `.env.docker` incluent maintenant une clé APP_KEY valide et le script d'initialisation fixe les permissions automatiquement.

### Erreur: "container uagl-postgres is unhealthy"
**Solution**: Attendre 40+ secondes. Windows est plus lent pour démarrer PostgreSQL.

Commande pour voir le statut:
```bash
docker-compose logs database
```

Attendre qu'il dise:
```
PostgreSQL Database System is ready to accept connections.
```

### Erreur: YAML syntax error
**Solution**: C'est déjà corrigé dans le docker-compose.yml.

### Les logs disent "ERROR starting userauth"
**Solution**: Attendre et réessayer. Peut-être que PHP-FPM demande plus de temps.

```bash
docker-compose restart server
docker-compose logs server --tail=30
```

## 🔄 Commandes Utiles

```bash
# Voir tous les logs
docker-compose logs

# Voir logs d'un seul service
docker-compose logs server
docker-compose logs database
docker-compose logs client

# Restart un service
docker-compose restart server

# Entrer dans le conteneur
docker-compose exec server bash
docker-compose exec database psql -U uagl_user -d uagl_db

# Arrêter tout
docker-compose down

# Arrêter et supprimer les données
docker-compose down -v

# Reconstruire une image
docker-compose build --no-cache server
docker-compose up -d server
```

## 📊 Architecture

```
Windows Machine
    ↓
Docker Desktop (WSL2)
    ↓
┌─────────────────────────┐
│   Docker Network        │
├─────────────────────────┤
│ PostgreSQL (5432)       │
│ Laravel Server (8000)   │
│ Vue Frontend (5173)     │
└─────────────────────────┘
```

## 🔐 Credentials Par Défaut

- **Database**:
  - Host: `database` (ou `localhost:5432`)
  - User: `uagl_user`
  - Password: `user_password_123`
  - Database: `uagl_db`

- **App Key**: `base64:QxQF0eWrNqlH5Tr7y8JkWf5fQqW7g0TzJ3bKmN2vP4M=`

## ✅ Checklist

- [ ] Docker Desktop est installé et en marche
- [ ] J'ai copié les fichiers `.env.docker` → `.env`
- [ ] J'ai exécuté `docker-compose up -d --build`
- [ ] J'ai attendu 40 secondes
- [ ] `docker-compose ps` montre tous les services "Up"
- [ ] Je peux accéder à http://localhost:5173 (Frontend)
- [ ] Je peux accéder à http://localhost:8000/api/health (Backend)

## 📚 Documentation Complète

Pour des explications détaillées, voir:
- `SOLUTION_POSTGRESQL_WINDOWS.md` - PostgreSQL health check
- `PERMISSION_DENIED_FIX.md` - Permission denied error
- `DOCKER_SETUP.md` - Setup détaillé
- `WINDOWS_TROUBLESHOOTING.md` - Troubleshooting

## 🆘 Besoin d'Aide?

1. **Cherche d'abord** si le problème est dans la liste "Si Ça Ne Marche Pas"
2. **Regarde les logs**: `docker-compose logs`
3. **Attends plus longtemps**: Parfois Windows est juste lent
4. **Restarting tout**: `docker-compose down -v && docker-compose up -d --build`

---

**Happy Coding! 🎉**
