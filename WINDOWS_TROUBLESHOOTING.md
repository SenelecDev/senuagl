# Windows Docker Troubleshooting Guide

## Problem: PostgreSQL Container Unhealthy

When running `docker-compose up -d --build` on Windows, you may see:
```
✘ Container uagl-postgres Error
dependency failed to start: container uagl-postgres is unhealthy
```

## Root Causes

1. **PostgreSQL startup time**: On Windows/WSL2, PostgreSQL can take longer to initialize
2. **Health check timing**: Original timeout (5s) was too short
3. **Volume permissions**: Windows file systems handle permissions differently

## Solutions Applied

The `docker-compose.yml` has been updated with more robust settings:

### Health Check Improvements
- **start_period**: Increased from 10s to 30s (allows PostgreSQL time to initialize)
- **timeout**: Increased from 5s to 10s
- **retries**: Increased from 5 to 10
- **test**: Uses explicit `pg_isready` command instead of shell variable substitution

### Environment Improvements  
- Added `POSTGRES_INITDB_ARGS` for better Unicode support
- Improved health check command to use explicit credentials

## What to Do Now

1. **Clean up old containers and volumes**:
```bash
cd /path/to/UAGL\ Project
docker-compose down -v
```

2. **Rebuild and start fresh**:
```bash
docker-compose up -d --build
```

3. **Wait for PostgreSQL to be healthy** (should take ~30-40 seconds):
```bash
docker-compose logs database
```

You should see logs like:
```
database_1 | ...
database_1 | PostgreSQL Database System is ready to accept connections.
```

4. **Verify services are running**:
```bash
docker-compose ps
```

Expected output:
```
NAME                  COMMAND                  SERVICE     STATUS
uagl-postgres         "docker-entrypoint.s…"   database    Up (healthy)
uagl-server           "docker-entrypoint.s…"   server      Up 
uagl-client           "docker-entrypoint.s…"   client      Up
```

## If Still Having Issues

### Check PostgreSQL logs
```bash
docker-compose logs database --tail=50
```

### Inspect the container
```bash
docker-compose exec database pg_isready -U uagl_user
```

### Force rebuild
```bash
docker-compose build --no-cache database
docker-compose up -d database
# Wait ~30 seconds
docker-compose up -d
```

### Nuclear option (last resort)
```bash
# Remove everything
docker-compose down -v
docker system prune -a

# Rebuild completely
docker-compose up -d --build
```

## Key Changes Made

| Setting | Before | After | Why |
|---------|--------|-------|-----|
| `start_period` | 10s | 30s | PostgreSQL needs more time on Windows |
| `timeout` | 5s | 10s | More time to respond to health checks |
| `retries` | 5 | 10 | More attempts for startup |
| Health check test | Shell variable | Explicit command | More reliable on Alpine |

## Preventive Measures

For future Docker work on Windows:
1. Always use explicit values instead of environment variable substitution in health checks
2. Give databases 30+ seconds for initialization
3. Use simple health check tests (pg_isready, ping) over complex queries
4. Monitor logs during first startup

## Expected Startup Time

- PostgreSQL: ~30-40 seconds to become healthy
- Laravel Server: ~10-15 seconds after database is ready
- Vue Client: Immediate (no health check)
- **Total**: ~50-60 seconds for full stack readiness
