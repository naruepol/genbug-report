#!/bin/sh
# Prepares the Laravel application on container start, then runs the given command (php-fpm).
set -e

cd /var/www/html

if [ ! -f .env ]; then
    echo "[entrypoint] .env not found, copying .env.example"
    cp .env.example .env
fi

# Install PHP dependencies when vendor/ is missing or composer.lock changed since the last install.
if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/composer/installed.json ]; then
    echo "[entrypoint] Installing Composer dependencies"
    composer install --no-interaction --prefer-dist --no-progress
fi

if ! grep -Eq '^APP_KEY=.+' .env; then
    echo "[entrypoint] Generating APP_KEY"
    php artisan key:generate --force
fi

if [ ! -e public/storage ]; then
    echo "[entrypoint] Linking public storage"
    php artisan storage:link || echo "[entrypoint] storage:link failed; nginx still serves /storage via alias"
fi

# Production deployments with several instances should run migrations as a separate, controlled step.
if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    echo "[entrypoint] Running migrations"
    php artisan migrate --force
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

exec "$@"
