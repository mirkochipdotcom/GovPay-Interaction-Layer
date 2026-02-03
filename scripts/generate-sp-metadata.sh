#!/bin/bash
set -e

# Script per generare i metadata del Service Provider (Frontoffice)
# Viene eseguito al primo avvio dal container frontoffice

METADATA_DIR="${1:-.}"
FRONTOFFICE_PUBLIC_BASE_URL="${2:-https://127.0.0.1:8444}"
METADATA_FILE="${METADATA_DIR}/frontoffice_sp.xml"

# Se il file esiste già, non rigenerare
if [ -f "$METADATA_FILE" ]; then
    echo "[INFO] Metadata SP già presente: $METADATA_FILE"
    exit 0
fi

# Crea la directory se non esiste
mkdir -p "$METADATA_DIR"

echo "[INFO] Generando metadata SP per: $FRONTOFFICE_PUBLIC_BASE_URL"

# Genera i metadata usando PHP
php /tmp/generate_sp_metadata.php "$FRONTOFFICE_PUBLIC_BASE_URL" > "$METADATA_FILE"

if [ -f "$METADATA_FILE" ] && [ -s "$METADATA_FILE" ]; then
    echo "[OK] Metadata SP generati con successo: $METADATA_FILE"
    exit 0
else
    echo "[ERROR] Fallito il caricamento dei metadata SP"
    exit 1
fi
