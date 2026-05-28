#!/bin/sh
set -e

# Carpetas que PHP-FPM debe poder escribir (admin, uploads, logs)
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/public/assets/img/uploads

# www-data en php:8.3-fpm-alpine suele ser UID 82
if [ -w /var/www/html/storage ] || [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data /var/www/html/storage /var/www/html/public/assets/img/uploads 2>/dev/null || \
        chown -R 82:82 /var/www/html/storage /var/www/html/public/assets/img/uploads 2>/dev/null || true
    chmod -R 775 /var/www/html/storage /var/www/html/public/assets/img/uploads 2>/dev/null || true
    if [ ! -f /var/www/html/storage/site_data.json ]; then
        echo '{}' > /var/www/html/storage/site_data.json 2>/dev/null || true
    fi
    if [ -f /var/www/html/storage/site_data.json ]; then
        chown www-data:www-data /var/www/html/storage/site_data.json 2>/dev/null || \
            chown 82:82 /var/www/html/storage/site_data.json 2>/dev/null || true
        chmod 664 /var/www/html/storage/site_data.json 2>/dev/null || true
    fi
fi

exec docker-php-entrypoint php-fpm
