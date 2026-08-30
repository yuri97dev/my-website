FROM php:8.5.0-fpm-bookworm

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN apt-get update && apt-get install -y \
        git \
        unzip \
        supervisor \
        libpq-dev \
        libzip-dev \
        libtidy-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libfreetype6-dev \
    && apt-get install -y --no-install-recommends \
        chromium \
        chromium-driver \
        fonts-liberation \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install \
        pdo_pgsql \
        bcmath \
        zip \
        pcntl \
        tidy \
        gd \
        exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=node:24.16.0-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24.16.0-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY docker/php/app.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/fpm-env.conf /usr/local/etc/php-fpm.d/fpm-env.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

RUN usermod -u "${USER_ID}" www-data \
    && groupmod -g "${GROUP_ID}" www-data

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
