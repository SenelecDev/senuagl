#!/bin/sh
set -e

# Sur un clone sans vendor/, le volume nommé server_vendor est vide : artisan échoue
# tant que les dépendances Composer ne sont pas installées dans ce volume.
echo "composer install (premier lancement ou volume vide : peut prendre plusieurs minutes)…"
composer install --no-interaction --prefer-dist --optimize-autoloader

exec php artisan serve --host=0.0.0.0 --port=8000
