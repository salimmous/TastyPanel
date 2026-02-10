#!/usr/bin/env bash
set -euo pipefail

TENANT_ROOT="${1:-}"
ALLOWED_ROOT="${TENANT_INSTANCES_ROOT:-/var/www/tastypanel-sites}"

usage() {
  echo "Usage: tenant-env-keys.sh <tenant_root>"
}

if [[ -z "${TENANT_ROOT}" ]]; then
  usage
  exit 1
fi

TENANT_ROOT_REAL="$(realpath -m "${TENANT_ROOT}")"
ALLOWED_ROOT_REAL="$(realpath -m "${ALLOWED_ROOT}")"
if [[ "${TENANT_ROOT_REAL}" != "${ALLOWED_ROOT_REAL}" && "${TENANT_ROOT_REAL}" != "${ALLOWED_ROOT_REAL}"/* ]]; then
  echo "Tenant root is outside allowed root"
  exit 1
fi

ENV_FILE="${TENANT_ROOT_REAL}/.env"
echo "ENV_FILE=${ENV_FILE}"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "STATUS=missing"
  exit 0
fi

echo "STATUS=ok"

# Output keys only (no values). Supports optional leading "export ".
grep -E '^[[:space:]]*(export[[:space:]]+)?[A-Za-z_][A-Za-z0-9_]*=' "${ENV_FILE}" \
  | sed -E 's/^[[:space:]]*export[[:space:]]+//' \
  | sed -E 's/^[[:space:]]*([A-Za-z_][A-Za-z0-9_]*)=.*/\\1/' \
  | awk 'NF' \
  | sort -u

