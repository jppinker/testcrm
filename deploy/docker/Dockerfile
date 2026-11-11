FROM node:22-alpine AS frontend
WORKDIR /src/frontend
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build:laravel

FROM php:8.5-fpm-bookworm AS app
RUN apt-get update && apt-get install -y --no-install-recommends libsqlite3-dev libzip-dev libonig-dev unzip && docker-php-ext-install pdo_sqlite mbstring zip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY backend/ ./
COPY --from=frontend /src/backend/public/app ./public/app
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache
COPY deploy/docker/entrypoint.sh /usr/local/bin/testcrm-entrypoint
RUN chmod +x /usr/local/bin/testcrm-entrypoint
ENTRYPOINT ["testcrm-entrypoint"]
CMD ["php-fpm"]

FROM nginx:alpine AS web
COPY deploy/docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
