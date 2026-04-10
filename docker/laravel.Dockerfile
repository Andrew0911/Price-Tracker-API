FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive
ARG USER=www-data
ENV PHP_VERSION=8.4

# 1. ACTUALIZARE & REPOSITORY PHP
RUN apt update
# Adăugăm repository-ul PPA (Ondrej) pentru PHP 8.4
RUN apt install -y lsb-release ca-certificates apt-transport-https software-properties-common gnupg2 curl
RUN add-apt-repository ppa:ondrej/php

# 2. INSTALARE PACHETE ESENȚIALE ȘI DEPENDENȚE
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl zip unzip mc nginx supervisor git nano wget \
    zlib1g-dev libpng-dev libicu-dev libonig-dev libxml2-dev libzip-dev \
    libyaml-dev && \
    apt-get clean && rm -rf /var/lib/apt/lists*

# 3. INSTALARE PHP ȘI EXTENSII ESENȚIALE
RUN apt-get update && apt-get -y install \
    php${PHP_VERSION}-cli php${PHP_VERSION}-fpm php${PHP_VERSION}-xml php${PHP_VERSION}-dev \
    php${PHP_VERSION}-intl php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-opcache php${PHP_VERSION}-zip php${PHP_VERSION}-exif php${PHP_VERSION}-dom php${PHP_VERSION}-yaml \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-imagick

# 4. INSTALARE COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurări finale
RUN chown -R nobody:nogroup /run
WORKDIR /var/www/html
RUN service php${PHP_VERSION}-fpm start

# Comanda de start
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]