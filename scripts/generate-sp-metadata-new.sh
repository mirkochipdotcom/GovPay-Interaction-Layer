#!/bin/sh
set -e

# Genera un nuovo metadata SP con suffisso -new.xml (senza sovrascrivere quello esistente).
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Usa la stessa variabile di ambiente del bootstrap
: "${FRONTOFFICE_PUBLIC_BASE_URL:=https://127.0.0.1:8444}"

sh "${SCRIPT_DIR}/ensure-sp-metadata.sh" --new
