#!/bin/sh
set -e

# ===============================================
# Docker Entrypoint Script for Filament PM
# ===============================================

# Ensure storage directory exists and is writable
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Fix permissions for writable directories
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# ===============================================
# Auto-run migrations for SQLite only
# ===============================================
# Run migrations ONLY if:
# 1. DB_CONNECTION is NOT set (use default SQLite)
# 2. Database file doesn't exist yet
# ===============================================

# Get DB_CONNECTION with proper priority:
# 1. System environment variable (Kubernetes ConfigMap/Secret, Docker -e flag)
# 2. .env file (for local Docker compatibility)
# 3. Empty (will default to 'sqlite' in logic below)
DB_CONNECTION="${DB_CONNECTION:-$(grep -E '^DB_CONNECTION=' /var/www/html/.env 2>/dev/null | cut -d '=' -f2)}"

if [ -z "$DB_CONNECTION" ] || [ "$DB_CONNECTION" = "sqlite" ]; then
    # Ensure database directory and file exist
    mkdir -p /var/www/html/database
    touch /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite

    echo "🔧 Using SQLite: database/database.sqlite"

    # Check if migrations table exists using sqlite3 command
    if sqlite3 /var/www/html/database/database.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='migrations';" 2>/dev/null | grep -q migrations; then
        echo "✅ Database already migrated, skipping..."
    else
        echo "🚀 Running database migrations..."
        php artisan migrate --force
    fi
else
    echo "ℹ️  DB_CONNECTION is set to '$DB_CONNECTION' - skipping auto-migration"
    echo "   Please run migrations manually: php artisan migrate --force"
fi

# ===============================================
# Start PHP-FPM and Nginx
# ===============================================
echo "🚀 Starting Filament PM..."
echo "   - PHP-FPM on port 9000"
echo "   - Nginx on port 80"

# Execute the main command
exec "$@"
