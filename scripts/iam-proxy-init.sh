#!/bin/bash
# IAM Proxy Italia initialization script
# Runs at container startup to ensure instance files exist

set -e

SATOSA_PROXY="${SATOSA_PROXY:-/satosa_proxy}"
REPO_URL="${IAM_PROXY_REPO_URL:-https://github.com/italia/iam-proxy-italia/archive/refs/heads/master.zip}"

if [ -f "$SATOSA_PROXY/proxy_conf.yaml" ]; then
  echo "[init] Instance already initialized at $SATOSA_PROXY"
  exec /satosa_proxy/entrypoint.sh "$@"
fi

echo "[init] Initializing IAM Proxy Italia instance..."

# Create base directories
mkdir -p "$SATOSA_PROXY"

# Download and extract from GitHub
TMP_ROOT="/tmp/iam-proxy-init-$$"
mkdir -p "$TMP_ROOT"
cd "$TMP_ROOT"

echo "[init] Downloading from $REPO_URL"
curl -fsSL "$REPO_URL" -o src.zip

echo "[init] Extracting archive..."
unzip -q src.zip

# Find extracted directory
EXTRACTED=$(find . -maxdepth 1 -type d -name "iam-proxy-italia-*" | head -1)
if [ -z "$EXTRACTED" ]; then
  echo "[init] ERROR: Could not find extracted iam-proxy-italia directory"
  exit 1
fi

PROJECT_SRC="$EXTRACTED/iam-proxy-italia-project"
if [ ! -d "$PROJECT_SRC" ]; then
  echo "[init] ERROR: iam-proxy-italia-project not found in archive"
  exit 1
fi

echo "[init] Copying files to $SATOSA_PROXY"
cp -r "$PROJECT_SRC"/* "$SATOSA_PROXY/"

# Generate dev certs if script exists
if [ -f "$SATOSA_PROXY/pki/generate-dev-certs.sh" ]; then
  echo "[init] Generating development certificates..."
  cd "$SATOSA_PROXY/pki"
  bash generate-dev-certs.sh || true
  cd /
fi

# Cleanup
rm -rf "$TMP_ROOT"

echo "[init] Instance initialization complete"

# Start SATOSA
exec /satosa_proxy/entrypoint.sh "$@"
