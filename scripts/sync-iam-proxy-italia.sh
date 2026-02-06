#!/bin/bash
# Sync IAM Proxy Italia project files from upstream repository
# Idempotent: can be run multiple times to keep files updated

set -e

is_true() {
  case "${1:-}" in
    1|true|TRUE|True|yes|YES|Yes|on|ON|On) return 0 ;;
    *) return 1 ;;
  esac
}

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

echo "[sync-iam-proxy] Skipping JS overrides for disco (use upstream defaults)"

# Generate i18n JSON files from templates with environment variables
echo "[sync-iam-proxy] Generating i18n JSON files from templates..."
if [ -f "$REPO_ROOT/iam-proxy/wallets-it.json.template" ]; then
  mkdir -p "$PROJECT_DST/static/locales/it"
  envsubst < "$REPO_ROOT/iam-proxy/wallets-it.json.template" > "$PROJECT_DST/static/locales/it/wallets.json"
  echo "[sync-iam-proxy] Generated wallets-it.json with environment variables"
fi
if [ -f "$REPO_ROOT/iam-proxy/wallets-en.json.template" ]; then
  mkdir -p "$PROJECT_DST/static/locales/en"
  envsubst < "$REPO_ROOT/iam-proxy/wallets-en.json.template" > "$PROJECT_DST/static/locales/en/wallets.json"
  echo "[sync-iam-proxy] Generated wallets-en.json with environment variables"
fi

# Generate customized spid_base.html template from override
echo "[sync-iam-proxy] Generating customized spid_base.html template..."
if [ -f "$REPO_ROOT/iam-proxy/spid_base_override.html.template" ]; then
  # Backup original if not exists
  [ ! -f "$PROJECT_DST/templates/spid_base.html.original" ] && cp "$PROJECT_DST/templates/spid_base.html" "$PROJECT_DST/templates/spid_base.html.original"
  
  envsubst < "$REPO_ROOT/iam-proxy/spid_base_override.html.template" > "$PROJECT_DST/templates/spid_base.html"
  echo "[sync-iam-proxy] Generated spid_base.html with environment variables"
fi

echo "[sync-iam-proxy] Skipping disco config overrides (use upstream defaults)"

# Override CIE OIDC backend config from template (envsubst)
if [ -f "$REPO_ROOT/iam-proxy/cieoidc_backend.override.yaml.template" ]; then
  echo "[sync-iam-proxy] Applying CIE OIDC backend override from template..."
  mkdir -p "$PROJECT_DST/conf/backends"
  # Default CIE OIDC values from existing env vars (if not explicitly set)
  : "${CIE_OIDC_CLIENT_NAME:=${APP_ENTITY_NAME}}"
  : "${CIE_OIDC_ORGANIZATION_NAME:=${APP_ENTITY_NAME}}"
  : "${CIE_OIDC_HOMEPAGE_URI:=${APP_ENTITY_URL}}"
  : "${CIE_OIDC_POLICY_URI:=${SATOSA_UI_LEGAL_URL_IT}}"
  : "${CIE_OIDC_LOGO_URI:=${SATOSA_UI_LOGO_URL}}"
  : "${CIE_OIDC_CONTACT_EMAIL:=${SATOSA_CONTACT_PERSON_EMAIL_ADDRESS}}"
  envsubst < "$REPO_ROOT/iam-proxy/cieoidc_backend.override.yaml.template" > "$PROJECT_DST/conf/backends/cieoidc_backend.yaml"
  echo "[sync-iam-proxy] Generated conf/backends/cieoidc_backend.yaml with environment variables"
fi

# Build static disco.html based on .env flags
DISCO_HTML="$PROJECT_DST/static/disco.html"
DISCO_TEMPLATE="$REPO_ROOT/iam-proxy/disco.static.html.template"

