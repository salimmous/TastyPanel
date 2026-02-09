#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-}"
TENANT_ROOT="${2:-}"
ENV_KEY="${3:-}"
ENV_VALUE="${4:-}"
OWNER_USER="${5:-}"
ALLOWED_ROOT="${TENANT_INSTANCES_ROOT:-/var/www/tastypanel-sites}"

usage() {
  echo "Usage: sync-tenant-env.sh <upsert|remove> <tenant_root> <env_key> [env_value] [owner_user]"
}

if [[ -z "${ACTION}" || -z "${TENANT_ROOT}" || -z "${ENV_KEY}" ]]; then
  usage
  exit 1
fi

if [[ "${ACTION}" != "upsert" && "${ACTION}" != "remove" ]]; then
  usage
  exit 1
fi

if [[ ! "${ENV_KEY}" =~ ^[A-Z][A-Z0-9_]*$ ]]; then
  echo "Invalid ENV key '${ENV_KEY}'. Use uppercase letters, numbers, underscores."
  exit 1
fi

if [[ "${ACTION}" == "upsert" && "${ENV_VALUE}" == *$'\n'* ]]; then
  echo "Multiline values are not supported by sync-tenant-env.sh"
  exit 1
fi

TENANT_ROOT_REAL="$(realpath -m "${TENANT_ROOT}")"
ALLOWED_ROOT_REAL="$(realpath -m "${ALLOWED_ROOT}")"
if [[ "${TENANT_ROOT_REAL}" != "${ALLOWED_ROOT_REAL}" && "${TENANT_ROOT_REAL}" != "${ALLOWED_ROOT_REAL}"/* ]]; then
  echo "Tenant root is outside allowed root"
  exit 1
fi

if [[ ! -d "${TENANT_ROOT_REAL}" ]]; then
  echo "Tenant root not found: ${TENANT_ROOT_REAL}"
  exit 1
fi

ENV_FILE="${TENANT_ROOT_REAL}/.env"
ENV_EXAMPLE="${TENANT_ROOT_REAL}/.env.example"

if [[ ! -f "${ENV_FILE}" ]]; then
  if [[ -f "${ENV_EXAMPLE}" ]]; then
    cp "${ENV_EXAMPLE}" "${ENV_FILE}"
  else
    touch "${ENV_FILE}"
  fi
fi

PREVIOUS_OWNER="$(stat -c '%u:%g' "${ENV_FILE}" 2>/dev/null || true)"

if [[ "${ACTION}" == "upsert" ]]; then
  ESCAPED_VALUE="$(printf '%s' "${ENV_VALUE}" | sed -e 's/[\\/&]/\\&/g')"
  if grep -qE "^${ENV_KEY}=" "${ENV_FILE}"; then
    sed -i "s/^${ENV_KEY}=.*/${ENV_KEY}=${ESCAPED_VALUE}/" "${ENV_FILE}"
  else
    printf '\n%s=%s\n' "${ENV_KEY}" "${ENV_VALUE}" >> "${ENV_FILE}"
  fi
  echo "SYNC_ACTION=upsert"
  echo "ENV_FILE=${ENV_FILE}"
  echo "ENV_KEY=${ENV_KEY}"
else
  sed -i "/^${ENV_KEY}=.*/d" "${ENV_FILE}"
  echo "SYNC_ACTION=remove"
  echo "ENV_FILE=${ENV_FILE}"
  echo "ENV_KEY=${ENV_KEY}"
fi

if [[ -n "${PREVIOUS_OWNER}" ]]; then
  chown "${PREVIOUS_OWNER}" "${ENV_FILE}" || true
elif [[ -n "${OWNER_USER}" ]] && id -u "${OWNER_USER}" >/dev/null 2>&1; then
  chown "${OWNER_USER}:www-data" "${ENV_FILE}" || true
fi

chmod 640 "${ENV_FILE}" || true
