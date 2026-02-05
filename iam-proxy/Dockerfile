FROM ghcr.io/italia/iam-proxy-italia:latest

# Download and extract iam-proxy-italia project files
RUN set -euo pipefail && \
    apk add --no-cache curl unzip bash && \
    TMP_ROOT=$(mktemp -d) && \
    curl -fsSL "https://github.com/italia/iam-proxy-italia/archive/refs/heads/master.zip" -o "${TMP_ROOT}/src.zip" && \
    unzip -q "${TMP_ROOT}/src.zip" -d "${TMP_ROOT}" && \
    SRC_DIR=$(find "${TMP_ROOT}" -maxdepth 1 -type d -name "iam-proxy-italia-*" | head -1) && \
    if [ -z "${SRC_DIR}" ]; then echo "ERROR: extracted directory not found"; exit 1; fi && \
    cp -r "${SRC_DIR}/iam-proxy-italia-project/." /satosa_proxy/ && \
    if [ -f /satosa_proxy/pki/build_spid_certs.sh ]; then bash /satosa_proxy/pki/build_spid_certs.sh || true; fi && \
    rm -rf "${TMP_ROOT}" && \
    apk del --no-cache curl unzip

# Generate self-signed dev certificates if they don't exist
RUN apk add --no-cache openssl && \
    mkdir -p /satosa_proxy/pki && \
    if [ ! -f /satosa_proxy/pki/privkey.pem ]; then \
      openssl req -x509 -newkey rsa:2048 -keyout /satosa_proxy/pki/privkey.pem -out /satosa_proxy/pki/cert.pem \
        -days 365 -nodes -subj "/CN=satosa" && \
      chmod 644 /satosa_proxy/pki/privkey.pem /satosa_proxy/pki/cert.pem; \
    fi && \
    apk del --no-cache openssl
# Verify entrypoint exists
RUN test -f /satosa_proxy/entrypoint.sh || (echo "ERROR: /satosa_proxy/entrypoint.sh not found after setup" && exit 1)

# Ensure logs directory is writable
RUN mkdir -p /satosa_proxy/logs && chmod 777 /satosa_proxy/logs

# Entrypoint script to handle SATOSA backend configuration
RUN cat > /satosa_proxy/docker-entrypoint.sh << 'EOF'
#!/bin/sh
set -e

echo "[SATOSA Init] Starting initialization..."

# Inject frontoffice SP entityID and IAM Proxy domain mapping into routing override if placeholder present
TARGET_ROUTING_FILE="/satosa_proxy/conf/microservices/target_based_routing.yaml"
RUNTIME_ROUTING_FILE="/satosa_proxy/runtime/target_based_routing.yaml"

