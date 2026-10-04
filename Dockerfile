FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js ./

RUN npm run build

FROM dunglas/frankenphp:php8.4-bookworm

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends ffmpeg \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions \
        pdo_pgsql \
        redis \
        pcntl \
        bcmath \
        intl \
        gd \
        zip \
        exif \
        opcache

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-levl.ini

RUN cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php \
       public/frankenphp-worker.php \
    && chmod 0644 public/frankenphp-worker.php

RUN composer dump-autoload \
        --no-dev \
        --optimize \
        --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && ln -sfn /app/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache public/storage

COPY docker/entrypoint.sh /usr/local/bin/levl-entrypoint
RUN chmod +x /usr/local/bin/levl-entrypoint

USER www-data

ENTRYPOINT ["/usr/local/bin/levl-entrypoint"]
