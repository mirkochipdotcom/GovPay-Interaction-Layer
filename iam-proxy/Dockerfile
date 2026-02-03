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

# Set the entrypoint to start SATOSA
ENTRYPOINT ["/satosa_proxy/entrypoint.sh"]
