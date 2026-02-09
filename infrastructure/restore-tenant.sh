#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="${1:-}"
DB_NAME="${2:-}"
DB_USER="${3:-}"
DB_PASS="${4:-}"
ZIP_PATH="${5:-}"

usage() {
  echo "Usage: restore-tenant.sh <root_dir> <db_name> <db_user> <db_pass> <backup_zip>"
}

if [[ -z "${ROOT_DIR}" || -z "${DB_NAME}" || -z "${DB_USER}" || -z "${ZIP_PATH}" ]]; then
  usage
  exit 1
fi

if [[ ! -d "${ROOT_DIR}" ]]; then
  echo "Root dir not found: ${ROOT_DIR}"
  exit 1
fi

if [[ ! -f "${ZIP_PATH}" ]]; then
  echo "Backup zip not found: ${ZIP_PATH}"
  exit 1
fi

TMP_DIR="$(mktemp -d)"
cleanup() {
  rm -rf "${TMP_DIR}"
}
trap cleanup EXIT

echo "==> Extracting backup"
unzip -q "${ZIP_PATH}" -d "${TMP_DIR}"

if [[ -f "${TMP_DIR}/database.sql" ]]; then
  echo "==> Restoring database ${DB_NAME}"
  MYSQL_PWD="${DB_PASS}" mysql -u "${DB_USER}" "${DB_NAME}" < "${TMP_DIR}/database.sql"
fi

if [[ -f "${TMP_DIR}/files.tar.gz" ]]; then
  echo "==> Restoring files"
  tar -xzf "${TMP_DIR}/files.tar.gz" -C "${ROOT_DIR}"
fi

echo "==> Fixing permissions"
chown -R www-data:www-data "${ROOT_DIR}/storage" "${ROOT_DIR}/public" || true

echo "Restore complete."