if [ -f "$DISCO_TEMPLATE" ]; then
  echo "[sync-iam-proxy] Building static disco.html from template..."
  cp "$DISCO_TEMPLATE" "$DISCO_HTML"

  # Remove blocks for disabled components
  if [ "$ENABLE_SPID" != "true" ]; then
    sed -i '/SPID_BLOCK_START/,/SPID_BLOCK_END/d' "$DISCO_HTML"
  fi
  if [ "$SATOSA_USE_DEMO_SPID_IDP" != "true" ]; then
    sed -i '/SPID_DEMO_START/,/SPID_DEMO_END/d' "$DISCO_HTML"
  fi
  if [ "$ENABLE_CIE" != "true" ]; then
    sed -i '/CIE_BLOCK_START/,/CIE_BLOCK_END/d' "$DISCO_HTML"
  fi
  if [ "$ENABLE_CIE_OIDC" != "true" ]; then
    sed -i '/CIE_OIDC_BLOCK_START/,/CIE_OIDC_BLOCK_END/d' "$DISCO_HTML"
  fi
  if [ "$ENABLE_IT_WALLET" != "true" ]; then
    sed -i '/IT_WALLET_BLOCK_START/,/IT_WALLET_BLOCK_END/d' "$DISCO_HTML"
  fi
  if [ "$ENABLE_IDEM" != "true" ]; then
    sed -i '/IDEM_BLOCK_START/,/IDEM_BLOCK_END/d' "$DISCO_HTML"
  fi
  if [ "$ENABLE_EIDAS" != "true" ]; then
    sed -i '/EIDAS_BLOCK_START/,/EIDAS_BLOCK_END/d' "$DISCO_HTML"
  fi

  echo "[sync-iam-proxy] Built disco.html with enabled components only"
else
  echo "[sync-iam-proxy] WARNING: disco.static.html.template not found at $DISCO_TEMPLATE"
fi

# Generate wallets-config.json for wallets UI filtering
echo "[sync-iam-proxy] Generating wallets-config.json for wallets UI filtering..."
if [ -f "$REPO_ROOT/iam-proxy/wallets-config.json.template" ]; then
  mkdir -p "$PROJECT_DST/static/config"
  envsubst < "$REPO_ROOT/iam-proxy/wallets-config.json.template" > "$PROJECT_DST/static/config/wallets-config.json"
  echo "[sync-iam-proxy] Generated wallets-config.json with environment variables"
else
  echo "[sync-iam-proxy] WARNING: wallets-config.json.template not found at $REPO_ROOT/iam-proxy/wallets-config.json.template"
fi

# Patch proxy_conf.yaml to disable problematic backends for test environment
# Set ENABLE_CIE_OIDC=true in environment to skip this patching
if [ -f "$PROJECT_DST/proxy_conf.yaml" ] && [ "$ENABLE_CIE_OIDC" != "true" ]; then
  echo "[sync-iam-proxy] Patching proxy_conf.yaml for test environment (SPID only)..."
  echo "[sync-iam-proxy] Set ENABLE_CIE_OIDC=true to enable CIE OIDC backend"
  # Backup original if not exists
  [ ! -f "$PROJECT_DST/proxy_conf.yaml.original" ] && cp "$PROJECT_DST/proxy_conf.yaml" "$PROJECT_DST/proxy_conf.yaml.original"
  
  # Comment out CIE OIDC backend (requires proper trust chain configuration)
  sed -i 's|^  - "conf/backends/cieoidc_backend.yaml"|  # - "conf/backends/cieoidc_backend.yaml"  # Disabled (set ENABLE_CIE_OIDC=true to enable)|' "$PROJECT_DST/proxy_conf.yaml"
  
  # Comment out pyeudiw backend (also requires trust chain)
  sed -i 's|^  - "conf/backends/pyeudiw_backend.yaml"|  # - "conf/backends/pyeudiw_backend.yaml"  # Disabled, requires trust chain config|' "$PROJECT_DST/proxy_conf.yaml"
  
  # Comment out generic saml2 backend if present (we only need spidSaml2 for SPID)
  sed -i 's|^  - "conf/backends/saml2_backend.yaml"|  # - "conf/backends/saml2_backend.yaml"  # Disabled, using spidSaml2 only|' "$PROJECT_DST/proxy_conf.yaml"
