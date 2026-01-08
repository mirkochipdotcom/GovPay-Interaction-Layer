#!/bin/bash
set -euo pipefail

# Directory applicativa effettiva nel runtime
APP_SUITE="${APP_SUITE:-backoffice}"
APP_DIR="/var/www/html"
if [ "$APP_SUITE" = "frontoffice" ]; then
  APP_DIR="/var/www/html/frontoffice"
fi
cd "$APP_DIR"

echo "--- Esecuzione Setup Composer Autocorreggente ---"

INIT_MARKER="${APP_DIR}/.init_done"
RUN_COMPOSER=1
if [ "$APP_SUITE" = "frontoffice" ]; then
  RUN_COMPOSER=0
fi
if [ -f "$INIT_MARKER" ]; then
  echo "ℹ️  Init marker presente: salto fase Composer (nessun cambiamento rilevato)."
  RUN_COMPOSER=0
fi

# Rileva composer (path assoluto se disponibile)
if command -v composer >/dev/null 2>&1; then
  COMPOSER_BIN="$(command -v composer)"
else
  # fallback: potrebbe non essere stato copiato (immagine minimale) -> esci con warning non bloccante
  echo "⚠️  Composer non trovato nel PATH. Skip operazioni Composer." >&2
  COMPOSER_BIN=""
fi

# Rileva openssl per eventuale generazione certificati
if command -v openssl >/dev/null 2>&1; then
  OPENSSL_BIN="$(command -v openssl)"
else
  OPENSSL_BIN=""
  echo "⚠️  openssl non trovato: salterò la generazione certificati self-signed." >&2
fi

# === Controllo file TLS forniti via env (GOVPAY) o fallback su directory certificate ===
# Se l'utente ha impostato le variabili GOVPAY_TLS_CERT e GOVPAY_TLS_KEY nel file .env,
# verifichiamo che siano entrambe presenti e che i file esistano nel container.
if [ -n "${GOVPAY_TLS_CERT:-}" ] || [ -n "${GOVPAY_TLS_KEY:-}" ]; then
  # Entrambe devono essere valorizzate
  if [ -z "${GOVPAY_TLS_CERT:-}" ] || [ -z "${GOVPAY_TLS_KEY:-}" ]; then
    echo "❌ Errore: hanno valore solo una delle variabili GOVPAY_TLS_CERT / GOVPAY_TLS_KEY. Entrambe devono essere impostate insieme." >&2
    echo "Valori correnti: GOVPAY_TLS_CERT='${GOVPAY_TLS_CERT:-}' GOVPAY_TLS_KEY='${GOVPAY_TLS_KEY:-}'" >&2
    exit 1
  fi

  # Controlla che i file esistano
  if [ ! -f "${GOVPAY_TLS_CERT}" ] || [ ! -f "${GOVPAY_TLS_KEY}" ]; then
    echo "⚠️ Avviso: uno o entrambi i file TLS specificati non esistono nel container:" >&2
    [ ! -f "${GOVPAY_TLS_CERT}" ] && echo "  - Cert mancante: ${GOVPAY_TLS_CERT}" >&2 || true
    [ ! -f "${GOVPAY_TLS_KEY}" ] && echo "  - Key mancante: ${GOVPAY_TLS_KEY}" >&2 || true
    # Proviamo il fallback in /var/www/certificate
    FB_CERT="/var/www/certificate/certificate.cer"
    FB_KEY="/var/www/certificate/private_key.key"
    if [ -f "${FB_CERT}" ] && [ -f "${FB_KEY}" ]; then
      echo "✅ Trovati certificati fallback in ${FB_CERT} e ${FB_KEY}; li userò al posto di quelli configurati." >&2
      GOVPAY_TLS_CERT="${FB_CERT}"
      GOVPAY_TLS_KEY="${FB_KEY}"
    else
      echo "⚠️ Nessun certificato GovPay disponibile; proseguo comunque (alcune funzionalità GovPay potrebbero non funzionare)." >&2
      unset GOVPAY_TLS_CERT GOVPAY_TLS_KEY
    fi
  else
    echo "✅ Trovati file TLS per GovPay forniti via env: ${GOVPAY_TLS_CERT} , ${GOVPAY_TLS_KEY}"
  fi
else
  # Se non sono state fornite variabili env, verifichiamo la presenza di eventuali certificati
  # nella cartella /var/www/certificate/ (copia della cartella `certificate/` del progetto)
  CERT_FALLBACK="/var/www/certificate/certificate.cer"
  KEY_FALLBACK="/var/www/certificate/private_key.key"
  if [ -f "${CERT_FALLBACK}" ] && [ -f "${KEY_FALLBACK}" ]; then
  echo "✅ Trovati certificati GovPay in /var/www/certificate/. Uso: ${CERT_FALLBACK}, ${KEY_FALLBACK}"
  GOVPAY_TLS_CERT="${CERT_FALLBACK}"
  GOVPAY_TLS_KEY="${KEY_FALLBACK}"
  fi
