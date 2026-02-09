#!/usr/bin/env bash
set -euo pipefail

SITE_KEY="${1:-}"
ROOT_DIR="${2:-}"
DB_NAME="${3:-}"
DB_USER="${4:-}"
PHP_VERSION="${5:-8.3}"
SYSTEM_USER="${6:-}"

usage() {
  echo "Usage: deprovision-instance.sh <site_key> <root_dir> <db_name> <db_user> [php_version] [system_user]"
}

if [[ -z "${SITE_KEY}" ]]; then
  usage
  exit 1
fi

FPM_POOL="/etc/php/${PHP_VERSION}/fpm/pool.d/${SITE_KEY}.conf"
FRONTEND_SERVICE="tastypanel-${SITE_KEY}-frontend"
FRONTEND_UNIT="/etc/systemd/system/${FRONTEND_SERVICE}.service"

echo "==> Deprovisioning instance ${SITE_KEY}"

if systemctl list-unit-files 2>/dev/null | grep -q "^${FRONTEND_SERVICE}\\.service"; then
  systemctl stop "${FRONTEND_SERVICE}" || true
  systemctl disable "${FRONTEND_SERVICE}" || true
fi

if [[ -f "${FRONTEND_UNIT}" ]]; then
  rm -f "${FRONTEND_UNIT}"
  systemctl daemon-reload || true
fi

if [[ -f "${FPM_POOL}" ]]; then
  rm -f "${FPM_POOL}"
  systemctl restart "php${PHP_VERSION}-fpm" || service "php${PHP_VERSION}-fpm" restart || true
fi

if [[ -n "${DB_NAME}" ]]; then
  mysql -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;" || true
fi

if [[ -n "${DB_USER}" ]]; then
  mysql -e "DROP USER IF EXISTS '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;" || true
fi

if [[ -n "${ROOT_DIR}" && -d "${ROOT_DIR}" ]]; then
  rm -rf "${ROOT_DIR}"
fi

if [[ -n "${SYSTEM_USER}" ]] && id -u "${SYSTEM_USER}" >/dev/null 2>&1; then
  userdel -r "${SYSTEM_USER}" >/dev/null 2>&1 || userdel "${SYSTEM_USER}" >/dev/null 2>&1 || true
fi

echo "==> Instance deprovisioned"

