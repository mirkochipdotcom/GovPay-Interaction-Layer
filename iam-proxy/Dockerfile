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

# Execute the original entrypoint
exec /satosa_proxy/entrypoint.sh
EOF

RUN sed -i 's/\r$//' /satosa_proxy/docker-entrypoint.sh \
  && chmod +x /satosa_proxy/docker-entrypoint.sh

# Set the entrypoint to start SATOSA
ENTRYPOINT ["/satosa_proxy/docker-entrypoint.sh"]
