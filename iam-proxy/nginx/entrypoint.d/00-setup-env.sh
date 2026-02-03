#!/bin/sh
# Substitute environment variables in nginx config files
# This runs before 10-docker-entrypoint.sh from the official nginx image

set -e

NGINX_HOST="${NGINX_HOST:-satosa-nginx}"

# Replace $NGINX_HOST in all config files
find /etc/nginx/conf.d -name "*.conf" -type f -exec sed -i "s|\$NGINX_HOST|${NGINX_HOST}|g" {} \;

echo "[nginx-setup] Substituted NGINX_HOST=${NGINX_HOST} in config files"
