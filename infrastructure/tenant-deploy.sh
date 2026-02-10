#!/usr/bin/env bash
set -euo pipefail

MODE="${1:-}"
TENANT_ROOT="${2:-}"
OWNER_USER="${3:-}"

ALLOWED_ROOT="${TENANT_INSTANCES_ROOT:-/var/www/tastypanel-sites}"

usage() {
  echo "Usage: tenant-deploy.sh <full|git_pull|composer_install> <tenant_root> [owner_user]"
}

if [[ -z "${MODE}" || -z "${TENANT_ROOT}" ]]; then
  usage
  exit 1
fi

if [[ "${MODE}" != "full" && "${MODE}" != "git_pull" && "${MODE}" != "composer_install" ]]; then
  usage
  exit 1
fi

TENANT_ROOT_REAL="$(realpath -m "${TENANT_ROOT}")"
ALLOWED_ROOT_REAL="$(realpath -m "${ALLOWED_ROOT}")"
if [[ "${TENANT_ROOT_REAL}" != "${ALLOWED_ROOT_REAL}" && "${TENANT_ROOT_REAL}" != "${ALLOWED_ROOT_REAL}"/* ]]; then
  echo "Tenant root is outside allowed root"
  exit 1
fi

if [[ ! -d "${TENANT_ROOT_REAL}" ]]; then
  echo "Root dir not found: ${TENANT_ROOT_REAL}"
  exit 1
fi

cd "${TENANT_ROOT_REAL}"

RUN_AS_USER=""
if [[ -n "${OWNER_USER}" ]] && id -u "${OWNER_USER}" >/dev/null 2>&1; then
  RUN_AS_USER="${OWNER_USER}"
fi

run_as() {
  if [[ -z "${RUN_AS_USER}" ]]; then
    "$@"
    return
  fi

  if command -v runuser >/dev/null 2>&1; then
    runuser -u "${RUN_AS_USER}" -- "$@"
  else
    sudo -u "${RUN_AS_USER}" -- "$@"
  fi
}

git_pull() {
  if [[ ! -d ".git" ]]; then
    echo "Missing .git directory in ${TENANT_ROOT_REAL}"
    return 1
  fi

  # Keep it conservative: fast-forward only.
  run_as git fetch --all --prune
  run_as git pull --ff-only
}

composer_install() {
  if [[ ! -f "composer.json" ]]; then
    echo "composer.json not found in ${TENANT_ROOT_REAL}"
    return 1
  fi

  if ! command -v composer >/dev/null 2>&1; then
    echo "composer binary not found on this host"
    return 1
  fi

  run_as composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
}

artisan() {
  if [[ ! -f artisan ]]; then
    # Not a Laravel app. Skip artisan steps.
    return 0
  fi

  run_as php artisan "$@"
}

echo "MODE=${MODE}"
echo "TENANT_ROOT=${TENANT_ROOT_REAL}"
echo "RUN_AS=${RUN_AS_USER:-<current>}"

case "${MODE}" in
  git_pull)
    git_pull
    ;;
  composer_install)
    composer_install
    ;;
  full)
    artisan down --message="Deploying" || true

    git_pull

    if [[ -f "composer.json" ]]; then
      composer_install
    fi

    if [[ -f artisan ]]; then
      artisan migrate --force
      artisan optimize:clear
      artisan up || true
    fi
    ;;
esac

echo "SUCCESS=true"

