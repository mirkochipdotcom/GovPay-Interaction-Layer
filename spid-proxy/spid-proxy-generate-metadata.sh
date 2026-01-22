#!/usr/bin/env bash
set -euo pipefail

TARGET_DIR="${TARGET_DIR:-/var/www/spid-cie-php}"
SERVICE_NAME="${SPID_PROXY_SERVICE_NAME:-myservice}"
META_DIR="${SPID_PROXY_METADATA_DIR:-${TARGET_DIR}/www/metadata}"
STRICT="${SPID_PROXY_METADATA_STRICT:-1}"
PUBLISH_SUFFIX="${SPID_PROXY_METADATA_PUBLISH_SUFFIX:-next}"

# Usa 127.0.0.1 (IPv4) perché curl può risolvere localhost su ::1 (IPv6)
META_BASE_URL="https://127.0.0.1/${SERVICE_NAME}/module.php/saml/sp/metadata.php"

mkdir -p "${META_DIR}" || true
TS="$(date -u +"%Y%m%dT%H%M%SZ" 2>/dev/null || date +"%Y%m%d%H%M%S")"

echo "[spid-proxy] Metadata snapshot: serviceName=${SERVICE_NAME} META_DIR=${META_DIR} strict=${STRICT}"
ls -la "${META_DIR}" 2>/dev/null || true

snapshot_one() {
  local kind="$1"
  local url="$2"
  local out_file="$3"
  local http_code
  local public_hostport
  local public_origin
  local desired_entity_id

  echo "[spid-proxy] Snapshot metadata ${kind}: ${out_file} (${url})"

  # Importante: il generator interroga Apache via https://127.0.0.1/... dentro al container.
  # SimpleSAML costruisce ACS/SLO basandosi su Host della request; se non forziamo Host
  # finiremmo con Location=...127.0.0.1... nei metadata anche se esiste un URL pubblico.
  public_hostport=""
  public_origin=""
  if [ -n "${SPID_PROXY_PUBLIC_BASE_URL:-}" ]; then
    public_hostport="${SPID_PROXY_PUBLIC_BASE_URL#*://}"
    public_hostport="${public_hostport%%/*}"
    public_origin="$(echo "${SPID_PROXY_PUBLIC_BASE_URL%/}" | sed -E 's#^(https?://[^/]+).*$#\1#')"
  fi

  if [ -n "${public_hostport}" ]; then
    http_code="$(curl -ksS --max-time 30 -H "Host: ${public_hostport}" -o "${out_file}" -w "%{http_code}" "${url}" || echo "000")"
  else
    http_code="$(curl -ksS --max-time 30 -o "${out_file}" -w "%{http_code}" "${url}" || echo "000")"
  fi

  if [ "${http_code}" != "200" ]; then
    echo "[spid-proxy] WARNING: snapshot metadata ${kind} HTTP ${http_code} (${url})" >&2
    echo "[spid-proxy] Response headers (first 20 lines):" >&2
    curl -kIsS --max-time 10 "${url}" 2>/dev/null | head -n 20 >&2 || true
    echo "[spid-proxy] Apache error.log (tail):" >&2
    tail -n 120 /var/log/apache2/error.log >&2 || true
    return 1
  fi

  if [ ! -s "${out_file}" ]; then
    echo "[spid-proxy] WARNING: snapshot metadata ${kind} vuoto o fallito (${out_file})" >&2
    rm -f "${out_file}" || true
    return 1
  fi

  # Validazione minimale: deve essere un metadata SAML, non HTML/error page.
  if ! grep -q "<md:EntityDescriptor" "${out_file}" 2>/dev/null; then
    echo "[spid-proxy] WARNING: snapshot ${kind} non sembra un metadata SAML (manca <md:EntityDescriptor>): ${out_file}" >&2
    echo "[spid-proxy] Response headers (first 20 lines):" >&2
    curl -kIsS --max-time 10 "${url}" 2>/dev/null | head -n 20 >&2 || true
    echo "[spid-proxy] Response body (first 2000 bytes):" >&2
    head -c 2000 "${out_file}" 2>/dev/null | tr -d '\r' >&2 || true
    echo >&2
    echo "[spid-proxy] Apache error.log (tail):" >&2
    tail -n 160 /var/log/apache2/error.log >&2 || true
    rm -f "${out_file}" || true
    return 1
  fi

  # NOTA: non riscriviamo entityID/Location nel file XML.
  # I metadata sono firmati (XMLDSIG): qualsiasi modifica post-generazione invalida la Signature.
  # Se serve un host pubblico corretto, va ottenuto PRIMA della generazione (es. forzando Host/URL base).

  # Pubblica SEMPRE un file stabile "next" (default), oltre allo snapshot timestampato.
  cp -f "${out_file}" "${META_DIR}/${kind}-metadata-${PUBLISH_SUFFIX}.xml" || true
  echo "[spid-proxy] Pubblicato metadata ${kind} ${PUBLISH_SUFFIX^^}: ${META_DIR}/${kind}-metadata-${PUBLISH_SUFFIX}.xml"
}

if ! command -v curl >/dev/null 2>&1; then
  echo "[spid-proxy] ERROR: curl non disponibile, impossibile fare snapshot metadata" >&2
  exit 1
fi

if ! apache2ctl -t >/dev/null 2>&1; then
  echo "[spid-proxy] ERROR: apache2ctl -t fallito (config Apache non valida)" >&2
  apache2ctl -t || true
  exit 1
fi

apache2ctl start >/dev/null 2>&1 || true

# Attendi che Apache risponda (anche solo con 301/404) su / per evitare race.
for _i in $(seq 1 30 2>/dev/null || echo "1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30"); do
  _code="$(curl -ksS --max-time 2 -o /dev/null -w "%{http_code}" "https://127.0.0.1/" 2>/dev/null || echo "000")"
  if [ "${_code}" != "000" ]; then
    break
  fi
  sleep 1
done

fail=0

snapshot_one "spid" "${META_BASE_URL}/spid" "${META_DIR}/spid-metadata-${TS}.xml" || fail=1

if [ "${SPID_PROXY_ADD_CIE:-0}" = "1" ]; then
  snapshot_one "cie" "${META_BASE_URL}/cie" "${META_DIR}/cie-metadata-${TS}.xml" || fail=1
fi

apache2ctl stop >/dev/null 2>&1 || true

echo "[spid-proxy] Metadata snapshot complete. Files in ${META_DIR}:"
ls -la "${META_DIR}" 2>/dev/null || true

if [ "${STRICT}" = "1" ] && [ "${fail}" != "0" ]; then
  echo "[spid-proxy] ERROR: one or more metadata snapshots failed (strict mode)." >&2
  exit 1
fi
