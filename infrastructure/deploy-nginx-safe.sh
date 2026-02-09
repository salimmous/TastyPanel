#!/usr/bin/env bash
set -euo pipefail

MODE="${1:-deploy}"
REQUESTED_BACKUP_PATH="${2:-}"

AVAILABLE_DIR="${NGINX_AVAILABLE_DIR:-/etc/nginx/sites-available}"
ENABLED_DIR="${NGINX_ENABLED_DIR:-/etc/nginx/sites-enabled}"
BACKUP_ROOT="${NGINX_DEPLOY_BACKUP_ROOT:-/var/backups/tastypanel-nginx}"

usage() {
  echo "Usage: deploy-nginx-safe.sh <deploy|rollback> [backup_path]"
}

if [[ "${MODE}" != "deploy" && "${MODE}" != "rollback" ]]; then
  usage
  exit 1
fi

install -d "${AVAILABLE_DIR}" "${ENABLED_DIR}" "${BACKUP_ROOT}"

create_backup() {
  local stamp backup_dir
  stamp="$(date +%Y%m%d-%H%M%S)"
  backup_dir="${BACKUP_ROOT}/${stamp}"
  install -d "${backup_dir}/available" "${backup_dir}/enabled"
  cp -a "${AVAILABLE_DIR}/." "${backup_dir}/available/" 2>/dev/null || true
  cp -a "${ENABLED_DIR}/." "${backup_dir}/enabled/" 2>/dev/null || true
  echo "${backup_dir}"
}

latest_backup() {
  ls -1dt "${BACKUP_ROOT}"/* 2>/dev/null | head -n 1
}

restore_backup() {
  local backup_dir="$1"
  if [[ ! -d "${backup_dir}/available" || ! -d "${backup_dir}/enabled" ]]; then
    echo "Invalid backup path: ${backup_dir}"
    return 1
  fi

  find "${AVAILABLE_DIR}" -mindepth 1 -maxdepth 1 -exec rm -rf {} +
  find "${ENABLED_DIR}" -mindepth 1 -maxdepth 1 -exec rm -rf {} +

  cp -a "${backup_dir}/available/." "${AVAILABLE_DIR}/" 2>/dev/null || true
  cp -a "${backup_dir}/enabled/." "${ENABLED_DIR}/" 2>/dev/null || true
}

if [[ "${MODE}" == "rollback" ]]; then
  BACKUP_PATH="${REQUESTED_BACKUP_PATH:-$(latest_backup)}"
  if [[ -z "${BACKUP_PATH}" ]]; then
    echo "No backup found under ${BACKUP_ROOT}"
    exit 1
  fi
  restore_backup "${BACKUP_PATH}"
  nginx -t
  systemctl reload nginx
  echo "MODE=rollback"
  echo "SUCCESS=true"
  echo "BACKUP_PATH=${BACKUP_PATH}"
  exit 0
fi

BACKUP_PATH="$(create_backup)"

if ! nginx -t; then
  echo "MODE=deploy"
  echo "SUCCESS=false"
  echo "STEP=nginx-test"
  echo "BACKUP_PATH=${BACKUP_PATH}"
  exit 1
fi

if ! systemctl reload nginx; then
  echo "nginx reload failed, restoring previous config..."
  restore_backup "${BACKUP_PATH}"
  nginx -t || true
  systemctl reload nginx || true
  echo "MODE=deploy"
  echo "SUCCESS=false"
  echo "STEP=reload"
  echo "ROLLBACK=true"
  echo "BACKUP_PATH=${BACKUP_PATH}"
  exit 1
fi

echo "MODE=deploy"
echo "SUCCESS=true"
echo "BACKUP_PATH=${BACKUP_PATH}"
