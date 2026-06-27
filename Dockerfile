###############################################################################
# Stage 1 — build: install PHP dependencies (no dev)
###############################################################################
FROM composer:latest AS builder

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
# Stage 2 — runtime: slim PHP-FPM image
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

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
