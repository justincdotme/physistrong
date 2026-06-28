FROM composer:2 AS composer

FROM node:22-alpine AS node

FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    freetype-dev \
    icu-dev \
    libzip-dev \
    pcre-dev \
    oniguruma-dev

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    --with-xpm

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    bcmath \
    opcache \
    zip \
    intl \
    gd \
    exif \
    pcntl

RUN apk add --no-cache sqlite-dev && docker-php-ext-install pdo_sqlite

RUN apk add --no-cache git

RUN apk add --no-cache libstdc++
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
 && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

RUN git config --system core.hooksPath /var/www/html/.githooks

RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini

RUN printf 'upload_max_filesize = 10M\npost_max_size = 12M\n' > /usr/local/etc/php/conf.d/uploads.ini

RUN addgroup -g 1000 laravel && \
    adduser -D -u 1000 -G laravel laravel

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --chown=laravel:laravel . .

RUN if [ "$BUILD_ENV" = "production" ]; then \
    composer install --no-dev --optimize-autoloader; \
  fi

USER laravel

EXPOSE 9000

CMD ["php-fpm"]
