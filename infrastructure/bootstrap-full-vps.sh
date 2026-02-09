#!/usr/bin/env bash
# Full VPS install — nafs l7aja b7al CloudPanel.
# On a fresh Ubuntu 24.04 VPS, this script clones TastyPanel and runs the full install
# so the server is 100% dedicated to the panel (Nginx, PHP, MySQL, Redis, panel, cron).
#
# Usage (one command on a new VPS):
#   curl -sSL https://raw.githubusercontent.com/YOUR_ORG/tastypanel/main/infrastructure/bootstrap-full-vps.sh | sudo bash -s -- REPO_URL=https://github.com/YOUR_ORG/tastypanel.git PANEL_HOST=your-server-ip-or-domain
#
# Or download and run:
#   sudo bash bootstrap-full-vps.sh REPO_URL=... PANEL_HOST=...
#
# Required: REPO_URL (git clone URL)
# Optional: PANEL_HOST (default: panel.example.com), PANEL_PORT (default: 80), DB_PASS, etc.
# See infrastructure/install-ubuntu-24.04.sh for all variables.

set -euo pipefail

REPO_URL="${REPO_URL:-}"
PANEL_HOST="${PANEL_HOST:-panel.example.com}"
PANEL_PORT="${PANEL_PORT:-80}"
APP_DIR="${APP_DIR:-/var/www/tastypanel}"

if [[ -z "$REPO_URL" ]]; then
  echo "REPO_URL is required. Example:"
  echo "  curl -sSL https://raw.githubusercontent.com/.../bootstrap-full-vps.sh | sudo bash -s -- REPO_URL=https://github.com/you/tastypanel.git PANEL_HOST=84.247.160.84"
  exit 1
fi

echo "==> Full VPS install (TastyPanel — nafs l7aja b7al CloudPanel)"
echo "    REPO_URL=$REPO_URL"
echo "    PANEL_HOST=$PANEL_HOST"
echo "    APP_DIR=$APP_DIR"
echo ""

# Ensure we have git
if ! command -v git &>/dev/null; then
  apt-get update -qq
  apt-get install -y -qq git curl
fi

# Clone or update
if [[ ! -d "$APP_DIR/.git" ]]; then
  echo "==> Cloning repository..."
  sudo rm -rf "$APP_DIR"
  sudo mkdir -p "$(dirname "$APP_DIR")"
  sudo git clone "$REPO_URL" "$APP_DIR"
  sudo chown -R "$USER:$USER" "$APP_DIR"
else
  echo "==> Directory exists, pulling latest..."
  cd "$APP_DIR"
  git pull --rebase || true
  cd - >/dev/null
fi

echo "==> Running full install (install-ubuntu-24.04.sh)..."
cd "$APP_DIR"
export REPO_URL PANEL_HOST PANEL_PORT APP_DIR
# Pass through other env vars (DB_PASS, PANEL_SSL_SELF_SIGNED, etc.)
sudo -E bash infrastructure/install-ubuntu-24.04.sh

echo ""
echo "==> Done. VPS is now dedicated to TastyPanel (nafs l7aja b7al CloudPanel)."
echo "    Open: http://${PANEL_HOST}:${PANEL_PORT}/platform/install (or https if configured)"
echo ""
