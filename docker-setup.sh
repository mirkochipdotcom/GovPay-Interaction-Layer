#!/bin/bash
set -euo pipefail

# Directory applicativa effettiva nel runtime
APP_DIR="/var/www/html"
cd "$APP_DIR"

echo "--- Esecuzione Setup Composer Autocorreggente ---"

INIT_MARKER="${APP_DIR}/.init_done"
RUN_COMPOSER=1
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
      -subj "/CN=localhost" >/dev/null 2>&1; then
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
  echo "ℹ️  Nessuna operazione Composer eseguita (composer mancante)"
fi

if [ "$RUN_COMPOSER" -eq 1 ]; then
  touch "$INIT_MARKER" || true
fi

echo "--- Setup completato. Eseguo migrazioni DB (non-bloccanti) ---"

# Esegui migrazioni database (non fatali in caso di errore)
if command -v php >/dev/null 2>&1; then
  if [ -f "/var/www/html/bin/run-migrations.php" ]; then
    echo "Eseguo: php bin/run-migrations.php"
    if ! php /var/www/html/bin/run-migrations.php; then
      echo "⚠️  Avviso: migrazioni fallite, proseguo comunque con l'avvio di Apache." >&2
    fi
  else
    echo "ℹ️  Nessun file di migrazione trovato (bin/run-migrations.php)."
  fi
else
  echo "ℹ️  PHP CLI non disponibile: salto migrazioni."
fi

# Additional SQL migrations runner: execute SQL files in migrations/ using mysql client if available
MIG_DIR="/var/www/html/migrations"
if [ -d "$MIG_DIR" ] && [ "$(ls -A $MIG_DIR | wc -l)" -gt 0 ]; then
  echo "--- Trovate migrazioni SQL in $MIG_DIR ---"
  if command -v mysql >/dev/null 2>&1; then
    echo "Eseguo migrazioni via client mysql..."
    DB_HOST=${DB_HOST:-db}
    DB_PORT=${DB_PORT:-3306}
    DB_NAME=${DB_NAME:-govpay}
    DB_USER=${DB_USER:-govpay}
    DB_PASS=${DB_PASSWORD:-}
    for f in $(ls -1 $MIG_DIR/*.sql 2>/dev/null | sort); do
      echo "Eseguo $f"
      if ! mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p$DB_PASS} "$DB_NAME" < "$f"; then
        echo "⚠️  Fallito l'import di $f via mysql client; proseguo." >&2
      fi
    done
  else
    echo "ℹ️  mysql client non disponibile. Provo fallback PHP per eseguire migrazioni SQL."
    if command -v php >/dev/null 2>&1; then
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
