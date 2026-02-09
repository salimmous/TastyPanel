#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="${1:-}"
DB_NAME="${2:-}"
DB_USER="${3:-}"
DB_PASS="${4:-}"
OUT_DIR="${5:-}"

usage() {
  echo "Usage: backup-tenant.sh <root_dir> <db_name> <db_user> <db_pass> <out_dir>"
}

if [[ -z "${ROOT_DIR}" || -z "${DB_NAME}" || -z "${DB_USER}" || -z "${OUT_DIR}" ]]; then
  usage
  exit 1
fi

if [[ ! -d "${ROOT_DIR}" ]]; then
  echo "Root dir not found: ${ROOT_DIR}"
  exit 1
fi

mkdir -p "${OUT_DIR}"

DB_PATH="${OUT_DIR}/database.sql"
FILES_PATH="${OUT_DIR}/files.tar.gz"
ZIP_PATH="${OUT_DIR}/backup.zip"

echo "==> Dumping database ${DB_NAME}"
MYSQL_PWD="${DB_PASS}" mysqldump --single-transaction -u "${DB_USER}" "${DB_NAME}" > "${DB_PATH}"

echo "==> Archiving files"
dirs=()
[[ -d "${ROOT_DIR}/storage/app" ]] && dirs+=("storage/app")
[[ -d "${ROOT_DIR}/public/uploads" ]] && dirs+=("public/uploads")
[[ -d "${ROOT_DIR}/public/media" ]] && dirs+=("public/media")

if [[ "${#dirs[@]}" -gt 0 ]]; then
  tar -czf "${FILES_PATH}" -C "${ROOT_DIR}" "${dirs[@]}"
else
  tar -czf "${FILES_PATH}" -C "${ROOT_DIR}" --files-from /dev/null
fi

echo "==> Creating zip"
if [[ -f "${ZIP_PATH}" ]]; then
  rm -f "${ZIP_PATH}"
fi
zip -j "${ZIP_PATH}" "${DB_PATH}" "${FILES_PATH}" >/dev/null

echo "Backup complete: ${ZIP_PATH}"
