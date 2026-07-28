FROM php:8.2-fpm-alpine

# Install PostgreSQL client headers and build pdo_pgsql extension
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql
