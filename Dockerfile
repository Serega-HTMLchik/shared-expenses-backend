FROM composer:2.8 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

FROM php:8.4-cli-alpine

RUN apk add --no-cache libpq-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install mbstring pcntl pdo_pgsql zip

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . ./

RUN php artisan package:discover --ansi \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
