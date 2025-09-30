FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libzip-dev zip openssl \
    && docker-php-ext-install intl mbstring pdo_mysql zip \
    && a2enmod ssl rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copiamo la configurazione SSL già pronta
COPY apache/000-default-ssl.conf /etc/apache2/sites-enabled/000-default-ssl.conf

WORKDIR /var/www/html
