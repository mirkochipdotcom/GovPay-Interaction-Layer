#!/usr/bin/env bash
# NOTE: questo script richiede bash. Se viene invocato con `sh script.sh ...`,
# questa guardia lo re-esegue con bash per evitare errori tipo "Bad substitution".
set -eu
if [ -z "${BASH_VERSION:-}" ]; then
  exec bash "$0" "$@"
fi
set -o pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Defaults
DATA_DIR="${SCRIPT_DIR}/../spid-proxy/data"
RESET_SETUP=0
RESET_VENDOR=0
RESTART=0
SERVICE="spid-proxy"

usage() {
  cat <<'EOF'
Usage:
  ./scripts/regenerate-spid-proxy-config.sh [options]

Options:
  -DataDir <path>     Directory persistita del proxy (default: spid-proxy/data)
  -ResetSetup         Rimuove anche spid-php-setup.json (più invasivo)
  -ResetVendor        Rimuove anche vendor/ (molto invasivo)
  -Restart            Ricrea il container docker compose (force-recreate)
  -Service <name>     Nome servizio compose (default: spid-proxy)
  -h|--help           Mostra questo help

Compat:
  Puoi anche usare variabili d'ambiente: RESET_SETUP=1 RESET_VENDOR=1 RESTART=1 SERVICE=...
EOF
}

# Supporta sia flag stile PowerShell (-ResetSetup) sia env var (RESET_SETUP=1)
if [ "${RESET_SETUP:-}" = "1" ]; then RESET_SETUP=1; fi
if [ "${RESET_VENDOR:-}" = "1" ]; then RESET_VENDOR=1; fi
if [ "${RESTART:-}" = "1" ]; then RESTART=1; fi
if [ -n "${SERVICE:-}" ]; then SERVICE="${SERVICE}"; fi

while [ "$#" -gt 0 ]; do
  case "$1" in
    -h|--help)
      usage
      exit 0
      ;;
    -DataDir|--data-dir)
      shift
      [ "$#" -gt 0 ] || { echo "[regen] ERROR: -DataDir richiede un valore" >&2; exit 1; }
      DATA_DIR="$1"
      ;;
    -ResetSetup|--reset-setup)
      RESET_SETUP=1
      ;;
    -ResetVendor|--reset-vendor)
      RESET_VENDOR=1
      ;;
    -Restart|--restart)
      RESTART=1
      ;;
    -Service|--service)
      shift
      [ "$#" -gt 0 ] || { echo "[regen] ERROR: -Service richiede un valore" >&2; exit 1; }
      SERVICE="$1"
      ;;
    *)
      echo "[regen] ERROR: argomento non riconosciuto: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
  shift
done

echo "[regen] DATA_DIR=${DATA_DIR}"
echo "[regen] RESET_SETUP=${RESET_SETUP}"
echo "[regen] RESET_VENDOR=${RESET_VENDOR}"
echo "[regen] RESTART=${RESTART}"
echo "[regen] SERVICE=${SERVICE}"

if [ ! -d "${DATA_DIR}" ]; then
  echo "[regen] ERROR: Data dir non esiste: ${DATA_DIR}" >&2
  exit 1
fi

rm -f "${DATA_DIR}/spid-php-proxy.json" || true
rm -f "${DATA_DIR}/www/proxy-home.php" || true

if [ "${RESET_SETUP}" = "1" ]; then
  rm -f "${DATA_DIR}/spid-php-setup.json" || true
  rm -f "${DATA_DIR}/spid-php-openssl.cnf" || true
fi

if [ "${RESET_VENDOR}" = "1" ]; then
  rm -rf "${DATA_DIR}/vendor" || true
fi

echo "[regen] Done."

if [ "${RESTART}" = "1" ]; then
  echo "[regen] Recreating container: ${SERVICE}"
  docker compose up -d --force-recreate "${SERVICE}"
fi
