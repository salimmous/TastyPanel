#!/usr/bin/env bash
# Export Laravel tenant app template (patch = theme + dashboard) for TENANT_APP_REPO.
# Usage: export-tenant-app.sh <destination_dir>  → then git init, push to GitHub, set TENANT_APP_REPO.
# See documentation/TENANT-WORKFLOW.md
set -euo pipefail

DESTINATION="${1:-}"

usage() {
  echo "Usage: export-tenant-app.sh <destination_dir>"
}

if [[ -z "${DESTINATION}" ]]; then
  usage
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

mkdir -p "${DESTINATION}"

echo "==> Exporting tenant app template to ${DESTINATION}"
tar --exclude="./.git" \
    --exclude="./node_modules" \
    --exclude="./vendor" \
    --exclude="./storage" \
    --exclude="./.env" \
    --exclude="./.idea" \
    --exclude="./.vscode" \
    -cf - -C "${ROOT_DIR}" . | tar -xf - -C "${DESTINATION}"

echo "==> Done."
echo "Next: create a new git repo from ${DESTINATION} and push it to GitHub."
