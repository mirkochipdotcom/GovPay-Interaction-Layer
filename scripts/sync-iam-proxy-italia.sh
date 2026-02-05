#!/bin/bash
# Sync IAM Proxy Italia project files from upstream repository
# Idempotent: can be run multiple times to keep files updated

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
PROJECT_DST="$REPO_ROOT/iam-proxy/iam-proxy-italia-project"
REPO_URL="${IAM_PROXY_REPO_URL:-https://github.com/italia/iam-proxy-italia.git}"
REF="${IAM_PROXY_REF:-master}"

echo "[sync-iam-proxy] Syncing IAM Proxy Italia from $REPO_URL (ref: $REF)"

# Create temp directory
TMP_ROOT="/tmp/iam-proxy-sync-$$"
mkdir -p "$TMP_ROOT"

cleanup() {
  rm -rf "$TMP_ROOT"
}
trap cleanup EXIT

cd "$TMP_ROOT"

# Clone repository with specific ref
echo "[sync-iam-proxy] Cloning repository..."
git clone --depth 1 --branch "$REF" "$REPO_URL" repo

PROJECT_SRC="$TMP_ROOT/repo/iam-proxy-italia-project"
if [ ! -d "$PROJECT_SRC" ]; then
  echo "[sync-iam-proxy] ERROR: iam-proxy-italia-project not found in repository"
  exit 1
fi

# Ensure destination exists
mkdir -p "$PROJECT_DST"

# Preserve .gitkeep if exists
GITKEEP_TMP=""
if [ -f "$PROJECT_DST/.gitkeep" ]; then
  GITKEEP_TMP="/tmp/.gitkeep-$$"
  cp "$PROJECT_DST/.gitkeep" "$GITKEEP_TMP"
fi

# Backup existing configuration files (if you want to preserve custom changes)
# Uncomment if you need to preserve custom configs:
# BACKUP_FILES=("proxy_conf.yaml" "internal_attributes.yaml")
# for file in "${BACKUP_FILES[@]}"; do
#   if [ -f "$PROJECT_DST/$file" ]; then
#     echo "[sync-iam-proxy] Backing up $file"
#     cp "$PROJECT_DST/$file" "$PROJECT_DST/$file.bak"
#   fi
# done

# Clean destination (keep only .gitkeep)
echo "[sync-iam-proxy] Cleaning destination..."
find "$PROJECT_DST" -mindepth 1 ! -name '.gitkeep' -delete

# Copy all files from source
echo "[sync-iam-proxy] Copying files from upstream..."
cp -r "$PROJECT_SRC"/* "$PROJECT_DST/"
cp -r "$PROJECT_SRC"/.??* "$PROJECT_DST/" 2>/dev/null || true

# Restore .gitkeep
if [ -n "$GITKEEP_TMP" ] && [ -f "$GITKEEP_TMP" ]; then
  cp "$GITKEEP_TMP" "$PROJECT_DST/.gitkeep"
  rm -f "$GITKEEP_TMP"
fi

# Generate dev certs if script exists and certs don't exist
if [ -f "$PROJECT_DST/pki/generate-dev-certs.sh" ]; then
  if [ ! -f "$PROJECT_DST/pki/cert.pem" ] || [ ! -f "$PROJECT_DST/pki/privkey.pem" ]; then
    echo "[sync-iam-proxy] Generating development certificates..."
    (cd "$PROJECT_DST/pki" && bash generate-dev-certs.sh) || true
  else
    echo "[sync-iam-proxy] Development certificates already exist"
  fi
fi

# Create required directories if they don't exist
mkdir -p "$PROJECT_DST/logs"
mkdir -p "$PROJECT_DST/metadata/idp"
mkdir -p "$PROJECT_DST/metadata/sp"

# Set permissions for directories that SATOSA needs to write to
# SATOSA container runs as user satosa (UID 100, GID 101)
# Make logs writable by all (container user needs write access)
chmod -R 777 "$PROJECT_DST/logs" 2>/dev/null || true
chmod -R 777 "$PROJECT_DST/metadata" 2>/dev/null || true
chmod -R 755 "$PROJECT_DST/pki" 2>/dev/null || true

echo "[sync-iam-proxy] Sync complete. Files are now up-to-date with upstream."
echo "[sync-iam-proxy] You can now run: docker compose --profile iam-proxy up -d"
