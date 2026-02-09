#!/usr/bin/env bash
set -euo pipefail

# Remove tenant frontend service and files.
# Required env:
#   TENANT_KEY
# Optional:
#   BASE_DIR (default /var/www/tastypanel-sites)

TENANT_KEY="${TENANT_KEY:?TENANT_KEY required}"
BASE_DIR="${BASE_DIR:-/var/www/tastypanel-sites}"

TARGET_ROOT="${BASE_DIR}/${TENANT_KEY}"
TARGET_DIR="${TARGET_ROOT}/frontend"
SERVICE_NAME="tastypanel-${TENANT_KEY}-frontend"
UNIT_FILE="/etc/systemd/system/${SERVICE_NAME}.service"

if systemctl list-unit-files | grep -q "^${SERVICE_NAME}\\.service"; then
  systemctl stop "${SERVICE_NAME}" || true
  systemctl disable "${SERVICE_NAME}" || true
fi

if [[ -f "${UNIT_FILE}" ]]; then
  rm -f "${UNIT_FILE}"
  systemctl daemon-reload
  systemctl reset-failed || true
fi

if [[ -d "${TARGET_DIR}" ]]; then
  rm -rf "${TARGET_DIR}"
fi

echo "Frontend deprovisioned for ${TENANT_KEY}"
