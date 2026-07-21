#!/bin/sh
set -eu

TARGET_CONFIG="/etc/nginx/conf.d/default.conf"
TEMPLATE_DIR="/opt/physistrong-nginx/templates"

export SERVER_NAME="${SERVER_NAME:-_}"
# Deployments with real certificates override these names via the environment;
# the defaults match what init.sh and generate-certs.sh produce.
export TLS_CERT_FILE="${TLS_CERT_FILE:-dev.crt}"
export TLS_KEY_FILE="${TLS_KEY_FILE:-dev.key}"

CERT_FILE="/etc/nginx/certs/$TLS_CERT_FILE"
KEY_FILE="/etc/nginx/certs/$TLS_KEY_FILE"

if [ -f "$CERT_FILE" ] && [ -f "$KEY_FILE" ]; then
  envsubst '${SERVER_NAME} ${TLS_CERT_FILE} ${TLS_KEY_FILE}' <"$TEMPLATE_DIR/https.conf" >"$TARGET_CONFIG"
  echo "Using HTTPS Nginx configuration (server_name $SERVER_NAME, cert $TLS_CERT_FILE)."
else
  envsubst '${SERVER_NAME}' <"$TEMPLATE_DIR/http.conf" >"$TARGET_CONFIG"
  echo "TLS certificates not found. Using HTTP-only Nginx configuration (server_name $SERVER_NAME)."
fi

exec nginx -g "daemon off;"
