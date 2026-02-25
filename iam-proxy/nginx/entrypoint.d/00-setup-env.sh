#!/bin/sh
# Substitute environment variables in nginx config files
# This runs before 10-docker-entrypoint.sh from the official nginx image

set -e

NGINX_HOST="${NGINX_HOST:-satosa-nginx}"
SSL_ENABLED="${SSL:-on}"

echo "[nginx-setup] Initializing configuration from templates..."
# Pulizia conf.d originale e copia dei template
rm -f /etc/nginx/conf.d/default.conf
cp /satosa_config_templates/*.conf /etc/nginx/conf.d/

# Replace $NGINX_HOST in all config files
find /etc/nginx/conf.d -name "*.conf" -type f -exec sed -i "s|\$NGINX_HOST|${NGINX_HOST}|g" {} \;

if [ "$SSL_ENABLED" = "on" ]; then
    echo "[nginx-setup] SSL is enabled. Injecting SSL config..."
    find /etc/nginx/conf.d -name "*.conf" -type f -exec sed -i "s|#SSL_MARKER#| ssl|g" {} \;
    find /etc/nginx/conf.d -name "*.conf" -type f -exec sed -i "s|#CERT_MARKER_1#|ssl_certificate /etc/nginx/certs/server.crt;|g" {} \;
    find /etc/nginx/conf.d -name "*.conf" -type f -exec sed -i "s|#CERT_MARKER_2#|ssl_certificate_key /etc/nginx/certs/server.key;|g" {} \;
    find /etc/nginx/conf.d -name "*.conf" -type f -exec sed -i "s|#PROTO_MARKER#|https|g" {} \;
else
    echo "[nginx-setup] SSL is disabled. Running in clear HTTP..."
    find /etc/nginx/conf.d -name "*.conf" -type f -exec sed -i "s|#SSL_MARKER#||g" {} \;
    find /etc/nginx/conf.d -name "*.conf" -type f -exec sed -i "s|#CERT_MARKER_1#||g" {} \;
    find /etc/nginx/conf.d -name "*.conf" -type f -exec sed -i "s|#CERT_MARKER_2#||g" {} \;
    find /etc/nginx/conf.d -name "*.conf" -type f -exec sed -i "s|#PROTO_MARKER#|http|g" {} \;
fi

echo "[nginx-setup] Substituted NGINX_HOST=${NGINX_HOST} and SSL macros in config files"
