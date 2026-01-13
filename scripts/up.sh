#!/usr/bin/env sh
set -eu

# Usage:
#   scripts/up.sh [docker compose up args...]
# Examples:
#   scripts/up.sh -d --build --force-recreate
#   scripts/up.sh -d
#
# Reads SPID_PROXY_MODE from .env:
#   off|external => do NOT enable spid-proxy profile
#   internal     => enable spid-proxy profile

repo_root="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$repo_root"

mode=""
if [ -f .env ]; then
  # Extract first occurrence; strip quotes/spaces.
  mode="$(sed -n 's/^[[:space:]]*SPID_PROXY_MODE[[:space:]]*=[[:space:]]*//p' .env | head -n 1 | tr -d '"\r' | tr -d "'" | tr -d '[:space:]')"
fi

if [ -z "$mode" ]; then
  mode="external"
fi

case "$mode" in
  internal)
    # Merge with existing COMPOSE_PROFILES (comma-separated)
    profiles="${COMPOSE_PROFILES:-}"
    case ",${profiles}," in
      *,spid-proxy,*) : ;;
      *)
        if [ -n "$profiles" ]; then
          profiles="$profiles,spid-proxy"
        else
          profiles="spid-proxy"
        fi
        ;;
    esac
    export COMPOSE_PROFILES="$profiles"
    ;;
  off|external)
    # Remove spid-proxy from COMPOSE_PROFILES if present
    profiles="${COMPOSE_PROFILES:-}"
    if [ -n "$profiles" ]; then
      # split by comma, filter, re-join
      new=""
      oldIFS="$IFS"
      IFS=','
      for p in $profiles; do
        p="$(echo "$p" | tr -d '[:space:]')"
        [ -z "$p" ] && continue
        [ "$p" = "spid-proxy" ] && continue
        if [ -n "$new" ]; then new="$new,$p"; else new="$p"; fi
      done
      IFS="$oldIFS"
      if [ -n "$new" ]; then
        export COMPOSE_PROFILES="$new"
      else
        unset COMPOSE_PROFILES || true
      fi
    fi
    ;;
  *)
    echo "[up.sh] Valore SPID_PROXY_MODE non valido: '$mode' (usa: off|external|internal)" >&2
    exit 2
    ;;
esac

echo "[up.sh] SPID_PROXY_MODE=$mode"
echo "[up.sh] COMPOSE_PROFILES=${COMPOSE_PROFILES:-}" 

exec docker compose up "$@"
