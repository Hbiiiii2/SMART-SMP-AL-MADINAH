#!/bin/sh
set -e

echo "[SMART+] Initializing container environment..."

# 1. Sync compiled Vite assets to public volume if mounted
if [ -d "/var/www/html/public_template/build" ]; then
    echo "[SMART+] Syncing Vite build assets to public volume..."
    rm -rf /var/www/html/public/build
    mkdir -p /var/www/html/public/build
    cp -rf /var/www/html/public_template/build/. /var/www/html/public/build/ 2>/dev/null || true
fi

# 2. Clear stale cache files to prevent class/config conflicts
echo "[SMART+] Clearing stale bootstrap cache files..."
rm -f /var/www/html/bootstrap/cache/*.php

# 3. Check database connection and wait until ready
echo "[SMART+] Waiting for database to be ready..."
php -r '
$host = getenv("DB_HOST") ?: "smart-db";
$port = getenv("DB_PORT") ?: 3306;
$user = getenv("DB_USERNAME") ?: "smart_user";
$pass = getenv("DB_PASSWORD") ?: "smart_secret_123";
$db   = getenv("DB_DATABASE") ?: "smart";

$candidates = [
    ["user" => $user, "pass" => $pass, "label" => "configured credentials ({$user})"],
    ["user" => "smart_user", "pass" => "smart_secret_123", "label" => "smart_user credentials"],
    ["user" => "root", "pass" => getenv("DB_ROOT_PASSWORD") ?: "root_secret_123", "label" => "root credentials"]
];

$maxTries = 30;
$connected = false;

for ($i = 1; $i <= $maxTries; $i++) {
    foreach ($candidates as $cand) {
        $u = $cand["user"];
        $p = $cand["pass"];
        try {
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db}", $u, $p, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3
            ]);
            echo "[SMART+] MySQL \"{$db}\" ready on {$host}:{$port} using {$cand["label"]}.\n";
            file_put_contents("/tmp/db_env", "export DB_USERNAME=\"{$u}\"\nexport DB_PASSWORD=\"{$p}\"\n");
            $connected = true;
            break 2;
        } catch (Exception $e) {
            // Check next candidate
        }
    }
    echo "[SMART+] Waiting for MySQL ({$host}:{$port})... Attempt {$i}/{$maxTries}\n";
    sleep(2);
}

if (!$connected) {
    echo "[SMART+] Warning: Database connection timed out. Proceeding anyway...\n";
}
'

if [ -f "/tmp/db_env" ]; then
    . /tmp/db_env
fi

# 4. Run Laravel migrations and optimization on php-fpm launch
if [ "$1" = "php-fpm" ]; then
    # Generate APP_KEY if empty
    if [ -z "$APP_KEY" ]; then
        echo "[SMART+] Checking APP_KEY..."
        php artisan key:generate --force || true
    fi

    echo "[SMART+] Running database migrations..."
    php artisan migrate --force || echo "[SMART+] Migrations finished with notices."

    echo "[SMART+] Ensuring storage symlink..."
    php artisan storage:link || true

    if [ "$APP_ENV" = "production" ]; then
        echo "[SMART+] Caching configuration, routes, and views for production..."
        php artisan optimize:clear || true
        php artisan config:cache || true
        php artisan route:cache || true
        php artisan view:cache || true
        php artisan event:cache || true
    fi
fi

# 5. Ensure proper permissions for web server
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "[SMART+] Container ready. Executing: $@"
if [ "$1" = "php-fpm" ]; then
    shift
    exec php-fpm -F "$@"
fi
exec "$@"
