# 1) Build (Composer)
FROM composer:2 AS build
WORKDIR /app
COPY composer.json composer.lock /app/
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress
COPY . /app
RUN composer dump-autoload --optimize

# 2) Runtime (PHP-FPM + Caddy web)
FROM php:8.3-fpm-alpine
RUN apk add --no-cache caddy bash \
 && docker-php-ext-install opcache pdo pdo_mysql
WORKDIR /var/www/html
COPY --from=build /app /var/www/html

# Caddy sirve /public y pasa PHP a FPM
COPY <<'CADDY' /etc/caddy/Caddyfile
:8080
root * /var/www/html/public
encode gzip
php_fastcgi 127.0.0.1:9000
file_server
CADDY

CMD php-fpm -D && caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
