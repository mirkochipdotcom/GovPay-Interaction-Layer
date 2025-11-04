######################################################################
# STAGE 1: Asset Builder (Bootstrap Italia + Font Awesome)
######################################################################
FROM node:20-trixie-slim AS asset_builder

# Installa dipendenze necessarie per la fase di build (Git, Wget, Unzip)
RUN apt-get update && \
    apt-get install -y --no-install-recommends git ca-certificates unzip wget && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

# ----------------------------------------------------------------------
# VARIABILI GLOBALI DI CONFIGURAZIONE
# ----------------------------------------------------------------------

# Variabile per il tag di Bootstrap Italia
ARG BOOTSTRAP_TAG="v2.17.0"

# Variabili per Font Awesome
ARG FA_VERSION="7.1.0"
ENV FA_URL=https://github.com/FortAwesome/Font-Awesome/releases/download/${FA_VERSION}/fontawesome-free-${FA_VERSION}-web.zip
ENV FA_DIR="fontawesome-free-${FA_VERSION}-web"

# 1. Clona il repository, checkout, installa e compila Bootstrap Italia
RUN git clone https://github.com/italia/bootstrap-italia.git . && \
    git checkout ${BOOTSTRAP_TAG} && \
    echo "Scarico e compilo Bootstap-italia versione ${BOOTSTRAP_TAG}..." && \
    npm install && \
    npm run build

# 2. Scarica Font Awesome in questa fase per usarlo come asset copiabile nella Fase 2
RUN mkdir -p /tmp/fa_download && \
    cd /tmp/fa_download && \
    echo "Scarico Font Awesome ${FA_VERSION}..." && \
    wget -q -O fa.zip ${FA_URL} && \
    unzip -q fa.zip && \
    mv ${FA_DIR} /app/fontawesome-dist && \
    rm -rf /tmp/fa_download

# Il risultato della compilazione di Bootstrap Italia è in /app/dist
# Il risultato del download di Font Awesome è in /app/fontawesome-dist

######################################################################
# STAGE 2: Composer vendor builder (solo dipendenze PHP)
######################################################################
FROM composer:2 AS vendor_builder
WORKDIR /app
COPY composer.json composer.lock* ./
COPY govpay-clients/ ./govpay-clients/
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction || \
    composer update --no-dev --prefer-dist --optimize-autoloader --no-interaction

######################################################################
# STAGE 3: Runtime (Apache + PHP) harden
######################################################################
FROM php:8.4-apache-trixie

# Installazione delle dipendenze di sistema e PHP (inclusi unzip e wget)
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev libzip-dev libonig-dev \
    ca-certificates curl unzip openssl \
    && docker-php-ext-install intl mbstring pdo_mysql zip \
    && a2enmod ssl rewrite headers \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && apt-get purge -y --auto-remove libicu-dev libzip-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copia vendor dal builder
COPY --from=vendor_builder /app/vendor /var/www/html/vendor
# Reintroduco composer solo per script di setup (dump-autoload scenario)
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Imposta la directory di lavoro
WORKDIR /var/www/html

# ----------------------------------------------------------------------
# COPIA ASSET FRONT-END
# ----------------------------------------------------------------------

RUN mkdir -p public/assets/bootstrap-italia public/assets/fontawesome
COPY --from=asset_builder /app/dist/ /var/www/html/public/assets/bootstrap-italia/

# 2. Copia Font Awesome (Asset scaricati dalla Fase 1)
ENV FA_DEST="/var/www/html/public/assets/fontawesome"
COPY --from=asset_builder /app/fontawesome-dist/css ${FA_DEST}/css/
COPY --from=asset_builder /app/fontawesome-dist/js ${FA_DEST}/js/
COPY --from=asset_builder /app/fontawesome-dist/webfonts ${FA_DEST}/webfonts/

# Imposta i permessi per gli asset copiati
RUN chmod -R 755 public/assets

# (Documentativo) composer gestito nello stage vendor_builder
COPY composer.json composer.lock* /var/www/html/

# ----------------------------------------------------------------------
# Configurazione Finale
# ----------------------------------------------------------------------

# Copia i certificati SSL
RUN mkdir -p /ssl
COPY ssl/ /ssl/ 

#Copia i certificati di govpay se esistono
RUN mkdir -p /certificate
COPY certificate/ /var/www/certificate/

# Copia lo script di setup nel container e rendilo eseguibile
COPY docker-setup.sh /usr/local/bin/docker-setup.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-setup.sh && chmod 755 /usr/local/bin/docker-setup.sh

# Copia e Abilita la configurazione Apache personalizzata
RUN rm /etc/apache2/sites-enabled/000-default.conf
COPY apache/000-default-ssl.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default.conf

COPY img /var/www/html/public/img
COPY assets /var/www/html/public/assets
COPY public.htaccess /var/www/html/public/.htaccess
COPY debug /var/www/html/public/debug

COPY src/ /var/www/html/src/
RUN ln -s /var/www/html/src/bootstrap /var/www/html/bootstrap \
    && ln -s /var/www/html/src/routes /var/www/html/routes
COPY templates/ /var/www/html/templates/
COPY bin/ /var/www/html/bin/
RUN cp -r /var/www/html/src/public/* /var/www/html/public/ || true

# Copia la sorgente dei client generati (necessario se Composer ha creato symlink per path repositories)
COPY govpay-clients/ /var/www/html/govpay-clients/

# Hardening Apache: rimuove Indexes e aggiunge security headers
RUN sed -i 's/Options Indexes FollowSymLinks/Options FollowSymLinks/g' /etc/apache2/apache2.conf && \
    printf '\n<IfModule mod_headers.c>\n  Header always set X-Content-Type-Options "nosniff"\n  Header always set X-Frame-Options "SAMEORIGIN"\n  Header always set Referrer-Policy "strict-origin-when-cross-origin"\n  Header always set X-XSS-Protection "1; mode=block"\n</IfModule>\n' > /etc/apache2/conf-enabled/security-headers.conf

# (RIMOSSO cambio utente: Apache necessita privilegi iniziali per bind 443, rimane root che poi esegue worker come www-data)
RUN useradd -r -d /var/www -g www-data www-app && chown -R www-app:www-data /var/www/html

# Permessi finali già assegnati a www-app

EXPOSE 443

CMD ["apache2-foreground"]
