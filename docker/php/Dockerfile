FROM php:8.2

RUN apt update
RUN apt install git zip unzip libxml2-dev -y

RUN docker-php-ext-install pdo_mysql mysqli opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html
