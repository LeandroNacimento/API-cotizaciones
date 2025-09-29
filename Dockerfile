# 1) Build (Composer)
FROM composer:2 AS build
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1
WORKDIR /app

# Copiamos manifests primero para cachear mejor
COPY composer.json composer.lock /app/
# Evitamos ejecutar scripts de Composer durante el build (pueden fallar sin .env)
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress

# Ahora sí, copiamos el resto del proyecto
COPY . /app
RUN composer dump-autoload --optimize --classmap-authoritative

# 2) Runtime (PHP-FPM + Caddy web)
FROM php:8.3-fpm-alpine
RUN apk add --no-cache caddy bash \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql opcache

WORKDIR /var/www/html
COPY --from=build /app /var/www/html

# Permisos para storage y cache (Laravel)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
 && find /var/www/html/storage -type d -exec chmod 775 {} \; \
 && find /var/www/html/storage -type f -exec chmod 664 {} \; \
 && chmod -R 775 /var/www/html/bootstrap/cache

# Caddy sirve /public y pasa PHP a FPM
COPY <<'CADDY' /etc/caddy/Caddyfile
:{$PORT}
root * /var/www/html/public
encode gzip
php_fastcgi 127.0.0.1:9000
file_server
CADDY

EXPOSE 8080
CMD php-fpm -D && caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
