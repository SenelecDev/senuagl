#!/bin/sh
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
# Cache routes figé (volume hôte / ancienne image) → 404 sur les nouvelles routes API
rm -f /var/www/bootstrap/cache/routes-*.php 2>/dev/null || true
php artisan config:clear
php artisan route:clear
exec php-fpm
