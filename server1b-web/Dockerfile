FROM php:8.2-fpm-alpine

# Install PostgreSQL client headers and build pdo_pgsql extension
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Patch OS packages & install dependencies
RUN apk update && apk upgrade --no-cache c-ares && \
    docker-php-ext-install pdo pdo_pgsql

# Healthcheck definition (DS-0026)
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD nc -z 127.0.0.1 9000 || exit 1

# Non-root user directive (DS-0002)
USER www-data
