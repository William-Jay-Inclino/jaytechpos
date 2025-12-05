#!/bin/sh
set -e

# Ensure common writable directories exist and are owned by the webserver user
mkdir -p /var/www/storage /var/www/bootstrap/cache /var/www/storage/framework/views
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/vendor || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache || true

# Start cron in the background
service cron start

# If no command is provided, default to running php-fpm
if [ "$#" -eq 0 ]; then
  exec php-fpm
fi

exec "$@"
