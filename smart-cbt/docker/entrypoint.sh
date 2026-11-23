#!/bin/sh
set -e

echo "[Smart-CBT] Starting container initialization (role=${CONTAINER_ROLE:-primary}, cmd=$1)..."

IS_PRIMARY=0
if [ "$1" = "php-fpm" ] && [ "${CONTAINER_ROLE:-primary}" = "primary" ]; then
    IS_PRIMARY=1
fi

# 1. Sync public template assets & clear stale cache ONLY on primary app container
if [ "$IS_PRIMARY" = "1" ]; then
    if [ -d "/var/www/html/public_template/build" ]; then
        echo "[Smart-CBT] Syncing Vite build assets to public volume..."
        rm -rf /var/www/html/public/build
        mkdir -p /var/www/html/public/build
        cp -rf /var/www/html/public_template/build/. /var/www/html/public/build/ 2>/dev/null || true
    fi

    echo "[Smart-CBT] Clearing stale cache files..."
    rm -f /var/www/html/bootstrap/cache/*.php
fi

# 3. Check database connection and auto-create database if not exists
echo "[Smart-CBT] Checking database connection..."
php -r '
$driver = getenv("DB_CONNECTION") ?: "pgsql";
$host   = getenv("DB_HOST") ?: "smart-cbt-db";
$port   = getenv("DB_PORT") ?: ($driver === "pgsql" ? 5432 : 3306);
$user   = getenv("DB_USERNAME") ?: "smart_cbt";
$pass   = getenv("DB_PASSWORD") ?: "smart_cbt_password";
$db     = getenv("DB_DATABASE") ?: "smart_cbt";

$maxTries = 20;
$connected = false;

for ($i = 1; $i <= $maxTries; $i++) {
    if ($driver === "pgsql") {
        try {
            $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$db}", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3
            ]);
            $testDb = $db . "_testing";
            $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '{$testDb}'");
            if (! $stmt->fetchColumn()) {
                $pdo->exec("CREATE DATABASE \"{$testDb}\" OWNER \"{$user}\"");
            }
            echo "[Smart-CBT] PostgreSQL \"{$db}\" and \"{$testDb}\" ready on {$host}:{$port}.\n";
            $connected = true;
            break;
        } catch (Exception $e) {
            // Retry
        }
    } else {
        try {
            $rootUser = getenv("DB_ROOT_USERNAME") ?: "root";
            $rootPass = getenv("DB_ROOT_PASSWORD") ?: "smart_cbt_root_password";
            $pdo = new PDO("mysql:host={$host};port={$port}", $rootUser, $rootPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}_testing` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("GRANT ALL ON `{$db}`.* TO '\''{$user}'\''@'\''%'\'';");
            $pdo->exec("GRANT ALL ON `{$db}_testing`.* TO '\''{$user}'\''@'\''%'\'';");
            $pdo->exec("FLUSH PRIVILEGES;");
            echo "[Smart-CBT] MySQL \"{$db}\" and \"{$db}_testing\" ready on {$host}:{$port}.\n";
            $connected = true;
            break;
        } catch (Exception $e) {
            // Retry
        }
    }
    echo "[Smart-CBT] Waiting for {$driver} ({$host}:{$port})... Attempt {$i}/{$maxTries}\n";
    sleep(2);
}

if (!$connected) {
    echo "[Smart-CBT] Warning: Database connection timed out. Proceeding anyway...\n";
}
'

# 4. Run Laravel migrations & caching only on primary app container
if [ "$IS_PRIMARY" = "1" ]; then
    if [ -z "$APP_KEY" ]; then
        echo "[Smart-CBT] Checking APP_KEY..."
        php artisan key:generate --force || true
    fi

    echo "[Smart-CBT] Running database migrations..."
    php artisan migrate --force || echo "[Smart-CBT] Migration step completed with notices."

    echo "[Smart-CBT] Ensuring storage symlink..."
    php artisan storage:link || true

    if [ "$APP_ENV" = "production" ]; then
        echo "[Smart-CBT] Caching configuration, routes, and views for production..."
        php artisan optimize:clear || true
        php artisan config:cache || true
        php artisan route:cache || true
        php artisan view:cache || true
        php artisan event:cache || true
    fi

    # Fix permissions for storage and bootstrap/cache on primary
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
else
    if [ "$APP_ENV" = "production" ]; then
        echo "[Smart-CBT] Waiting for primary container cache initialization..."
        wait_tries=0
        while [ ! -f /var/www/html/bootstrap/cache/config.php ] && [ "$wait_tries" -lt 20 ]; do
            wait_tries=$((wait_tries + 1))
            sleep 1
        done
    fi
fi

echo "[Smart-CBT] Container ready. Executing: $@"
if [ "$1" = "php-fpm" ]; then
    shift
    exec php-fpm -F "$@"
fi
exec "$@"
