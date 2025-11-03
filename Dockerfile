# syntax=docker/dockerfile:1.7

FROM composer:2.7 AS composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

FROM node:20-alpine AS node
WORKDIR /var/www/html
COPY package.json package-lock.json* vite.config.js tailwind.config.js postcss.config.js ./
RUN npm install --legacy-peer-deps
COPY resources ./resources
RUN npm run build

FROM php:8.3-fpm-alpine AS php
WORKDIR /var/www/html

RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    git \
    curl

RUN docker-php-ext-install intl pdo_mysql zip

COPY --from=composer /var/www/html/vendor ./vendor
COPY --from=node /var/www/html/public/build ./public/build
COPY . .

RUN addgroup -S laravel && adduser -S laravel -G laravel
RUN chown -R laravel:laravel storage bootstrap/cache
USER laravel
RUN php artisan config:clear && php artisan route:clear
USER root

FROM php:8.3-fpm-alpine AS runtime
WORKDIR /var/www/html

RUN apk add --no-cache nginx supervisor icu libzip

COPY --from=php /usr/local/etc/php /usr/local/etc/php
COPY --from=php /usr/local/bin/php /usr/local/bin/php
COPY --from=php /usr/local/bin/composer /usr/local/bin/composer
COPY --from=php /var/www/html /var/www/html

RUN addgroup -S laravel && adduser -S laravel -G laravel
RUN chown -R laravel:laravel /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

EXPOSE 8080

USER laravel

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
