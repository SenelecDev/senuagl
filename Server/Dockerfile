# Stage 1: Builder PHP avec Composer
FROM php:8.4-fpm-bookworm AS builder

WORKDIR /app

# Installer les dépendances système + extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    curl \
    git \
    postgresql-client \
    libpq-dev \
    libonig-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    bcmath \
    mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

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
FROM php:8.4-fpm-bookworm

WORKDIR /app

# Installer uniquement les dépendances runtime
RUN apt-get update && apt-get install -y \
    nginx \
    curl \
    postgresql-client \
    libonig5 \
    supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

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

# Copier et préparer le script d'initialisation
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Créer les dossiers nécessaires et définir les permissions initiales
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
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/api/health || exit 1

# Exposer le port HTTP
EXPOSE 80

# Utiliser le script d'initialisation
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]