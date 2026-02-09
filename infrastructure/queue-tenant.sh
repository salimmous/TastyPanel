#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-}"
ROOT_DIR="${2:-}"

usage() {
  echo "Usage: queue-tenant.sh <restart|flush|retry> <root_dir>"
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

case "${ACTION}" in
  restart)
    php artisan queue:restart
    ;;
  flush)
    php artisan queue:flush
    ;;
  retry)
    php artisan queue:retry all
    ;;
  *)
    usage
    exit 1
    ;;
esac

echo "Queue action completed: ${ACTION}"
