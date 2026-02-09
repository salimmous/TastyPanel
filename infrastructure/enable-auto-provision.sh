#!/bin/bash
# Enable tenant auto-provisioning by updating .env with required values.
# Usage: TENANT_APP_REPO=https://github.com/org/tenant-app.git [TENANT_APP_BRANCH=main] [FRONTEND_AUTO=false] ./infrastructure/enable-auto-provision.sh

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
ENV_FILE="$ROOT_DIR/.env"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing .env at $ENV_FILE" >&2
  exit 1
fi

REPO="${TENANT_APP_REPO:-}"  # required
BRANCH="${TENANT_APP_BRANCH:-main}"
FRONTEND_AUTO="${FRONTEND_AUTO:-false}"

if [[ -z "$REPO" ]]; then
  echo "Set TENANT_APP_REPO to the tenant code repository (https://...git)" >&2
  exit 1
fi

set_kv() {
  local key="$1" value="$2"
  if grep -q "^${key}=" "$ENV_FILE"; then
    sed -i "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
  else
    echo "${key}=${value}" >> "$ENV_FILE"
  fi
}

set_kv "AUTO_PROVISION_ON_TENANT_CREATE" "true"
set_kv "TENANT_APP_REPO" "$REPO"
set_kv "TENANT_APP_BRANCH" "$BRANCH"
set_kv "FRONTEND_AUTO" "$FRONTEND_AUTO"

cat <<EOF
Updated .env:
  AUTO_PROVISION_ON_TENANT_CREATE=true
  TENANT_APP_REPO=$REPO
  TENANT_APP_BRANCH=$BRANCH
  FRONTEND_AUTO=$FRONTEND_AUTO

Restart queue workers so new env is picked up, e.g.:
  supervisorctl restart tastypanel-worker:*
EOF
