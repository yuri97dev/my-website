FROM php:8.5.0-fpm-bookworm

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        bcmath \
        zip \
        pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=node:24.16.0-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24.16.0-bookworm-slim /usr/local/bin/npm /usr/local/bin/npm
COPY --from=node:24.16.0-bookworm-slim /usr/local/bin/npx /usr/local/bin/npx
COPY --from=node:24.16.0-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules

COPY docker/php/app.ini /usr/local/etc/php/conf.d/app.ini

RUN usermod -u "${USER_ID}" www-data \
    && groupmod -g "${GROUP_ID}" www-data

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
