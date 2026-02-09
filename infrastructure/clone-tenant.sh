#!/usr/bin/env bash
set -euo pipefail

SRC_ROOT="${1:-}"
DST_ROOT="${2:-}"
SRC_DB="${3:-}"
SRC_USER="${4:-}"
SRC_PASS="${5:-}"
DST_DB="${6:-}"
DST_USER="${7:-}"
DST_PASS="${8:-}"
NEW_HOST="${9:-}"
NEW_NAME="${10:-}"
NEW_SLUG="${11:-}"
NEW_THEME_ID="${12:-}"

usage() {
  echo "Usage: clone-tenant.sh <src_root> <dst_root> <src_db> <src_user> <src_pass> <dst_db> <dst_user> <dst_pass> [new_host] [new_name] [new_slug] [new_theme_id]"
}

if [[ -z "${SRC_ROOT}" || -z "${DST_ROOT}" || -z "${SRC_DB}" || -z "${SRC_USER}" || -z "${DST_DB}" || -z "${DST_USER}" ]]; then
  usage
  exit 1
fi

if [[ ! -d "${SRC_ROOT}" || ! -d "${DST_ROOT}" ]]; then
  echo "Source or target root missing."
  exit 1
fi

escape_sql() {
  printf "%s" "$1" | sed "s/'/''/g"
}

echo "==> Dumping source database"
TMP_SQL="/tmp/tastypanel_clone_${SRC_DB}_$(date +%s).sql"
MYSQL_PWD="${SRC_PASS}" mysqldump --single-transaction -u "${SRC_USER}" "${SRC_DB}" > "${TMP_SQL}"

echo "==> Importing into target database"
MYSQL_PWD="${DST_PASS}" mysql -u "${DST_USER}" "${DST_DB}" < "${TMP_SQL}"
rm -f "${TMP_SQL}"

echo "==> Updating domain + tenant metadata"
if [[ -n "${NEW_HOST}" ]]; then
  HOST_ESC="$(escape_sql "${NEW_HOST}")"
  MYSQL_PWD="${DST_PASS}" mysql -u "${DST_USER}" "${DST_DB}" -e "UPDATE domains SET hostname='${HOST_ESC}', is_primary=1, status='pending', cf_zone_id=NULL, cf_record_id=NULL;"
fi
if [[ -n "${NEW_NAME}" ]]; then
  NAME_ESC="$(escape_sql "${NEW_NAME}")"
  MYSQL_PWD="${DST_PASS}" mysql -u "${DST_USER}" "${DST_DB}" -e "UPDATE tenants SET name='${NAME_ESC}';"
fi
if [[ -n "${NEW_SLUG}" ]]; then
  SLUG_ESC="$(escape_sql "${NEW_SLUG}")"
  MYSQL_PWD="${DST_PASS}" mysql -u "${DST_USER}" "${DST_DB}" -e "UPDATE tenants SET slug='${SLUG_ESC}';"
fi
if [[ -n "${NEW_THEME_ID}" ]]; then
  MYSQL_PWD="${DST_PASS}" mysql -u "${DST_USER}" "${DST_DB}" -e "UPDATE tenants SET theme_id=${NEW_THEME_ID};"
fi

copy_dir() {
  local src="$1"
  local dst="$2"
  if [[ -d "${src}" ]]; then
    mkdir -p "${dst}"
    rsync -a "${src}/" "${dst}/"
  fi
}

echo "==> Copying storage assets"
copy_dir "${SRC_ROOT}/storage/app" "${DST_ROOT}/storage/app"
copy_dir "${SRC_ROOT}/public/uploads" "${DST_ROOT}/public/uploads"
copy_dir "${SRC_ROOT}/public/media" "${DST_ROOT}/public/media"

echo "==> Fixing permissions"
chown -R www-data:www-data "${DST_ROOT}/storage" "${DST_ROOT}/public" || true

echo "==> Clone completed"
