# ===============================================
# Stage 1: Builder
# Installs Composer dependencies and builds npm assets
# ===============================================
FROM composer:2.8 AS builder

WORKDIR /app

# Install PHP extensions required for Laravel
RUN docker-php-ext-install pdo pdo_sqlite

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

# Clear and cache configs
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# ===============================================
# Stage 2: Production
# Slim image with only production dependencies
# ===============================================
FROM php:8.2-fpm-alpine

# Install required PHP extensions
RUN apk add --no-cache \
    curl \
    nginx \
    sqlite \
    libpng-dev \
    libzip-dev \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
        gd \
        zip \
        bcmath \
        opcache

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

# Expose port 80 for HTTP
EXPOSE 80

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Start PHP-FPM and Nginx
CMD ["php-fpm", "-D", "&&", "nginx", "-g", "daemon off;"]
