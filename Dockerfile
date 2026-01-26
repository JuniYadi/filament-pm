# ===============================================
# Stage 1: Builder
# Installs Composer dependencies and builds npm assets
# ===============================================
FROM composer:2.8 AS builder

WORKDIR /app

# Install Node.js and npm for building assets
# composer:2.8 is Alpine-based
RUN apk add --no-cache nodejs npm

# Install PHP extensions required for Laravel and Filament
# Need sqlite-dev for building pdo_sqlite
# Also need icu-dev for intl extension required by Filament
RUN apk add --no-cache \
    sqlite-dev \
    icu-dev \
    && docker-php-ext-install pdo pdo_sqlite intl \
    && rm -rf /var/cache/apk/*

# Copy Composer files
COPY composer.json composer.lock ./

# Install production dependencies (no dev dependencies)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --no-ansi

# Copy application files
COPY . .

# Install npm dependencies and build assets
RUN npm install \
    && npm run build

# Create .env from .env.example and generate APP key
RUN cp .env.example .env \
    && php -r "echo 'APP_KEY=base64:' . base64_encode(random_bytes(32)) . PHP_EOL;" > /tmp/key.txt \
    && grep -v "^APP_KEY=" .env > /tmp/.env.tmp \
    && cat /tmp/key.txt >> /tmp/.env.tmp \
    && mv /tmp/.env.tmp .env

# Clear bootstrap cache (will be regenerated at runtime)
RUN rm -rf bootstrap/cache/*.php

# ===============================================
# Stage 2: Production
# Slim image with only production dependencies
# ===============================================
FROM php:8.4-fpm-alpine

# Install required PHP extensions
RUN apk add --no-cache \
    curl \
    nginx \
    sqlite \
    sqlite-dev \
    libpng-dev \
    libzip-dev \
    zip \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
        intl \
        gd \
        zip \
        bcmath \
        opcache \
    && rm -rf /var/cache/apk/*

# Create www-data user and set permissions
RUN mkdir -p /var/www/html \
    && chown -R www-data:www-data /var/www/html

# Set working directory
WORKDIR /var/www/html

# Copy Composer dependencies and built assets from builder
COPY --from=builder --chown=www-data:www-data /app /var/www/html

# Set permissions for storage and cache
RUN chmod -R 775 storage bootstrap/cache || true

# Copy nginx configuration
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy entrypoint script
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copy startup script
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose port 80 for HTTP
EXPOSE 80

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Start using startup script
CMD ["/usr/local/bin/start.sh"]

# OCI image description (can be overridden by build-time args)
LABEL org.opencontainers.image.description="${DESCRIPTION:-Filament PM Application}"