if [ -f "$TARGET_ROUTING_FILE" ]; then
  echo "[SATOSA Init] Processing target_based_routing configuration..."
  mkdir -p /satosa_proxy/runtime
  cp "$TARGET_ROUTING_FILE" "$RUNTIME_ROUTING_FILE"
  
  # Extract and prepare variables for substitution
  BASE_URL="${FRONTOFFICE_PUBLIC_BASE_URL:-}"
  if [ -n "$BASE_URL" ]; then
    BASE_URL="${BASE_URL%/}"
    FRONTOFFICE_SAML_SP_ENTITY_ID="${FRONTOFFICE_SAML_SP_ENTITY_ID:-${BASE_URL}/saml/sp}"
  else
    FRONTOFFICE_SAML_SP_ENTITY_ID="${FRONTOFFICE_SAML_SP_ENTITY_ID:-}"
  fi
  
  # Extract domain from IAM_PROXY_PUBLIC_BASE_URL for local IdP mapping
  IAM_PROXY_BASE_URL="${SATOSA_BASE:-${IAM_PROXY_PUBLIC_BASE_URL:-https://localhost:9445}}"
  IAM_PROXY_DOMAIN=$(echo "$IAM_PROXY_BASE_URL" | sed 's|https://||g;s|http://||g;s|:[0-9]*||g;s|/.*||g')
  
  if [ -n "$IAM_PROXY_DOMAIN" ]; then
    IAM_PROXY_DOMAIN_MAPPING="https://${IAM_PROXY_DOMAIN}/Saml2/metadata"
    echo "[SATOSA Init] IAM Proxy domain: $IAM_PROXY_DOMAIN -> mapping to Saml2 backend"
  fi
  
  # Use envsubst for safer variable substitution (handles special chars better)
  # First, replace the frontoffice SP entity ID
  if [ -n "$FRONTOFFICE_SAML_SP_ENTITY_ID" ]; then
    # Escape quotes in the entity ID for sed
    ESCAPED_SP_ENTITY_ID=$(printf '%s\n' "$FRONTOFFICE_SAML_SP_ENTITY_ID" | sed -e 's/[\/&]/\\&/g')
    sed -i "s|__FRONTOFFICE_SAML_SP_ENTITY_ID__|${ESCAPED_SP_ENTITY_ID}|g" "$RUNTIME_ROUTING_FILE"
    echo "[SATOSA Init] Substituted FRONTOFFICE_SAML_SP_ENTITY_ID: $FRONTOFFICE_SAML_SP_ENTITY_ID"
  fi
  
  # Then replace the IAM Proxy domain mapping
  if [ -n "$IAM_PROXY_DOMAIN_MAPPING" ]; then
    # Escape the mapping URL for sed
    ESCAPED_IAM_MAPPING=$(printf '%s\n' "$IAM_PROXY_DOMAIN_MAPPING" | sed -e 's/[\/&]/\\&/g')
    sed -i "s|__IAM_PROXY_DOMAIN_MAPPING__|${ESCAPED_IAM_MAPPING}|g" "$RUNTIME_ROUTING_FILE"
    echo "[SATOSA Init] Substituted IAM_PROXY_DOMAIN_MAPPING: $IAM_PROXY_DOMAIN_MAPPING"
  fi
  
  # Verify substitution was successful
  if grep -q "__FRONTOFFICE_SAML_SP_ENTITY_ID__\|__IAM_PROXY_DOMAIN_MAPPING__" "$RUNTIME_ROUTING_FILE"; then
    echo "[WARN] Some placeholders were not replaced in target_based_routing.yaml"
    grep "__" "$RUNTIME_ROUTING_FILE" || true
  else
    echo "[SATOSA Init] All placeholders replaced successfully, using runtime routing config"
    sed -i 's|conf/microservices/target_based_routing.yaml|runtime/target_based_routing.yaml|g' /satosa_proxy/proxy_conf.yaml
  fi
fi

# Disable problematic backends based on environment variables
if [ "${SATOSA_DISABLE_CIEOIDC_BACKEND:-false}" = "true" ]; then
  echo "[SATOSA Init] Disabling CIE OIDC backend..."
  sed -i '/conf\/backends\/cieoidc_backend.yaml/d' /satosa_proxy/proxy_conf.yaml
  rm -f /satosa_proxy/conf/backends/cieoidc_backend.yaml
fi

if [ "${SATOSA_DISABLE_PYEUDIW_BACKEND:-false}" = "true" ]; then
  echo "[SATOSA Init] Disabling pyeudiw backend..."
  sed -i '/conf\/backends\/pyeudiw_backend.yaml/d' /satosa_proxy/proxy_conf.yaml
  rm -f /satosa_proxy/conf/backends/pyeudiw_backend.yaml
fi

echo "[SATOSA Init] Configuration complete, starting SATOSA..."

# Execute the original entrypoint
exec /satosa_proxy/entrypoint.sh
EOF

RUN sed -i 's/\r$//' /satosa_proxy/docker-entrypoint.sh \
  && chmod +x /satosa_proxy/docker-entrypoint.sh

# Set the entrypoint to start SATOSA
ENTRYPOINT ["/satosa_proxy/docker-entrypoint.sh"]
