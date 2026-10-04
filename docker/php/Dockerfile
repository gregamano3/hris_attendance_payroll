# syntax=docker/dockerfile:1

############################
# Base: PHP-FPM + extensions
############################
FROM php:8.4-fpm-alpine AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

RUN apk add --no-cache \
        bash fcgi git icu-libs libpq libzip libpng freetype libjpeg-turbo unzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
        icu-dev libpq-dev libzip-dev libpng-dev freetype-dev libjpeg-turbo-dev linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl opcache pcntl pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
ENV PHP_FPM_MAX_CHILDREN=20

WORKDIR /var/www/html

############################
# Development image
############################
FROM base AS dev

ARG UID=1000
ARG GID=1000
RUN apk add --no-cache shadow \
    && usermod -u "${UID}" www-data \
    && groupmod -g "${GID}" www-data \
    && echo "opcache.validate_timestamps=1" > /usr/local/etc/php/conf.d/zz-dev.ini

USER www-data

############################
# Front-end assets build
############################
FROM node:26-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
COPY . .
RUN npm run build

############################
# Production image
############################
FROM base AS prod

COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
COPY --from=assets /app/node_modules ./node_modules

RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && php artisan adminlte:install --only=assets --only=vendor_assets --force -n \
    && rm -rf node_modules \
    && chown -R www-data:www-data storage bootstrap/cache public/vendor

USER www-data

HEALTHCHECK --interval=30s --timeout=3s CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

CMD ["sh", "-c", "php artisan optimize && php-fpm"]

############################
# Web server (Caddy, automatic HTTPS) serving the public assets
############################
FROM caddy:2-alpine AS web

COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY --from=prod /var/www/html/public /var/www/html/public
