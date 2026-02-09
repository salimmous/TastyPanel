#!/usr/bin/env bash
set -euo pipefail

# Provision a Next.js frontend per tenant.
# Required env:
#   TENANT_KEY          slug, e.g. "brand1"
#   TENANT_ID           numeric id (used for port)
#   TENANT_HOST         primary domain, e.g. brand1.com
#   PLATFORM_API_BASE   e.g. https://platform.example.com/api
# Optional:
#   TENANT_ENV          default: production
#   BASE_DIR            default: /var/www/tastypanel-sites
#   NODE_VERSION        default: 18

TENANT_KEY="${TENANT_KEY:?TENANT_KEY required}"
TENANT_ID="${TENANT_ID:?TENANT_ID required}"
TENANT_HOST="${TENANT_HOST:?TENANT_HOST required}"
PLATFORM_API_BASE="${PLATFORM_API_BASE:?PLATFORM_API_BASE required}"
TENANT_ENV="${TENANT_ENV:-production}"
BASE_DIR="${BASE_DIR:-/var/www/tastypanel-sites}"
NODE_VERSION="${NODE_VERSION:-18}"

SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../frontend" && pwd)"
TARGET_ROOT="${BASE_DIR}/${TENANT_KEY}"
TARGET_DIR="${TARGET_ROOT}/frontend"
PORT=$((32000 + TENANT_ID))
SERVICE_NAME="tastypanel-${TENANT_KEY}-frontend"

echo "==> Preparing directories"
mkdir -p "${TARGET_ROOT}"
rm -rf "${TARGET_DIR}"
cp -r "${SRC_DIR}" "${TARGET_DIR}"
cd "${TARGET_DIR}"

echo "==> Writing .env"
cp .env.example .env
sed -i "s|^TENANT_HOST=.*|TENANT_HOST=${TENANT_HOST}|g" .env
sed -i "s|^PLATFORM_API_BASE=.*|PLATFORM_API_BASE=${PLATFORM_API_BASE}|g" .env
sed -i "s|^TENANT_ENV=.*|TENANT_ENV=${TENANT_ENV}|g" .env

echo "==> Installing Node ${NODE_VERSION} (if not present) and deps"
if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | sudo -E bash -
  sudo apt install -y nodejs
fi

npm install --omit=dev
npm run build

echo "==> Creating systemd service ${SERVICE_NAME}"
cat <<EOF | sudo tee /etc/systemd/system/${SERVICE_NAME}.service >/dev/null
[Unit]
Description=TastyPanel tenant frontend (${TENANT_KEY})
After=network.target

[Service]
Type=simple
WorkingDirectory=${TARGET_DIR}
ExecStart=/usr/bin/npm start -- -p ${PORT}
Restart=always
User=www-data
Environment=HOST=0.0.0.0 PORT=${PORT}

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable ${SERVICE_NAME}
sudo systemctl restart ${SERVICE_NAME}

echo "==> Done. Frontend running on 127.0.0.1:${PORT}"
echo "Add Nginx proxy to this port for domain ${TENANT_HOST}."
