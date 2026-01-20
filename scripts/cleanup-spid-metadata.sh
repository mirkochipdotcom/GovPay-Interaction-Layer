#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
METADATA_DIR="${1:-${SCRIPT_DIR}/../spid-proxy/metadata}"
CURRENT_DIR="${2:-${SCRIPT_DIR}/../spid-proxy/metadata-current}"
ARCHIVE_DIR="${3:-${SCRIPT_DIR}/../spid-proxy/metadata-archive}"
INCLUDE_CIE="${INCLUDE_CIE:-1}"

mkdir -p "$METADATA_DIR" "$CURRENT_DIR" "$ARCHIVE_DIR" >/dev/null 2>&1 || true

ts="$(date -u +"%Y%m%dT%H%M%SZ" 2>/dev/null || date +"%Y%m%d%H%M%S")"

echo "[cleanup] METADATA_DIR=$METADATA_DIR"
echo "[cleanup] CURRENT_DIR=$CURRENT_DIR"
echo "[cleanup] ARCHIVE_DIR=$ARCHIVE_DIR"

move_to_archive() {
  local src="$1"
  local base
  base="$(basename "$src")"
  local dst="$ARCHIVE_DIR/$base"
  if [ -e "$dst" ]; then
    dst="$ARCHIVE_DIR/${base}.${ts}.xml"
  fi
  mv -f "$src" "$dst"
  echo "[cleanup] Archived -> $dst"
}

ensure_current_in_place() {
  local kind="$1"
  local cur_name="${kind}-metadata-current.xml"
  if [ ! -f "$CURRENT_DIR/$cur_name" ] && [ -f "$METADATA_DIR/$cur_name" ]; then
    mv -f "$METADATA_DIR/$cur_name" "$CURRENT_DIR/$cur_name"
    echo "[cleanup] Moved CURRENT ($kind) -> $CURRENT_DIR/$cur_name"
  fi
}

ensure_current_in_place spid
if [ "$INCLUDE_CIE" = "1" ]; then
  ensure_current_in_place cie
fi

# In metadata/ devono restare solo:
# - .gitkeep
# - *-metadata-next.xml
# Tutto il resto viene archiviato.
shopt -s nullglob
for f in "$METADATA_DIR"/*; do
  base="$(basename "$f")"
  if [ "$base" = ".gitkeep" ]; then
    continue
  fi
  if [[ "$base" =~ ^(spid|cie)-metadata-next\.xml$ ]]; then
    continue
  fi
  move_to_archive "$f"
done

echo "[cleanup] Done."
