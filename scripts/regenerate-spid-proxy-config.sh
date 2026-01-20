#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DATA_DIR="${1:-${SCRIPT_DIR}/../spid-proxy/data}"
RESET_SETUP="${RESET_SETUP:-0}"
RESET_VENDOR="${RESET_VENDOR:-0}"
RESTART="${RESTART:-0}"
SERVICE="${SERVICE:-spid-proxy}"

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