fi

# Patch target_based_routing.yaml to add demo.spid.gov.it and fix default backend
if [ -f "$PROJECT_DST/conf/microservices/target_based_routing.yaml" ] && [ "$ENABLE_CIE_OIDC" != "true" ]; then
  echo "[sync-iam-proxy] Patching target_based_routing.yaml for test environment..."
  ROUTING_FILE="$PROJECT_DST/conf/microservices/target_based_routing.yaml"
  
  # Backup original if not exists
  [ ! -f "$ROUTING_FILE.original" ] && cp "$ROUTING_FILE" "$ROUTING_FILE.original"
  
  # Change default_backend from Saml2 to spidSaml2
  sed -i 's|^  default_backend: Saml2$|  default_backend: spidSaml2|' "$ROUTING_FILE"
  
  # Add demo.spid.gov.it mapping if not present
  if ! grep -q "demo.spid.gov.it" "$ROUTING_FILE"; then
    # Insert after "https://localhost:8443": "spidSaml2" line
    sed -i '/"https:\/\/localhost:8443": "spidSaml2"/a\    "https://demo.spid.gov.it": "spidSaml2"' "$ROUTING_FILE"
  fi
fi

# Restore .gitkeep
if [ -n "$GITKEEP_TMP" ] && [ -f "$GITKEEP_TMP" ]; then
  cp "$GITKEEP_TMP" "$PROJECT_DST/.gitkeep"
  rm -f "$GITKEEP_TMP"
fi

# Generate dev certs if script exists and certs don't exist
if [ -f "$PROJECT_DST/pki/generate-dev-certs.sh" ]; then
  if [ ! -f "$PROJECT_DST/pki/cert.pem" ] || [ ! -f "$PROJECT_DST/pki/privkey.pem" ]; then
    echo "[sync-iam-proxy] Generating development certificates..."
    (cd "$PROJECT_DST/pki" && bash generate-dev-certs.sh) || {
      echo "[sync-iam-proxy] generate-dev-certs.sh failed, creating minimal self-signed cert..."
      openssl req -x509 -newkey rsa:2048 -keyout "$PROJECT_DST/pki/privkey.pem" \
        -out "$PROJECT_DST/pki/cert.pem" -days 365 -nodes \
        -subj "/CN=satosa-dev/O=Development/C=IT" 2>/dev/null || true
    }
  else
    echo "[sync-iam-proxy] Development certificates already exist"
  fi
else
  # No script, create minimal certs if missing
  if [ ! -f "$PROJECT_DST/pki/cert.pem" ] || [ ! -f "$PROJECT_DST/pki/privkey.pem" ]; then
    echo "[sync-iam-proxy] Creating minimal self-signed certificates..."
    mkdir -p "$PROJECT_DST/pki"
    openssl req -x509 -newkey rsa:2048 -keyout "$PROJECT_DST/pki/privkey.pem" \
      -out "$PROJECT_DST/pki/cert.pem" -days 365 -nodes \
      -subj "/CN=satosa-dev/O=Development/C=IT" 2>/dev/null || true
  fi
fi

# Create required directories if they don't exist
mkdir -p "$PROJECT_DST/logs"
mkdir -p "$PROJECT_DST/metadata/idp"
mkdir -p "$PROJECT_DST/metadata/sp"

