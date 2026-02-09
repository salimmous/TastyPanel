#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-}"
SITE_KEY="${2:-}"
ROOT_DIR="${3:-}"
PHP_VERSION="${4:-8.3}"
PHP_SOCKET="${5:-}"
FRONTEND_SERVICE="tastypanel-${SITE_KEY}-frontend"

usage() {
  echo "Usage: orchestrate-tenant.sh <start|stop|restart> <site_key> <root_dir> [php_version] [php_socket]"
}

if [[ -z "${ACTION}" || -z "${SITE_KEY}" || -z "${ROOT_DIR}" ]]; then
  usage
  exit 1
fi

if [[ ! -d "${ROOT_DIR}" ]]; then
  echo "Root dir not found: ${ROOT_DIR}"
  exit 1
fi

cd "${ROOT_DIR}"

if [[ ! -f artisan ]]; then
  echo "artisan not found in ${ROOT_DIR}"
  exit 1
fi

restart_php() {
  if systemctl list-units --full -all | grep -q "php${PHP_VERSION}-fpm.service"; then
    systemctl reload "php${PHP_VERSION}-fpm" || systemctl restart "php${PHP_VERSION}-fpm"
  else
    service "php${PHP_VERSION}-fpm" restart || true
  fi
}

manage_frontend() {
  local op="$1"
  if systemctl list-unit-files | grep -q "^${FRONTEND_SERVICE}\\.service"; then
    case "${op}" in
      start) systemctl restart "${FRONTEND_SERVICE}" ;;
      stop) systemctl stop "${FRONTEND_SERVICE}" || true ;;
      restart) systemctl restart "${FRONTEND_SERVICE}" ;;
    esac
    echo "Frontend service ${FRONTEND_SERVICE}: ${op}"
  fi
}

case "${ACTION}" in
  start)
    php artisan up || true
    restart_php
    manage_frontend start
    ;;
  stop)
    php artisan down --message="Maintenance mode" || true
    manage_frontend stop
    ;;
  restart)
    php artisan down --message="Restarting" || true
    sleep 1
    php artisan up || true
    restart_php
    manage_frontend restart
    ;;
  *)
    usage
    exit 1
    ;;
esac

if [[ -n "${PHP_SOCKET}" ]]; then
  if [[ -S "${PHP_SOCKET}" ]]; then
    echo "PHP socket OK: ${PHP_SOCKET}"
  else
    echo "PHP socket missing: ${PHP_SOCKET}"
  fi
fi

echo "Done: ${ACTION} ${SITE_KEY}"
