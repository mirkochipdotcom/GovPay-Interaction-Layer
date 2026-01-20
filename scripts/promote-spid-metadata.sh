#!/usr/bin/env bash
set -euo pipefail

SOURCE_DIR="${1:-$(cd "$(dirname "$0")/../spid-proxy/metadata" && pwd)}"
TARGET_DIR="${2:-$(cd "$(dirname "$0")/../spid-proxy/metadata-current" && pwd)}"
ARCHIVE_DIR="${3:-$(cd "$(dirname "$0")/../spid-proxy/metadata-archive" && pwd)}"
INCLUDE_CIE="${INCLUDE_CIE:-1}"

ts="$(date -u +"%Y%m%dT%H%M%SZ" 2>/dev/null || date +"%Y%m%d%H%M%S")"

assert_xml_valid() {
  local path="$1"
  local kind="$2"

  if [ ! -f "$path" ]; then
    echo "[promote] ERROR: file mancante: $path" >&2
    exit 1
  fi
  if [ ! -s "$path" ]; then
    echo "[promote] ERROR: file vuoto: $path" >&2
    exit 1
  fi

  # Validazione minimale: deve contenere EntityDescriptor e entityID
  if ! grep -q "<md:EntityDescriptor" "$path"; then
    echo "[promote] ERROR: XML non sembra un metadata SAML (manca <md:EntityDescriptor): $path" >&2
    exit 1
  fi
  if ! grep -q "entityID=\"" "$path"; then
    echo "[promote] ERROR: entityID mancante: $path" >&2
    exit 1
  fi
}

promote_one() {
  local kind="$1"
  local next="$SOURCE_DIR/${kind}-metadata-next.xml"
  local cur="$TARGET_DIR/${kind}-metadata-current.xml"

  echo "[promote] Checking $kind NEXT: $next"
  assert_xml_valid "$next" "$kind"

  if [ -f "$cur" ]; then
    local bak="$ARCHIVE_DIR/${kind}-metadata-current.${ts}.bak.xml"
    mv -f "$cur" "$bak"
    echo "[promote] Archived CURRENT -> $bak"
  fi

  cp -f "$next" "$cur"
  echo "[promote] PROMOTED $kind: $(basename "$next") -> $(basename "$cur")"
}

mkdir -p "$TARGET_DIR" "$ARCHIVE_DIR" >/dev/null 2>&1 || true

echo "[promote] SOURCE_DIR=$SOURCE_DIR"
echo "[promote] TARGET_DIR=$TARGET_DIR"
echo "[promote] ARCHIVE_DIR=$ARCHIVE_DIR"

promote_one spid
if [ "$INCLUDE_CIE" = "1" ]; then
  promote_one cie
fi

echo "[promote] Done."