fi

# === SSL: genera certificati self-signed se non esistono ===
# Se SKIP_SELF_SIGNED è impostato (user ha fornito certificati GOVPAY), non generare
if [ -z "${SKIP_SELF_SIGNED:-}" ] && ( [ ! -f /ssl/server.crt ] || [ ! -f /ssl/server.key ] ); then
  echo "⚙️  Certificati SSL mancanti: genero certificati self-signed in /ssl ..."
  mkdir -p /ssl
  # Genera una chiave privata e un certificato self-signed valido 365 giorni
  if [ -n "$OPENSSL_BIN" ]; then
    if ! $OPENSSL_BIN req -x509 -nodes -days 365 -newkey rsa:2048 \
      -keyout /ssl/server.key -out /ssl/server.crt \
      -subj "/CN=localhost" \
      -addext "basicConstraints=CA:FALSE" \
      -addext "subjectAltName=DNS:localhost" >/dev/null 2>&1; then
        echo "❌ Errore: generazione certificati fallita" >&2
    fi
  else
    echo "⚠️  openssl assente: salto generazione certificati" >&2
  fi
  chmod 600 /ssl/server.key || true
  chmod 644 /ssl/server.crt || true
  echo "✅ Certificati self-signed creati.";
fi

# === 1. SCENARIO AGGIORNAMENTO/RIGENERAZIONE LOCK ===
# Se il file lock NON esiste E la cartella vendor ESISTE,
# l'utente ha ELIMINATO il lock per forzare un aggiornamento.
if [ "$RUN_COMPOSER" -eq 1 ] && [ -n "$COMPOSER_BIN" ] && [ ! -f composer.lock ] && [ -d /var/www/html/vendor ]; then
  echo '🟡 ATTENZIONE: composer.lock mancante. Eseguo: composer update per rigenerarlo...'
  $COMPOSER_BIN update --no-dev --optimize-autoloader;

# === 2. SCENARIO PRIMA INSTALLAZIONE (Nuovo progetto o pulizia completa) ===
# Se il file lock NON esiste E la cartella vendor NON esiste (primo avvio/pulizia totale)
elif [ "$RUN_COMPOSER" -eq 1 ] && [ -n "$COMPOSER_BIN" ] && [ ! -f composer.lock ] && [ ! -d /var/www/html/vendor ]; then
  echo '🔴 ATTENZIONE: Nessun artefatto trovato. Eseguo: composer install...'
  $COMPOSER_BIN install --no-dev --optimize-autoloader;

# === 3. SCENARIO NORMALE (Dump-autoload veloce) ===
elif [ "$RUN_COMPOSER" -eq 1 ] && [ -n "$COMPOSER_BIN" ]; then
  echo '✅ Artefatti trovati. Eseguo dump-autoload o update condizionale...'

  # Puoi mantenere qui la tua logica originale di update/dump
  if [ /var/www/html/vendor/composer/installed.json -ot composer.json ]; then
      echo 'Eseguo: composer update (file modificati)...'
      $COMPOSER_BIN update --no-dev --optimize-autoloader;
  else
      echo 'Eseguo: composer dump-autoload...'
      $COMPOSER_BIN dump-autoload;
  fi
else
  echo "ℹ️  Nessuna operazione Composer eseguita (composer mancante o suite frontoffice)"
fi

if [ "$RUN_COMPOSER" -eq 1 ]; then
  touch "$INIT_MARKER" || true
fi

