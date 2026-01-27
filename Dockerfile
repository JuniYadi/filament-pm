# ===============================================
# Stage 1: Builder
# Installs Composer dependencies and builds npm assets
# ===============================================
FROM composer:2.8 AS builder

WORKDIR /app

# Install Node.js for asset building
RUN apk add --no-cache nodejs npm

# Install PHP extensions required for Composer dependencies (intl for Filament)
RUN apk add --no-cache icu-dev \
    && docker-php-ext-install intl \
    && rm -rf /var/cache/apk/*

# Copy Composer files
COPY composer.json composer.lock ./

# Install production dependencies
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
RUN npm install && npm run build

# Create .env from .env.example and generate APP key (only if not using K8s ConfigMap/Secret)
# If APP_KEY is provided via environment variable, skip .env generation
RUN if [ -z "$APP_KEY" ]; then \
    cp .env.example .env \
    && php -r "echo 'APP_KEY=base64:' . base64_encode(random_bytes(32)) . PHP_EOL;" > /tmp/key.txt \
    && grep -v "^APP_KEY=" .env > /tmp/.env.tmp \
    && cat /tmp/key.txt >> /tmp/.env.tmp \
    && mv /tmp/.env.tmp .env; \
    fi

# Clear bootstrap cache
RUN rm -rf bootstrap/cache/*.php

# ===============================================
# Stage 2: Production (using php-base)
# ===============================================
FROM ghcr.io/juniyadi/php-base:8.5

# Enable required extensions via environment variables
ENV PHP_EXT_sqlite=1
ENV PHP_EXT_bcmath=1

# Copy built application from builder
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

# Entrypoint handles SQLite setup, migrations
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Start script handles PHP-FPM and Nginx startup
CMD ["/usr/local/bin/start.sh"]

# OCI image description (can be overridden by build-time args)
LABEL org.opencontainers.image.description="${DESCRIPTION:-Filament PM Application}"
