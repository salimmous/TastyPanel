#!/bin/bash
# Bootstrap the platform database and caches using the current .env.
# Run this on the host where MySQL/Redis are reachable.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

ENV_FILE="$ROOT_DIR/.env"

if [[ ! -f "$ENV_FILE" ]]; then
  echo ".env not found at $ENV_FILE" >&2
  exit 1
fi

val() {
  local key="$1"
  grep -E "^${key}=" "$ENV_FILE" | head -n1 | cut -d= -f2- | tr -d '"'
}

DB_HOST="$(val DB_HOST)"
DB_PORT="$(val DB_PORT)"
DB_DATABASE="$(val DB_DATABASE)"
DB_USERNAME="$(val DB_USERNAME)"
DB_PASSWORD="$(val DB_PASSWORD)"

if [[ -z "$DB_HOST" || -z "$DB_PORT" || -z "$DB_DATABASE" || -z "$DB_USERNAME" ]]; then
  echo "Missing DB settings in .env (DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME)" >&2
  exit 1
fi

echo "Checking MySQL connectivity to ${DB_HOST}:${DB_PORT} as ${DB_USERNAME}..."
MYSQL_PWD="$DB_PASSWORD" mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" --protocol=TCP -w 5 || {
  echo "MySQL not reachable; aborting." >&2
  exit 1
}

echo "Running migrations..."
php artisan migrate --force

if [[ "${SEED:-0}" == "1" ]]; then
  echo "Seeding database..."
  php artisan db:seed --force
fi

echo "Linking storage..."
php artisan storage:link || true

echo "Caching config/routes/views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Done."
