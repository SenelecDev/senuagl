#!/bin/bash
set -e

echo "🚀 Initializing Laravel application..."

# Fix permissions on mounted volumes (Windows Docker issue)
echo "📁 Setting up directories and permissions..."
mkdir -p /app/storage/logs
mkdir -p /app/storage/app
mkdir -p /app/bootstrap/cache
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Generate APP_KEY if not exists
if [ -z "$APP_KEY" ] || [ "$APP_KEY" == "" ]; then
    echo "🔑 Generating APP_KEY..."
    php artisan key:generate --force
fi

# Run migrations if database is ready
echo "🗄️  Checking database connection..."
if php artisan tinker --execute="DB::connection()->getPDO();" 2>/dev/null; then
    echo "✅ Database is ready, running migrations..."
    php artisan migrate --force 2>/dev/null || true
else
    echo "⚠️  Database not ready yet, skipping migrations"
fi

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

echo "✅ Laravel initialization complete!"

# Start supervisord
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
