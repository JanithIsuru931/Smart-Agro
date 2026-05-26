#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Ensure .env exists
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "[entrypoint] Created .env from .env.example"
    fi
fi

# Generate APP_KEY if missing
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --force --no-interaction
    echo "[entrypoint] Generated APP_KEY"
fi

# SQLite database file
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p database
    touch database/database.sqlite
    chmod 664 database/database.sqlite
fi

# Storage directories
mkdir -p storage/app/public storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache database 2>/dev/null || true

# Run migrations (with --force for non-interactive)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction || echo "[entrypoint] Migration step skipped/failed"
fi

# Optionally seed on first boot (set SEED_DATABASE=true)
if [ "${SEED_DATABASE:-false}" = "true" ]; then
    php artisan db:seed --force --no-interaction || echo "[entrypoint] Seeding skipped/failed"
fi

# Storage symlink
if [ -e public/storage ] || [ -L public/storage ]; then
    rm -rf public/storage
fi
php artisan storage:link --no-interaction || true

# Cache config/routes/views in production
if [ "${APP_ENV:-local}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
