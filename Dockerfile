# ----------------------------------------------------------------------
# FASE 1: Asset Builder (Compilazione Asset Front-End)
# ----------------------------------------------------------------------
FROM node:20 AS asset_builder

# Installa dipendenze necessarie per la fase di build (Git, Wget, Unzip)
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    git ca-certificates coreutils unzip wget && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

# ----------------------------------------------------------------------
# VARIABILI GLOBALI DI CONFIGURAZIONE
# ----------------------------------------------------------------------

# Variabile per il tag di Bootstrap Italia
ARG BOOTSTRAP_TAG="v2.16.2"

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

# ----------------------------------------------------------------------
# FASE 2: Finale (Immagine Apache e Servizio PHP)
# ----------------------------------------------------------------------
FROM php:8.3-apache

# Installazione delle dipendenze di sistema e PHP (inclusi unzip e wget)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev libonig-dev libzip-dev openssl curl ca-certificates coreutils unzip wget && \
    docker-php-ext-install intl mbstring pdo_mysql zip \
    && a2enmod ssl rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Installa Composer separatamente
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Imposta la directory di lavoro
WORKDIR /var/www/html

# ----------------------------------------------------------------------
# COPIA ASSET FRONT-END
# ----------------------------------------------------------------------

# 1. Copia Bootstrap Italia (Asset compilati dalla Fase 1)
RUN mkdir -p public/assets/bootstrap-italia
COPY --from=asset_builder /app/dist/ /var/www/html/public/assets/bootstrap-italia

# 2. Copia Font Awesome (Asset scaricati dalla Fase 1)
ENV FA_DEST="/var/www/html/public/assets/fontawesome"
RUN mkdir -p ${FA_DEST}
# Copia le sottocartelle essenziali dalla cartella rinominata
COPY --from=asset_builder /app/fontawesome-dist/css ${FA_DEST}/css/
COPY --from=asset_builder /app/fontawesome-dist/js ${FA_DEST}/js/
COPY --from=asset_builder /app/fontawesome-dist/webfonts ${FA_DEST}/webfonts/

# Imposta i permessi per gli asset copiati
RUN chmod -R 755 public/assets

# Copia Composer (solo dipendenze PHP)
COPY composer.json composer.lock* /var/www/html/

# Copia i client govpay usati come repository path in composer.json
COPY govpay-clients/ /app/govpay-clients/

# Installa le dipendenze PHP con Composer
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction || composer install --no-interaction

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
RUN chmod +x /usr/local/bin/docker-setup.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-setup.sh

# Copia e Abilita la configurazione Apache personalizzata
RUN rm /etc/apache2/sites-enabled/000-default.conf
COPY apache/000-default-ssl.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default.conf

# Copia la cartella 'img' dall'Host alla destinazione finale nel Container.
COPY img /var/www/html/public/img

# Copia il codice sorgente del progetto
COPY src/ /var/www/html/

# Imposta i permessi finali
RUN chown -R www-data:www-data /var/www/

EXPOSE 443

CMD ["apache2-foreground"]
