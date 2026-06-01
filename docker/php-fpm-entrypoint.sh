#!/bin/sh
set -e

# Carpetas que PHP-FPM debe poder escribir (admin, uploads, logs, SQLite)
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/cache
mkdir -p /var/www/html/public/assets/img/uploads

# www-data en php:8.3-fpm-alpine suele ser UID 82
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data /var/www/html/storage /var/www/html/public/assets/img/uploads 2>/dev/null || \
        chown -R 82:82 /var/www/html/storage /var/www/html/public/assets/img/uploads 2>/dev/null || true
    chmod -R 775 /var/www/html/storage /var/www/html/public/assets/img/uploads 2>/dev/null || true

    if [ ! -f /var/www/html/storage/site_data.json ]; then
        echo '{}' > /var/www/html/storage/site_data.json
    fi
    chmod 664 /var/www/html/storage/site_data.json 2>/dev/null || true
    chown www-data:www-data /var/www/html/storage/site_data.json 2>/dev/null || \
        chown 82:82 /var/www/html/storage/site_data.json 2>/dev/null || true

    # SQLite: el archivo Y la carpeta storage deben ser escribibles (journal -wal/-shm)
    if [ ! -f /var/www/html/storage/database.sqlite ]; then
        touch /var/www/html/storage/database.sqlite
    fi
    chmod 664 /var/www/html/storage/database.sqlite 2>/dev/null || true
    chown www-data:www-data /var/www/html/storage/database.sqlite 2>/dev/null || \
        chown 82:82 /var/www/html/storage/database.sqlite 2>/dev/null || true
    for f in /var/www/html/storage/database.sqlite-*; do
        [ -e "$f" ] || continue
        chmod 664 "$f" 2>/dev/null || true
        chown www-data:www-data "$f" 2>/dev/null || chown 82:82 "$f" 2>/dev/null || true
    done
fi

exec docker-php-entrypoint php-fpm
