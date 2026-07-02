###############################################################################
# Stage 1 — build: install PHP dependencies (no dev)
###############################################################################
FROM php:8.5-fpm AS builder

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --prefer-dist

COPY . .

RUN composer dump-autoload --no-dev --optimize

###############################################################################
# Stage 2 — testing: runtime extensions + composer + dev dependencies baked in
###############################################################################
FROM php:8.5-fpm AS testing

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY composer.json composer.lock ./

RUN composer install --no-interaction --prefer-dist --no-autoloader

COPY . .

RUN composer dump-autoload --optimize

RUN mkdir -p /var/www/storage/framework/views \
             /var/www/storage/framework/cache/data \
             /var/www/storage/logs \
             /var/www/storage/api-docs \
    && touch /var/www/.env

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

CMD ["php-fpm"]

###############################################################################
# Stage 3 — runtime: slim PHP-FPM image
###############################################################################
FROM php:8.5-fpm AS runtime

# System deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY --from=builder /app .

RUN mkdir -p /var/www/storage/framework/views \
             /var/www/storage/framework/cache/data \
             /var/www/storage/logs \
             /var/www/storage/api-docs

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]

