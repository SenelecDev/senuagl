FROM php:8.2-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configuration OPcache pour performance maximale
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Configuration PHP-FPM optimisée
RUN echo "[www]" > /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm.min_spare_servers = 3" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm.max_spare_servers = 10" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm.max_requests = 500" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "request_terminate_timeout = 60" >> /usr/local/etc/php-fpm.d/zz-custom.conf

# Configuration PHP générale
RUN echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=60" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=20M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/custom.ini

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Optimisations Laravel au build
RUN php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

# Donner les droits à Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]