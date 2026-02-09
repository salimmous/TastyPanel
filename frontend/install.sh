#!/usr/bin/env bash
set -euo pipefail

# One-shot installer for tenant frontend (Next.js)
# Usage:
#   TENANT_HOST=yourdomain.com PLATFORM_API_BASE=https://platform.example.com/api ./install.sh

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ -z "${TENANT_HOST:-}" || -z "${PLATFORM_API_BASE:-}" ]]; then
  echo "TENANT_HOST and PLATFORM_API_BASE are required env vars."
  exit 1
fi

cd "$ROOT"

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

if sed --version >/dev/null 2>&1; then
  sed -i "s|^TENANT_HOST=.*|TENANT_HOST=${TENANT_HOST}|g" .env
  sed -i "s|^PLATFORM_API_BASE=.*|PLATFORM_API_BASE=${PLATFORM_API_BASE}|g" .env
else
  sed -i '' "s|^TENANT_HOST=.*|TENANT_HOST=${TENANT_HOST}|g" .env
  sed -i '' "s|^PLATFORM_API_BASE=.*|PLATFORM_API_BASE=${PLATFORM_API_BASE}|g" .env
fi

npm install
npm run build

echo "Done. Start with: npm start"
