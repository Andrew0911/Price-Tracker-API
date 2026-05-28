FROM php:8.4-fpm

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    nginx supervisor git curl zip unzip \
    libpng-dev libicu-dev libonig-dev libxml2-dev libzip-dev \
    libmagickwand-dev libyaml-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring intl zip exif pcntl bcmath gd \
    && pecl install redis imagick yaml \
    && docker-php-ext-enable redis imagick yaml opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY ./laravel /var/www/html

COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./docker/linux/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/linux/php-custom.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY ./docker/linux/opcache.ini /usr/local/etc/php/conf.d/98-opcache.ini
COPY ./docker/nginx.sample.conf /etc/nginx/nginx.conf

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

RUN mkdir -p /var/run/php && chown nobody:nogroup /var/run/php

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]