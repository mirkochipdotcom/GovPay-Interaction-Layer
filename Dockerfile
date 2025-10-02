# ----------------------------------------------------------------------
# FASE 1: Asset Builder (Compilazione Asset Front-End)
# ----------------------------------------------------------------------
FROM node:20 as asset_builder

# Installa Git per la clonazione (git) e coreutils in un unico passaggio.
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    git ca-certificates coreutils && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Variabile per il tag di Bootstrap Italia
ENV BOOTSTRAP_TAG="v2.16.2"

# 1. Clona il repository, checkout, installa e compila
RUN git clone https://github.com/italia/bootstrap-italia.git . && \
    git checkout ${BOOTSTRAP_TAG} && \
    npm install && \
    npm run build

# Il risultato della compilazione si trova in /app/dist

# ----------------------------------------------------------------------
# FASE 2: Finale (Immagine Apache e Servizio PHP)
# ----------------------------------------------------------------------
FROM php:8.3-apache

# Installazione delle dipendenze di sistema e PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev libonig-dev libzip-dev openssl curl ca-certificates coreutils && \
    docker-php-ext-install intl mbstring pdo_mysql zip \
    && a2enmod ssl rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Installa Composer separatamente (con il metodo corretto)
# Questo è il modo più pulito e affidabile:
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Imposta la directory di lavoro
WORKDIR /var/www/html

# Crea la cartella di destinazione
RUN mkdir -p public/assets/bootstrap-italia

# Copia CRITICA: Copia i file COMPILATI da /app/dist nella destinazione finale.
COPY --from=asset_builder /app/dist/ /var/www/html/public/assets/bootstrap-italia

# Imposta i permessi
RUN chmod -R 755 public/assets/bootstrap-italia

# Copia Composer (solo dipendenze PHP)
COPY composer.json composer.lock* /var/www/html/

# ----------------------------------------------------------------------
# Configurazione Finale
# ----------------------------------------------------------------------

# Copia i certificati SSL
RUN mkdir -p /ssl
COPY ssl/ /ssl/ 

# Copia e Abilita la configurazione Apache personalizzata
RUN rm /etc/apache2/sites-enabled/000-default.conf
COPY apache/000-default-ssl.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default.conf

# Copia il codice sorgente del progetto
COPY src/ /var/www/html/

# Imposta i permessi finali
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80 443

CMD ["apache2-foreground"]
