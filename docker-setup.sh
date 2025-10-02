#!/bin/bash
set -e

# Entra nella directory del progetto dove ci sono i file di configurazione
cd /app

echo "--- Esecuzione Setup Composer Autocorreggente ---"

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