# Download demo SPID IdP metadata if SATOSA_USE_DEMO_SPID_IDP=true
if is_true "$SATOSA_USE_DEMO_SPID_IDP"; then
  DEMO_METADATA_URL="https://demo.spid.gov.it/metadata.xml"
  DEMO_METADATA_FILE="$PROJECT_DST/metadata/idp/demo-spid.xml"
  
  if [ ! -f "$DEMO_METADATA_FILE" ] || [ "$FORCE_SYNC" = "true" ]; then
    echo "[sync-iam-proxy] Downloading demo SPID IdP metadata from $DEMO_METADATA_URL..."
    curl -sSL --max-time 30 "$DEMO_METADATA_URL" -o "$DEMO_METADATA_FILE" 2>/dev/null && \
      echo "[sync-iam-proxy] Demo SPID IdP metadata downloaded successfully" || {
      echo "[sync-iam-proxy] WARNING: Failed to download demo SPID IdP metadata"
      echo "[sync-iam-proxy] You may need to manually add demo.spid.gov.it metadata to $PROJECT_DST/metadata/idp/"
    }
  else
    echo "[sync-iam-proxy] Demo SPID IdP metadata already exists"
  fi
  
  # Apply demo SPID wallets override
  echo "[sync-iam-proxy] Applying demo SPID wallets override..."
  if [ -f "$REPO_ROOT/iam-proxy/wallets-spid-demo-override.json.template" ]; then
    mkdir -p "$PROJECT_DST/static/config"
    # Generate the override file with environment variables
    envsubst < "$REPO_ROOT/iam-proxy/wallets-spid-demo-override.json.template" > "$PROJECT_DST/static/config/wallets-spid-demo-override.json"
    echo "[sync-iam-proxy] Generated wallets-spid-demo-override.json"
  fi
else
  echo "[sync-iam-proxy] Skipping demo SPID IdP metadata (SATOSA_USE_DEMO_SPID_IDP not set to 'true')"
fi

# Build static disco.html based on .env flags
DISCO_HTML="$PROJECT_DST/static/disco.html"
DISCO_TEMPLATE="$REPO_ROOT/iam-proxy/disco.static.html.template"

if [ -f "$DISCO_TEMPLATE" ]; then
  echo "[sync-iam-proxy] Building static disco.html from template..."
  cp "$DISCO_TEMPLATE" "$DISCO_HTML"

  if ! is_true "$ENABLE_SPID"; then
    sed -i '/SPID_BLOCK_START/,/SPID_BLOCK_END/d' "$DISCO_HTML"
  fi
  if ! is_true "$SATOSA_USE_DEMO_SPID_IDP"; then
    sed -i '/SPID_DEMO_START/,/SPID_DEMO_END/d' "$DISCO_HTML"
  fi
  if ! is_true "$ENABLE_CIE"; then
    sed -i '/CIE_BLOCK_START/,/CIE_BLOCK_END/d' "$DISCO_HTML"
  fi
  if ! is_true "$ENABLE_CIE_OIDC"; then
    sed -i '/CIE_OIDC_BLOCK_START/,/CIE_OIDC_BLOCK_END/d' "$DISCO_HTML"
  fi
  if ! is_true "$ENABLE_IT_WALLET"; then
    sed -i '/IT_WALLET_BLOCK_START/,/IT_WALLET_BLOCK_END/d' "$DISCO_HTML"
  fi
  if ! is_true "$ENABLE_IDEM"; then
    sed -i '/IDEM_BLOCK_START/,/IDEM_BLOCK_END/d' "$DISCO_HTML"
  fi
  if ! is_true "$ENABLE_EIDAS"; then
    sed -i '/EIDAS_BLOCK_START/,/EIDAS_BLOCK_END/d' "$DISCO_HTML"
  fi

  echo "[sync-iam-proxy] Built disco.html with enabled components only"
else
  echo "[sync-iam-proxy] WARNING: disco.static.html.template not found at $DISCO_TEMPLATE"
fi

echo "[sync-iam-proxy] Skipping wallets.js patch (use upstream defaults)"

# Set permissions for directories that SATOSA needs to write to
# SATOSA container runs as user satosa (UID 100, GID 101)
# Make logs writable by all (container user needs write access)
chmod -R 777 "$PROJECT_DST/logs" 2>/dev/null || true
chmod -R 777 "$PROJECT_DST/metadata" 2>/dev/null || true
chmod -R 755 "$PROJECT_DST/pki" 2>/dev/null || true

echo "[sync-iam-proxy] Sync complete. Files are now up-to-date with upstream."
echo "[sync-iam-proxy] You can now run: docker compose --profile iam-proxy up -d"
