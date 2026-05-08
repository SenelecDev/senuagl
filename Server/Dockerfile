# Stage 1: Builder PHP avec Composer
FROM php:8.4-fpm-alpine AS builder

WORKDIR /app

# Installer les dépendances système + extensions PHP nécessaires
RUN apk add --no-cache \
    curl \
    git \
    postgresql-client \
    postgresql-dev \
    oniguruma-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    bcmath \
    mbstring

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les fichiers Composer
COPY composer.json composer.lock ./

# Installer les dépendances PHP
RUN composer install \
    --no-dev \
    --no-scripts \
    --optimize-autoloader \
    --no-interaction

# ============================================================
# Stage 2: Image de production
# ============================================================
FROM php:8.4-fpm-alpine

WORKDIR /app

# Installer uniquement les dépendances runtime
RUN apk add --no-cache \
    nginx \
    curl \
    postgresql-client \
    oniguruma \
    supervisor

# Copier les extensions PHP compilées
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Copier les dépendances Composer
COPY --from=builder /app/vendor /app/vendor

# Copier l'application
COPY . .

# Copier la configuration Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

# Copier la configuration Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Créer les dossiers nécessaires et définir les permissions
RUN mkdir -p \
    /var/log/supervisor \
    /app/bootstrap/cache \
    /app/storage/logs \
    /app/storage/app \
    && chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Rendre Artisan exécutable
RUN chmod +x /app/artisan

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/api/test || exit 1

# Exposer le port HTTP
EXPOSE 80

# Lancer Supervisor
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]