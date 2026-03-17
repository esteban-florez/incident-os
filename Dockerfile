# ==============================================================
#  Multi-stage Dockerfile
#  1. composer_deps  - instala dependencias PHP
#  2. node_builder   - instala dependencias JS y compila assets
#  3. runtime        - imagen final PHP-FPM + Nginx
# ==============================================================

# ---------- Stage 1: PHP dependencies ----------
FROM composer:2 AS composer_deps

# composer:2 no incluye ext-intl (requerida por Filament v5)
RUN apk add --no-cache icu-dev \
    && docker-php-ext-install intl

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# ---------- Stage 2: Node / frontend build ----------
FROM node:20-alpine AS node_builder

WORKDIR /app

COPY package.json package-lock.json ./
COPY vite.config.js ./
COPY resources/js ./resources/js
COPY resources/css ./resources/css

RUN npm ci

COPY . .
COPY --from=composer_deps /app/vendor ./vendor

RUN npm run build

# ---------- Stage 3: Runtime ----------
FROM php:8.3-fpm-alpine

# netcat-openbsd: necesario para el health check de DB en entrypoint.sh
RUN apk add --no-cache \
        bash \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        libpng-dev \
        libxml2-dev \
        zip \
        unzip \
        git \
        nginx \
        supervisor \
        netcat-openbsd

# PHP extensions
RUN docker-php-ext-configure zip \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        opcache \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --from=node_builder /app ./

# Permisos
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public/build \
    && chmod -R 775 storage bootstrap/cache

# fpm-pool.conf: fuerza PHP-FPM a escuchar en TCP 127.0.0.1:9000
# (Alpine usa unix socket por defecto, Nginx apunta a TCP -> "Client Closed Request")
COPY docker/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
