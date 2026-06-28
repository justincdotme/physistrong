#!/usr/bin/env bash
# One-command bring-up: self-signed TLS, environment, dependencies, frontend
# assets, and the full stack with the database migrated. Safe to re-run.
#
#   ./init.sh physistrong.justinc.srv
set -euo pipefail

cd "$(dirname "$0")"

DOMAIN="${1:-${DOMAIN:-}}"
if [ -z "$DOMAIN" ]; then
  if [ -t 0 ]; then
    printf "Domain to serve over HTTPS [localhost]: "
    read -r DOMAIN
  fi
  DOMAIN="${DOMAIN:-localhost}"
fi

COMPOSE="docker compose -f docker-compose.yml"

set_env() {
  local key="$1" value="$2"
  if grep -qE "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    printf '%s=%s\n' "$key" "$value" >>.env
  fi
}

echo "==> TLS certificate"
if [ -f docker/nginx/certs/dev.crt ]; then
  echo "    present, skipping (delete docker/nginx/certs to renew)"
else
  docker/nginx/generate-certs.sh "$DOMAIN"
fi

echo "==> Environment"
if [ ! -f .env ]; then
  cp .env.example .env
  echo "    created .env from .env.example"
fi
set_env APP_URL "https://$DOMAIN"
set_env SESSION_DOMAIN "$DOMAIN"
set_env SESSION_SECURE_COOKIE "true"
set_env CORS_ALLOWED_ORIGINS "https://$DOMAIN"
set_env NGINX_SERVER_NAME "$DOMAIN"
echo "    pinned HTTPS settings for $DOMAIN"

echo "==> Git identity"
git_user=$(git config user.name 2>/dev/null || true)
git_email=$(git config user.email 2>/dev/null || true)
if [ -n "$git_user" ] && [ -z "$(git config --local user.name 2>/dev/null || true)" ]; then
  git config --local user.name "$git_user"
  echo "    copied user.name to repo-local config"
fi
if [ -n "$git_email" ] && [ -z "$(git config --local user.email 2>/dev/null || true)" ]; then
  git config --local user.email "$git_email"
  echo "    copied user.email to repo-local config"
fi

echo "==> Building images"
$COMPOSE build

echo "==> Installing PHP dependencies"
$COMPOSE run --rm --no-deps app composer install

if ! grep -qE '^APP_KEY=base64:' .env; then
  echo "==> Generating APP_KEY"
  $COMPOSE run --rm --no-deps app php artisan key:generate
fi

echo "==> Building frontend assets"
docker run --rm \
  --user "$(id -u):$(id -g)" \
  -e HOME=/tmp \
  -v "$PWD":/var/www/html \
  -w /var/www/html \
  node:22-alpine sh -c "npm install && npm run build"

echo "==> Starting the stack"
$COMPOSE up -d
$COMPOSE restart nginx

echo
echo "Physistrong is starting at https://$DOMAIN"
echo "The certificate is self-signed; accept the browser warning or trust"
echo "docker/nginx/certs/dev.crt on your clients."
