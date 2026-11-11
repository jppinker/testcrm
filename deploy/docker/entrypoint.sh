#!/bin/sh
set -eu
mkdir -p /var/www/data /var/www/html/storage/framework/cache/data /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/logs
[ -f /var/www/data/database.sqlite ] || touch /var/www/data/database.sqlite
chown -R www-data:www-data /var/www/data /var/www/html/storage /var/www/html/bootstrap/cache
exec "$@"
