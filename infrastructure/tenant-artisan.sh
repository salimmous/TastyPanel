#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-}"
ROOT_DIR="${2:-}"
SYSTEM_USER="${3:-}"

usage() {
  echo "Usage: tenant-artisan.sh <migrate|migrate_status|optimize_clear|config_cache|route_cache|view_cache> <root_dir> [system_user]"
}

if [[ -z "${ACTION}" || -z "${ROOT_DIR}" ]]; then
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

run_cmd() {
  # Prefer running as the tenant system user when invoked as root to avoid root-owned cache files.
  if [[ -n "${SYSTEM_USER}" ]] && id -u "${SYSTEM_USER}" >/dev/null 2>&1 && [[ "$(id -u)" -eq 0 ]]; then
    if command -v runuser >/dev/null 2>&1; then
      runuser -u "${SYSTEM_USER}" -- "$@"
      return $?
    fi
    if command -v sudo >/dev/null 2>&1; then
      sudo -u "${SYSTEM_USER}" -H "$@"
      return $?
    fi
  fi

  "$@"
}

case "${ACTION}" in
  migrate)
    run_cmd php artisan migrate --force
    ;;
  migrate_status)
    run_cmd php artisan migrate:status
    ;;
  optimize_clear)
    run_cmd php artisan optimize:clear
    ;;
  config_cache)
    run_cmd php artisan config:cache
    ;;
  route_cache)
    run_cmd php artisan route:cache
    ;;
  view_cache)
    run_cmd php artisan view:cache
    ;;
  *)
    usage
    exit 1
    ;;
esac

echo "Tenant artisan action completed: ${ACTION}"
