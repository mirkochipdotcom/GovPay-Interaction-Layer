#!/bin/bash
set -e

# Entra nella directory del progetto dove ci sono i file di configurazione
cd /app

echo "--- Esecuzione Setup Composer Autocorreggente ---"

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
  openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /ssl/server.key -out /ssl/server.crt \
    -subj "/CN=localhost" >/dev/null 2>&1 || {
    echo "❌ Errore: openssl non disponibile o generazione fallita";
  }
  chmod 600 /ssl/server.key || true
  chmod 644 /ssl/server.crt || true
  echo "✅ Certificati self-signed creati.";
fi

# === 1. SCENARIO AGGIORNAMENTO/RIGENERAZIONE LOCK ===
# Se il file lock NON esiste E la cartella vendor ESISTE,
# l'utente ha ELIMINATO il lock per forzare un aggiornamento.
if [ ! -f composer.lock ] && [ -d /var/www/html/vendor ]; then
  echo '🟡 ATTENZIONE: composer.lock mancante. Eseguo: composer update per rigenerarlo...'
  composer update --no-dev --optimize-autoloader;

# === 2. SCENARIO PRIMA INSTALLAZIONE (Nuovo progetto o pulizia completa) ===
# Se il file lock NON esiste E la cartella vendor NON esiste (primo avvio/pulizia totale)
elif [ ! -f composer.lock ] && [ ! -d /var/www/html/vendor ]; then
  echo '🔴 ATTENZIONE: Nessun artefatto trovato. Eseguo: composer install...'
  composer install --no-dev --optimize-autoloader;

# === 3. SCENARIO NORMALE (Dump-autoload veloce) ===
else
  echo '✅ Artefatti trovati. Eseguo dump-autoload o update condizionale...'

  # Puoi mantenere qui la tua logica originale di update/dump
  if [ /var/www/html/vendor/composer/installed.json -ot composer.json ]; then
      echo 'Eseguo: composer update (file modificati)...'
      composer update --no-dev --optimize-autoloader;
  else
      echo 'Eseguo: composer dump-autoload...'
      composer dump-autoload;
  fi
fi

echo "--- Setup Composer completato. Avvio Apache. ---"

# Esegue il comando finale che ti interessa (es. apache2-foreground)
exec "$@"
