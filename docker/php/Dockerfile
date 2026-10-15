# PHP-FPM 8.4 para Laravel 13
FROM php:8.4-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev libonig-dev \
        libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring intl zip gd bcmath exif pcntl opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

# Mismo UID/GID que tu usuario: lo que crea artisan queda a tu nombre, no de root
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} -g ${GID} www-data

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-adoptauy.ini
COPY docker/php/entrypoint.sh /usr/local/bin/adoptauy-entrypoint
RUN chmod +x /usr/local/bin/adoptauy-entrypoint

WORKDIR /var/www/laravel
USER www-data

# La primera vez prepara Laravel (ver entrypoint.sh); después arranca PHP-FPM
ENTRYPOINT ["adoptauy-entrypoint"]
CMD ["php-fpm"]
