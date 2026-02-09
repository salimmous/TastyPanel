#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-}"
TENANT_KEY="${TENANT_KEY:-}"
MAILBOX_EMAIL="${MAILBOX_EMAIL:-}"
MAILBOX_ROOT="${MAILBOX_ROOT:-/var/mail/tastypanel}"
MAILBOX_USERS_FILE="${MAILBOX_USERS_FILE:-/etc/dovecot/tastypanel-users}"
MAILBOX_QUOTA_MB="${MAILBOX_QUOTA_MB:-1024}"
MAILBOX_PASSWORD="${MAILBOX_PASSWORD:-}"
MAILBOX_OS_USER="${MAILBOX_OS_USER:-vmail}"
MAILBOX_OS_GROUP="${MAILBOX_OS_GROUP:-vmail}"

usage() {
  echo "Usage: manage-tenant-mailbox.sh <create|reset-password|delete|usage>"
}

if [[ -z "${ACTION}" ]]; then
  usage
  exit 1
fi

if [[ "${ACTION}" != "usage" && "${ACTION}" != "delete" && -z "${MAILBOX_PASSWORD}" ]]; then
  MAILBOX_PASSWORD="$(openssl rand -base64 18 | tr -d '\n')"
fi

validate_email() {
  if [[ -z "${MAILBOX_EMAIL}" ]]; then
    echo "MAILBOX_EMAIL is required."
    exit 1
  fi
  if ! printf '%s' "${MAILBOX_EMAIL}" | grep -Eq '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'; then
    echo "Invalid mailbox email format."
    exit 1
  fi
}

email_local() {
  printf '%s' "${MAILBOX_EMAIL%%@*}"
}

email_domain() {
  printf '%s' "${MAILBOX_EMAIL##*@}"
}

mailbox_dir() {
  local local_part domain
  local_part="$(email_local)"
  domain="$(email_domain)"
  printf '%s/%s/%s/Maildir' "${MAILBOX_ROOT}" "${domain}" "${local_part}"
}

assert_inside_root() {
  local dir root_real dir_parent_real
  dir="$1"
  mkdir -p "${MAILBOX_ROOT}"
  root_real="$(cd "${MAILBOX_ROOT}" && pwd -P)"
  mkdir -p "$(dirname "${dir}")"
  dir_parent_real="$(cd "$(dirname "${dir}")" && pwd -P)"
  if [[ "${dir_parent_real}" != "${root_real}" && "${dir_parent_real}" != "${root_real}/"* ]]; then
    echo "Mailbox path is outside MAILBOX_ROOT."
    exit 1
  fi
}

ensure_users_file() {
  mkdir -p "$(dirname "${MAILBOX_USERS_FILE}")"
  touch "${MAILBOX_USERS_FILE}"
  chmod 600 "${MAILBOX_USERS_FILE}"
}

hash_password() {
  local password="$1"
  if command -v doveadm >/dev/null 2>&1; then
    doveadm pw -s SHA512-CRYPT -p "${password}"
    return
  fi
  if command -v mkpasswd >/dev/null 2>&1; then
    mkpasswd -m sha-512 "${password}"
    return
  fi
  openssl passwd -6 "${password}"
}

replace_user_entry() {
  local hash tmp line
  hash="$(hash_password "${MAILBOX_PASSWORD}")"
  tmp="$(mktemp)"
  grep -v -E "^${MAILBOX_EMAIL//./\\.}:" "${MAILBOX_USERS_FILE}" > "${tmp}" || true
  line="${MAILBOX_EMAIL}:${hash}:quota=${MAILBOX_QUOTA_MB}M"
  printf '%s\n' "${line}" >> "${tmp}"
  install -m 600 "${tmp}" "${MAILBOX_USERS_FILE}"
  rm -f "${tmp}"
}

remove_user_entry() {
  local tmp
  tmp="$(mktemp)"
  grep -v -E "^${MAILBOX_EMAIL//./\\.}:" "${MAILBOX_USERS_FILE}" > "${tmp}" || true
  install -m 600 "${tmp}" "${MAILBOX_USERS_FILE}"
  rm -f "${tmp}"
}

set_mailbox_owner() {
  local target="$1"
  if id -u "${MAILBOX_OS_USER}" >/dev/null 2>&1; then
    chown -R "${MAILBOX_OS_USER}:${MAILBOX_OS_GROUP}" "${target}" || true
  fi
}

create_mailbox() {
  validate_email
  ensure_users_file
  local dir
  dir="$(mailbox_dir)"
  assert_inside_root "${dir}"
  mkdir -p "${dir}/cur" "${dir}/new" "${dir}/tmp"
  replace_user_entry
  set_mailbox_owner "${dir}"

  echo "TENANT_KEY=${TENANT_KEY}"
  echo "MAILBOX_EMAIL=${MAILBOX_EMAIL}"
  echo "MAILBOX_PATH=${dir}"
  echo "QUOTA_MB=${MAILBOX_QUOTA_MB}"
  echo "PASSWORD=${MAILBOX_PASSWORD}"
}

reset_mailbox_password() {
  validate_email
  ensure_users_file
  replace_user_entry

  echo "TENANT_KEY=${TENANT_KEY}"
  echo "MAILBOX_EMAIL=${MAILBOX_EMAIL}"
  echo "QUOTA_MB=${MAILBOX_QUOTA_MB}"
  echo "PASSWORD=${MAILBOX_PASSWORD}"
}

delete_mailbox() {
  validate_email
  ensure_users_file
  local dir
  dir="$(mailbox_dir)"
  assert_inside_root "${dir}"
  rm -rf "${dir}"
  remove_user_entry

  echo "TENANT_KEY=${TENANT_KEY}"
  echo "MAILBOX_EMAIL=${MAILBOX_EMAIL}"
  echo "REMOVED=true"
}

mailbox_usage() {
  validate_email
  local dir bytes
  dir="$(mailbox_dir)"
  assert_inside_root "${dir}"
  if [[ ! -d "${dir}" ]]; then
    echo "Mailbox directory not found."
    exit 1
  fi

  bytes="$(du -sb "${dir}" 2>/dev/null | awk '{print $1}')"
  bytes="${bytes:-0}"

  echo "TENANT_KEY=${TENANT_KEY}"
  echo "MAILBOX_EMAIL=${MAILBOX_EMAIL}"
  echo "MAILBOX_PATH=${dir}"
  echo "USAGE_BYTES=${bytes}"
}

case "${ACTION}" in
  create)
    create_mailbox
    ;;
  reset-password)
    reset_mailbox_password
    ;;
  delete)
    delete_mailbox
    ;;
  usage)
    mailbox_usage
    ;;
  *)
    usage
    exit 1
    ;;
esac
