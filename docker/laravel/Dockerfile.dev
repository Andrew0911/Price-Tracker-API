FROM php:8.4-fpm

ENV DEBIAN_FRONTEND=noninteractive

# 1. INSTALARE DEPENDENȚE SISTEM
RUN apt-get update && apt-get install -y \
    nginx supervisor git nano wget curl zip unzip mc \
    libpng-dev libicu-dev libonig-dev libxml2-dev libzip-dev libyaml-dev \
    libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. EXTENSII PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    intl \
    zip \
    exif \
    pcntl \
    bcmath \
    gd

# Redis
RUN pecl install redis && docker-php-ext-enable redis

# Imagick
RUN pecl install imagick && docker-php-ext-enable imagick

# YAML
RUN pecl install yaml && docker-php-ext-enable yaml

# OPcache
RUN docker-php-ext-enable opcache

# 3. COMPOSER
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. WORKDIR
WORKDIR /var/www/html

# 5. PERMISIUNI
RUN chown -R www-data:www-data /var/www/html

# 6. SOCKET DIR pentru PHP-FPM
RUN mkdir -p /var/run/php && chown nobody:nogroup /var/run/php

# 6. NGINX CONFIG (folosești deja volumele tale)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]