if [ "$APP_SUITE" = "frontoffice" ]; then
  TARGET_PUBLIC="/var/www/html/public"
  SOURCE_PUBLIC="/var/www/html/frontoffice/public"
  if [ -d "$SOURCE_PUBLIC" ]; then
    mkdir -p "$TARGET_PUBLIC"
    shopt -s dotglob
    for entry in "$TARGET_PUBLIC"/*; do
      name="$(basename "$entry")"
      case "$name" in
        .|..|assets|img|.htaccess)
          continue
          ;;
      esac
      rm -rf "$entry"
    done
    shopt -u dotglob
    cp -R "$SOURCE_PUBLIC"/. "$TARGET_PUBLIC"/
    rm -rf "$TARGET_PUBLIC"/debug
    chown -R www-app:www-data "$TARGET_PUBLIC" || true
    echo "✅ Frontoffice pubblicato in $TARGET_PUBLIC (debug disattivata)"

    # Log applicativo frontoffice (usato da App\Logger)
    # Deve essere scrivibile dall'utente del web server (di solito www-data).
    FRONT_STORAGE_DIR="/var/www/html/frontoffice/storage"
    FRONT_LOG_DIR="${FRONT_STORAGE_DIR}/logs"
    mkdir -p "$FRONT_LOG_DIR" 2>/dev/null || true
    chown -R www-data:www-data "$FRONT_STORAGE_DIR" 2>/dev/null || true
    chown -R www-app:www-data "$FRONT_STORAGE_DIR" 2>/dev/null || true
    chmod 775 "$FRONT_STORAGE_DIR" 2>/dev/null || true
    chmod 777 "$FRONT_LOG_DIR" 2>/dev/null || true
  else
    echo "⚠️  Sorgente frontoffice $SOURCE_PUBLIC non trovata" >&2
  fi

  # === SPID/CIE (spid-cie-php) opzionale ===
  # Abilitazione: FRONT_OFFICE_AUTH_PROVIDER=spid_cie
  AUTH_PROVIDER="${FRONTOFFICE_AUTH_PROVIDER:-${FRONT_OFFICE_AUTH_PROVIDER:-}}"
  AUTH_PROVIDER_LOWER="$(echo "${AUTH_PROVIDER}" | tr '[:upper:]' '[:lower:]' | tr -d '[:space:]')"
  if [ "$AUTH_PROVIDER_LOWER" = "spid_cie" ]; then
    SPID_ROOT="${SPID_CIE_ROOT:-/var/www/spid-cie-php}"
    SPID_VERSION="${SPID_CIE_VERSION:-master}"
    SPID_SDK="${SPID_ROOT}/spid-php.php"
    SPID_VENDOR_AUTOLOAD="${SPID_ROOT}/vendor/autoload.php"

    if [ ! -f "${SPID_ROOT}/composer.json" ]; then
      echo "⚙️  SPID/CIE abilitato: installo sorgente spid-cie-php (${SPID_VERSION}) in ${SPID_ROOT}..."
      mkdir -p "$SPID_ROOT" || true
      # Scarico sorgente (no git) + installo dipendenze senza scripts (evita setup interattivo)
      if command -v curl >/dev/null 2>&1; then
        DOWNLOAD_URL=""
        if [ "$SPID_VERSION" = "master" ] || [ "$SPID_VERSION" = "main" ]; then
          DOWNLOAD_URL="https://github.com/italia/spid-cie-php/archive/refs/heads/${SPID_VERSION}.tar.gz"
        else
          DOWNLOAD_URL="https://github.com/italia/spid-cie-php/archive/refs/tags/${SPID_VERSION}.tar.gz"
        fi

        if ! curl -fsSL -L -o /tmp/spid-cie-php.tgz "$DOWNLOAD_URL"; then
          echo "⚠️  Download spid-cie-php fallito da ${DOWNLOAD_URL}. Provo fallback su master..." >&2
          DOWNLOAD_URL="https://github.com/italia/spid-cie-php/archive/refs/heads/master.tar.gz"
        fi

        if curl -fsSL -L -o /tmp/spid-cie-php.tgz "$DOWNLOAD_URL"; then
          if tar -xzf /tmp/spid-cie-php.tgz -C "$SPID_ROOT" --strip-components=1; then
            rm -f /tmp/spid-cie-php.tgz || true
            if [ -z "$COMPOSER_BIN" ]; then
              echo "⚠️  Composer non disponibile: impossibile installare dipendenze spid-cie-php." >&2
            fi
          else
            echo "⚠️  Estrazione spid-cie-php fallita." >&2
          fi
        else
          echo "⚠️  Download spid-cie-php fallito (GitHub non raggiungibile dal container)." >&2
        fi
      else
        echo "⚠️  curl non disponibile: impossibile installare spid-cie-php." >&2
      fi
    fi

    # Installa dipendenze se manca l'autoload (può essere impostato COMPOSER_VENDOR_DIR globale nel container)
    if [ ! -f "$SPID_VENDOR_AUTOLOAD" ] && [ -n "$COMPOSER_BIN" ] && [ -f "${SPID_ROOT}/composer.json" ]; then
      echo "⚙️  Installo dipendenze spid-cie-php (vendor isolato)..."
      (cd "$SPID_ROOT" && COMPOSER_VENDOR_DIR="$SPID_ROOT/vendor" $COMPOSER_BIN install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts --no-security-blocking) \
        || echo "⚠️  Composer install spid-cie-php fallito: verifica connessone/repo e riprova." >&2
    fi

    # Config minima SimpleSAMLphp (evita errori di configurazione e abilita metadata)
    SSP_ROOT="$SPID_ROOT/vendor/simplesamlphp/simplesamlphp"
    if [ -d "$SSP_ROOT" ]; then
      SSP_CONFIG_DIR="$SSP_ROOT/config"
      SSP_METADATA_DIR="$SSP_ROOT/metadata"
      SSP_CERT_DIR="$SSP_ROOT/cert"
      # Log in /tmp per evitare permessi su vendor/ (Apache non sempre può scrivere lì)
      SSP_LOG_DIR="/tmp/simplesamlphp-log"
      SSP_DATA_DIR="$SSP_ROOT/data"
      SSP_TEMP_DIR="$SSP_ROOT/temp"
      mkdir -p "$SSP_CONFIG_DIR" "$SSP_METADATA_DIR" "$SSP_CERT_DIR" "$SSP_LOG_DIR" "$SSP_DATA_DIR" "$SSP_TEMP_DIR" || true
      # Permessi: SimpleSAML (eseguito via Apache) deve poter scrivere in log/data/temp.
      # In alcune immagini l'utente può essere www-data; mantenere un fallback non bloccante.
      chown -R www-data:www-data "$SSP_LOG_DIR" "$SSP_DATA_DIR" "$SSP_TEMP_DIR" 2>/dev/null || true
      chown -R www-app:www-data "$SSP_LOG_DIR" "$SSP_DATA_DIR" "$SSP_TEMP_DIR" 2>/dev/null || true
      chmod 777 "$SSP_LOG_DIR" 2>/dev/null || true

      # Parametri base (override via env se serve)
      SSP_BASEURLPATH="${SPID_CIE_BASEURLPATH:-spid-cie/}"
      SSP_ADMIN_PWD="${SPID_CIE_ADMIN_PASSWORD:-admin}"
      SSP_SECRETSALT="${SPID_CIE_SECRETSALT:-}"
      SSP_SESSION_COOKIE_NAME="${SPID_CIE_SESSION_COOKIE_NAME:-SPIDCIESESSID}"

      # Per usare il DEMO IdP serve che lo SP sia registrato e che entityID/ACS/SLO
      # siano raggiungibili pubblicamente. Imposta SPID_CIE_PUBLIC_BASE_URL (es. https://<tunnel-host>).
      SSP_PUBLIC_BASE_URL="${SPID_CIE_PUBLIC_BASE_URL:-}"
      if [ -n "$SSP_PUBLIC_BASE_URL" ]; then
        SSP_PUBLIC_BASE_URL="${SSP_PUBLIC_BASE_URL%/}"
        SSP_ENTITY_ID_DEFAULT="${SSP_PUBLIC_BASE_URL}/spid-cie/module.php/saml/sp/metadata.php/spid"
        SSP_ACS_CUSTOM_LOCATION_DEFAULT="${SSP_PUBLIC_BASE_URL}/spid-cie/module.php/saml/sp/saml2-acs.php/spid"
        SSP_SLO_CUSTOM_LOCATION_DEFAULT="${SSP_PUBLIC_BASE_URL}/spid-cie/module.php/saml/sp/saml2-logout.php/spid"
      else
        SSP_ENTITY_ID_DEFAULT="https://localhost:8444/spid-cie/module.php/saml/sp/metadata.php/spid"
        SSP_ACS_CUSTOM_LOCATION_DEFAULT="https://localhost:8444/spid-cie/module.php/saml/sp/saml2-acs.php/spid"
        SSP_SLO_CUSTOM_LOCATION_DEFAULT="https://localhost:8444/spid-cie/module.php/saml/sp/saml2-logout.php/spid"
      fi

      SSP_ENTITY_ID="${SPID_CIE_SPID_ENTITY_ID:-$SSP_ENTITY_ID_DEFAULT}"
      SSP_ACS_CUSTOM_LOCATION="${SPID_CIE_ACS_CUSTOM_LOCATION:-$SSP_ACS_CUSTOM_LOCATION_DEFAULT}"
      SSP_SLO_CUSTOM_LOCATION="${SPID_CIE_SLO_CUSTOM_LOCATION:-$SSP_SLO_CUSTOM_LOCATION_DEFAULT}"
      if [ -z "$SSP_SECRETSALT" ] && command -v openssl >/dev/null 2>&1; then
        SSP_SECRETSALT="$(openssl rand -hex 16 2>/dev/null || true)"
      fi
      if [ -z "$SSP_SECRETSALT" ]; then
        SSP_SECRETSALT="changeme"
      fi

      # Certificati SP minimi
      if [ ! -f "$SSP_CERT_DIR/spid.key" ] || [ ! -f "$SSP_CERT_DIR/spid.crt" ]; then
        if command -v openssl >/dev/null 2>&1; then
          openssl req -x509 -newkey rsa:2048 -sha256 -days 3650 -nodes \
            -keyout "$SSP_CERT_DIR/spid.key" -out "$SSP_CERT_DIR/spid.crt" \
            -subj "/CN=spid" >/dev/null 2>&1 || true
        fi
      fi
      if [ ! -f "$SSP_CERT_DIR/cie.key" ] || [ ! -f "$SSP_CERT_DIR/cie.crt" ]; then
        if command -v openssl >/dev/null 2>&1; then
          openssl req -x509 -newkey rsa:2048 -sha256 -days 3650 -nodes \
            -keyout "$SSP_CERT_DIR/cie.key" -out "$SSP_CERT_DIR/cie.crt" \
            -subj "/CN=cie" >/dev/null 2>&1 || true
        fi
      fi

      # Le chiavi generate da root finiscono tipicamente con permessi 600 e owner root.
      # SimpleSAML gira come www-data e deve poter leggere i private key.
      chown -R www-data:www-data "$SSP_CERT_DIR" 2>/dev/null || true
      chmod 644 "$SSP_CERT_DIR"/*.crt 2>/dev/null || true
      chmod 600 "$SSP_CERT_DIR"/*.key 2>/dev/null || true

      # config.php (include anche acsCustomLocation/sloCustomLocation richiesti dal SAMLBuilder custom)
      if [ ! -f "$SSP_CONFIG_DIR/config.php" ]; then
        cat > "$SSP_CONFIG_DIR/config.php" <<PHP
<?php

\$config = [
    'baseurlpath' => '${SSP_BASEURLPATH}',
    'certdir' => 'cert/',
    'logging.handler' => 'file',
    'loggingdir' => '${SSP_LOG_DIR}/',
    'datadir' => 'data/',
    'tempdir' => 'temp/',
    'secretsalt' => '${SSP_SECRETSALT}',
    'auth.adminpassword' => '${SSP_ADMIN_PWD}',
    'admin.protectindexpage' => false,
    'language.default' => 'it',
    'store.type' => 'phpsession',
    'session.phpsession.cookiename' => '${SSP_SESSION_COOKIE_NAME}',
    'acsCustomLocation' => '${SSP_ACS_CUSTOM_LOCATION}',
    'sloCustomLocation' => '${SSP_SLO_CUSTOM_LOCATION}',
];

PHP
      fi

      # Se config.php esiste già (da una run precedente) ma manca le chiavi richieste, le inseriamo.
      if [ -f "$SSP_CONFIG_DIR/config.php" ]; then
        # Se è rimasta una loggingdir relativa, la spostiamo su /tmp (writable)
        if grep -q "'loggingdir' => 'log/'" "$SSP_CONFIG_DIR/config.php"; then
          sed -i "s#'loggingdir' => 'log/',#'loggingdir' => '${SSP_LOG_DIR}/',#" "$SSP_CONFIG_DIR/config.php" || true
        fi
        if ! grep -q "acsCustomLocation" "$SSP_CONFIG_DIR/config.php"; then
          sed -i "/^\];/i\\    'acsCustomLocation' => '${SSP_ACS_CUSTOM_LOCATION}'," "$SSP_CONFIG_DIR/config.php" || true
        fi
        if ! grep -q "sloCustomLocation" "$SSP_CONFIG_DIR/config.php"; then
          sed -i "/^\];/i\\    'sloCustomLocation' => '${SSP_SLO_CUSTOM_LOCATION}'," "$SSP_CONFIG_DIR/config.php" || true
        fi
        if ! grep -q "session\.phpsession\.cookiename" "$SSP_CONFIG_DIR/config.php"; then
          sed -i "/^\];/i\\    'session.phpsession.cookiename' => '${SSP_SESSION_COOKIE_NAME}'," "$SSP_CONFIG_DIR/config.php" || true
        fi

        # Se esistono già, aggiorniamo i valori per mantenere coerenza con le env (es. URL pubblico via tunnel).
        if grep -q "acsCustomLocation" "$SSP_CONFIG_DIR/config.php"; then
          sed -i "s#'acsCustomLocation' => '[^']*',#'acsCustomLocation' => '${SSP_ACS_CUSTOM_LOCATION}',#" "$SSP_CONFIG_DIR/config.php" 2>/dev/null || true
        fi
        if grep -q "sloCustomLocation" "$SSP_CONFIG_DIR/config.php"; then
          sed -i "s#'sloCustomLocation' => '[^']*',#'sloCustomLocation' => '${SSP_SLO_CUSTOM_LOCATION}',#" "$SSP_CONFIG_DIR/config.php" 2>/dev/null || true
        fi
        if grep -q "session\.phpsession\.cookiename" "$SSP_CONFIG_DIR/config.php"; then
          sed -i "s#'session\.phpsession\.cookiename' => '[^']*',#'session.phpsession.cookiename' => '${SSP_SESSION_COOKIE_NAME}',#" "$SSP_CONFIG_DIR/config.php" 2>/dev/null || true
        fi
      fi

      # authsources.php (necessario per metadata.php/spid e metadata.php/cie)
      SPID_ENTITY_ID="${SPID_CIE_SPID_ENTITY_ID:-$SSP_ENTITY_ID}"
      if [ -n "$SSP_PUBLIC_BASE_URL" ]; then
        CIE_ENTITY_ID_DEFAULT="${SSP_PUBLIC_BASE_URL}/spid-cie/module.php/saml/sp/metadata.php/cie"
      else
        CIE_ENTITY_ID_DEFAULT="https://localhost:8444/spid-cie/module.php/saml/sp/metadata.php/cie"
      fi
      CIE_ENTITY_ID="${SPID_CIE_CIE_ENTITY_ID:-$CIE_ENTITY_ID_DEFAULT}"

      # Dati organizzazione nel metadata SP (alcuni validatori/portali li richiedono esplicitamente).
      # Usiamo variabili già presenti nel progetto, con override dedicati se necessario.
      ORG_NAME_RAW="${SPID_CIE_ORGANIZATION_NAME:-${APP_ENTITY_NAME:-${APACHE_SERVER_NAME:-Service Provider}}}"
      ORG_URL_RAW="${SPID_CIE_ORGANIZATION_URL:-${URL_ENTE:-${SSP_PUBLIC_BASE_URL:-}}}"
      if [ -z "$ORG_URL_RAW" ]; then
        ORG_URL_RAW="https://${APACHE_SERVER_NAME:-localhost}"
      fi
      # Escape per PHP single-quoted string
      ORG_NAME="$(printf "%s" "$ORG_NAME_RAW" | sed "s/'/\\\\'/g")"
      ORG_URL="$(printf "%s" "$ORG_URL_RAW" | sed "s/'/\\\\'/g")"

      AUTH_SOURCES_NEED_UPDATE=0
      if [ ! -f "$SSP_CONFIG_DIR/authsources.php" ]; then
        AUTH_SOURCES_NEED_UPDATE=1
      else
        if ! grep -q "'entityID' => '${SPID_ENTITY_ID}'" "$SSP_CONFIG_DIR/authsources.php" 2>/dev/null; then
          AUTH_SOURCES_NEED_UPDATE=1
        fi
        if ! grep -q "'entityID' => '${CIE_ENTITY_ID}'" "$SSP_CONFIG_DIR/authsources.php" 2>/dev/null; then
          AUTH_SOURCES_NEED_UPDATE=1
        fi
        # Validatori SPID spesso richiedono md:Organization nel metadata: se manca, forziamo l'update.
        if ! grep -q "OrganizationName" "$SSP_CONFIG_DIR/authsources.php" 2>/dev/null; then
          AUTH_SOURCES_NEED_UPDATE=1
        fi
      fi

      if [ "$AUTH_SOURCES_NEED_UPDATE" -eq 1 ]; then
        cat > "$SSP_CONFIG_DIR/authsources.php" <<PHP
<?php

\$config = [
    'admin' => [
        'core:AdminPassword',
    ],
    'spid' => [
        'saml:SP',
        'entityID' => '${SPID_ENTITY_ID}',
        'privatekey' => 'spid.key',
        'certificate' => 'spid.crt',
        'OrganizationName' => ['it' => '${ORG_NAME}'],
        'OrganizationDisplayName' => ['it' => '${ORG_NAME}'],
        'OrganizationURL' => ['it' => '${ORG_URL}'],
    ],
    'cie' => [
        'saml:SP',
        'entityID' => '${CIE_ENTITY_ID}',
        'privatekey' => 'cie.key',
        'certificate' => 'cie.crt',
        'OrganizationName' => ['it' => '${ORG_NAME}'],
        'OrganizationDisplayName' => ['it' => '${ORG_NAME}'],
        'OrganizationURL' => ['it' => '${ORG_URL}'],
    ],
];

PHP
      fi
    fi

    # Popola metadata IdP (DEMO) + genera spid-php.php, senza setup interattivo
    if [ -f "$SPID_VENDOR_AUTOLOAD" ] && [ -f "${SPID_ROOT}/setup/Setup.php" ]; then
      IDP_METADATA_FILE="$SPID_ROOT/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-remote.php"
      SETUP_JSON="$SPID_ROOT/spid-php-setup.json"
      ENTITY_MARKER="$SPID_ROOT/.spid_entityid"

      NEED_METADATA_UPDATE=0
      if [ ! -f "$SPID_SDK" ] || [ ! -f "$IDP_METADATA_FILE" ]; then
        NEED_METADATA_UPDATE=1
      fi
      if [ -f "$ENTITY_MARKER" ]; then
        CURRENT_ENTITY_ID="$(cat "$ENTITY_MARKER" 2>/dev/null || true)"
        if [ "$CURRENT_ENTITY_ID" != "$SSP_ENTITY_ID" ]; then
          NEED_METADATA_UPDATE=1
        fi
      else
        NEED_METADATA_UPDATE=1
      fi

      # Manteniamo sempre aggiornato spid-php-setup.json: nel README è il file di riferimento per reinstall/aggiornamenti.
      cat > "$SETUP_JSON" <<JSON
{
  "installDir": "${SPID_ROOT}",
  "wwwDir": "/var/www/html/public",
  "serviceName": "spid-cie",
  "entityID": "${SSP_ENTITY_ID}",
  "addSPID": true,
  "addCIE": false,
  "addDemoIDP": true,
  "addDemoValidatorIDP": false,
  "addValidatorIDP": false,
  "addLocalTestIDP": "",
  "addProxyExample": false
}
JSON

      if [ "$NEED_METADATA_UPDATE" -eq 1 ]; then
        echo "⚙️  Genero/aggiorno metadata IdP (DEMO) e SDK helper (spid-php.php)..."
        echo "$SSP_ENTITY_ID" > "$ENTITY_MARKER" 2>/dev/null || true

        (cd "$SPID_ROOT" && php -r "require 'vendor/autoload.php'; \\SPID_PHP\\Setup::updateMetadata();") \
          || echo "⚠️  updateMetadata fallito (SPID registry/demo non raggiungibili?): riprova o controlla connettività." >&2
      fi

      # In alcune versioni dello script upstream, l'IdP DEMO viene scritto nei metadata ma non viene aggiunto
      # alla lista IDP nello SDK (spid-php.php). Per la demo aggiungiamo una mappatura esplicita se manca.
      if [ -f "$SPID_SDK" ] && [ -f "$IDP_METADATA_FILE" ]; then
        if grep -q "\$metadata\['https://demo\.spid\.gov\.it'\]" "$IDP_METADATA_FILE" 2>/dev/null; then
          if ! grep -q "\['DEMO'\]" "$SPID_SDK" 2>/dev/null; then
            echo "⚙️  Aggiungo IdP DEMO allo SDK (spid-php.php)..."
            sed -i "/\$this->service = \$service;/a\\
			\$this->idps['DEMO'] = 'https://demo.spid.gov.it';" "$SPID_SDK" || true
          fi
        fi

        # Evita warning/errore in runtime: lo SDK prova a creare spid-idps.json nella cwd.
        # In produzione la cwd spesso non è scrivibile; usiamo /tmp (sempre scrivibile).
        sed -i "s/file_put_contents('spid-idps.json',/file_put_contents(sys_get_temp_dir().'\/spid-idps.json',/g" "$SPID_SDK" 2>/dev/null || true
        sed -i "s/file_get_contents('spid-idps.json')/file_get_contents(sys_get_temp_dir().'\/spid-idps.json')/g" "$SPID_SDK" 2>/dev/null || true

        # Espone gli asset del bottone SPID/CIE sotto /spid-cie/ (alias Apache su www/ SimpleSAML).
        # Senza questi symlink, CSS/JS/img tornano 404 e il bottone non funziona correttamente.
        SSP_WWW_DIR="$SPID_ROOT/vendor/simplesamlphp/simplesamlphp/www"
        if [ -d "$SSP_WWW_DIR" ]; then
          # spid-sp-access-button: gli asset pubblici stanno sotto src/production/
          if [ -d "$SPID_ROOT/vendor/italia/spid-sp-access-button/src/production" ]; then
            rm -rf "$SSP_WWW_DIR/spid-sp-access-button" 2>/dev/null || true
            ln -s "$SPID_ROOT/vendor/italia/spid-sp-access-button/src/production" "$SSP_WWW_DIR/spid-sp-access-button" 2>/dev/null || true
          elif [ -d "$SPID_ROOT/vendor/italia/spid-sp-access-button" ]; then
            rm -rf "$SSP_WWW_DIR/spid-sp-access-button" 2>/dev/null || true
            ln -s "$SPID_ROOT/vendor/italia/spid-sp-access-button" "$SSP_WWW_DIR/spid-sp-access-button" 2>/dev/null || true
          fi
          if [ -d "$SPID_ROOT/vendor/italia/spid-smart-button" ]; then
            rm -rf "$SSP_WWW_DIR/spid-smart-button" 2>/dev/null || true
            ln -s "$SPID_ROOT/vendor/italia/spid-smart-button" "$SSP_WWW_DIR/spid-smart-button" 2>/dev/null || true
          fi
          if [ -d "$SPID_ROOT/vendor/italia/cie-graphics" ]; then
            rm -rf "$SSP_WWW_DIR/cie-graphics" 2>/dev/null || true
            ln -s "$SPID_ROOT/vendor/italia/cie-graphics" "$SSP_WWW_DIR/cie-graphics" 2>/dev/null || true
          fi
        fi
      fi
    fi
  fi
fi

echo "--- Setup completato. Eseguo controllo first-run DB ---"

if [ "$APP_SUITE" != "frontoffice" ]; then
  # First-run marker
  FIRST_RUN_MARKER="/var/www/html/.first_run_done"
  if [ -f "$FIRST_RUN_MARKER" ]; then
    echo "ℹ️  First-run DB già eseguito (marker presente)."
  else
    echo "⚙️  Primo avvio: creo tabelle richieste via PHP (bin/first_run_create_tables.php)"
    if command -v php >/dev/null 2>&1; then
      if [ -f "/var/www/html/bin/first_run_create_tables.php" ]; then
        # Wait for DB to be reachable with a short timeout
        DB_HOST=${DB_HOST:-db}
        DB_PORT=${DB_PORT:-3306}
        WAIT_DB_TIMEOUT=${DB_WAIT_TIMEOUT:-30}
        echo "Attendo DB ${DB_HOST}:${DB_PORT} per fino a ${WAIT_DB_TIMEOUT}s..."
        i=0
        until php -r "try { new PDO('mysql:host=${DB_HOST};port=${DB_PORT};', '${DB_USER:-govpay}', '${DB_PASSWORD:-}', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT=>2]); exit(0);} catch (Throwable \$e) { exit(1);}" >/dev/null 2>&1; do
          i=$((i+1))
          if [ "$i" -ge "$WAIT_DB_TIMEOUT" ]; then
            echo "⚠️ DB non raggiungibile dopo ${WAIT_DB_TIMEOUT}s: salto creazione tabelle in questo avvio." >&2
            break
          fi
          sleep 1
        done

        if [ "$i" -lt "$WAIT_DB_TIMEOUT" ]; then
          echo "Eseguo: php bin/first_run_create_tables.php"
          if ! php /var/www/html/bin/first_run_create_tables.php; then
            echo "⚠️ Errore: creazione tabelle fallita. Continuerò senza bloccare il container." >&2
          else
            touch "$FIRST_RUN_MARKER" || echo "⚠️  Impossibile creare marker $FIRST_RUN_MARKER" >&2
          fi
        fi
      else
        echo "ℹ️  Nessun script di creazione tabelle (bin/first_run_create_tables.php) trovato; salto." >&2
      fi
    else
      echo "⚠️  PHP CLI non disponibile: non posso creare tabelle (bin/first_run_create_tables.php mancante)." >&2
      # Non interrompiamo qui: proviamo le migrazioni SQL più sotto
    fi
  fi
fi

if [ "$APP_SUITE" != "frontoffice" ]; then
  # Additional SQL migrations runner: execute SQL files in migrations/ using mysql client if available
  MIG_DIR="/var/www/html/migrations"
  if [ -d "$MIG_DIR" ] && [ "$(ls -A "$MIG_DIR" | wc -l)" -gt 0 ]; then
    echo "--- Trovate migrazioni SQL in $MIG_DIR ---"
    DB_HOST=${DB_HOST:-db}
    DB_PORT=${DB_PORT:-3306}
    DB_NAME=${DB_NAME:-govpay}
    DB_USER=${DB_USER:-govpay}
    DB_PASS=${DB_PASSWORD:-}

    # Preferiamo usare il client mysql se disponibile
    if command -v mysql >/dev/null 2>&1; then
      echo "Eseguo migrazioni via client mysql..."
      for f in $(ls -1 "$MIG_DIR"/*.sql 2>/dev/null | sort); do
        echo "Eseguo $f"
        if ! mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p$DB_PASS} "$DB_NAME" < "$f"; then
          echo "⚠️  Fallito l'import di $f via mysql client; proseguo." >&2
        fi
      done

    # Fallback: PHP CLI può eseguire gli SQL
    elif command -v php >/dev/null 2>&1; then
      echo "Eseguo migrazioni via fallback PHP..."
      php -r '
        $dir = getenv("PWD") . "/migrations";
        $files = glob($dir."/*.sql");
        if(!$files) { exit(0); }
        $host = getenv("DB_HOST") ?: "db";
        $port = getenv("DB_PORT") ?: "3306";
        $db = getenv("DB_NAME") ?: "govpay";
        $user = getenv("DB_USER") ?: "govpay";
        $pass = getenv("DB_PASSWORD") ?: "";
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        try {
          $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
          foreach($files as $f) {
            echo "Eseguo PHP $f\n";
            $sql = file_get_contents($f);
            $pdo->exec($sql);
          }
        } catch (Throwable $e) {
          echo "PHP migration fallback failed: ". $e->getMessage() ."\n";
          exit(1);
        }
      '
    else
      echo "⚠️  Nessun metodo disponibile per eseguire migrazioni SQL (mysql client e PHP mancanti)." >&2
    fi
  fi
fi

echo "--- Avvio Apache. ---"

# Se nessun comando passato, avvia apache2-foreground di default
# Se è stato passato un comando custom (debug), eseguilo direttamente
if [ "$#" -gt 0 ]; then
  exec "$@"
fi

# Avvio standard Apache (sorgendo envvars per definire APACHE_RUN_DIR etc.)
if [ -f /etc/apache2/envvars ]; then
  # shellcheck disable=SC1091
  . /etc/apache2/envvars
fi
exec apache2 -DFOREGROUND
