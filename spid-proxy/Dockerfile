FROM php:8.4-apache-trixie

ARG DEBIAN_FRONTEND=noninteractive
ARG SPID_CIE_PHP_REF="v.3.19.1"

RUN apt-get update && apt-get install -y --no-install-recommends \
    git ca-certificates unzip zip openssl \
    curl \
    libgmp-dev \
    libonig-dev \
    && docker-php-ext-install gmp mbstring \
    && a2enmod ssl rewrite headers \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && { \
        echo "display_errors=Off"; \
        echo "log_errors=On"; \
        echo "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED"; \
      } > /usr/local/etc/php/conf.d/zz-spid-proxy.ini \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Vendorizza il codice upstream in /opt (read-only-ish). La copia runtime vive su volume.
RUN git clone --depth 1 --branch "${SPID_CIE_PHP_REF}" https://github.com/italia/spid-cie-php.git /opt/spid-cie-php \
    && rm -rf /opt/spid-cie-php/.git

# Apache vhost dedicato
COPY apache-ssl.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default.conf

# Entrypoint: copia su volume, genera cert self-signed se serve, avvia Apache
COPY entrypoint.sh /usr/local/bin/spid-proxy-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/spid-proxy-entrypoint.sh && chmod 755 /usr/local/bin/spid-proxy-entrypoint.sh

WORKDIR /var/www/spid-cie-php

EXPOSE 443
ENTRYPOINT ["/usr/local/bin/spid-proxy-entrypoint.sh"]
CMD ["apache2-foreground"]
