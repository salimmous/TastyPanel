#!/usr/bin/env bash
# Full VPS install — nafs l7aja b7al CloudPanel.
# On a fresh Ubuntu 24.04 VPS, this script clones TastyPanel and runs the full install
# so the server is 100% dedicated to the panel (Nginx, PHP, MySQL, Redis, panel, cron).
#
# Usage (one command on a new VPS — full VPS b7al CloudPanel):
#   curl -sSL https://raw.githubusercontent.com/salimmous/TastyPanel/main/infrastructure/bootstrap-full-vps.sh | sudo bash -s -- PANEL_HOST=84.247.160.84 PANEL_PORT=8042
#   → Panel: http://84.247.160.84:8042/platform/install
#
# Or with gh:  gh repo clone salimmous/TastyPanel /var/www/tastypanel && cd /var/www/tastypanel && sudo PANEL_HOST=... PANEL_PORT=8042 bash infrastructure/install-ubuntu-24.04.sh
#
# REPO_URL defaults to https://github.com/salimmous/TastyPanel.git (optional override).
# PANEL_PORT: default 8080 in install script; use PANEL_PORT=8042 (or 80, 8443, etc.) so panel is on that port.
# See infrastructure/install-ubuntu-24.04.sh for all variables.

set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/salimmous/TastyPanel.git}"
PANEL_HOST="${PANEL_HOST:-}"
PANEL_PORT="${PANEL_PORT:-80}"
APP_DIR="${APP_DIR:-/var/www/tastypanel}"

if [[ -z "$PANEL_HOST" ]]; then
  echo "PANEL_HOST is required (server IP or domain). Example:"
  echo "  curl -sSL .../bootstrap-full-vps.sh | sudo bash -s -- PANEL_HOST=84.247.160.84 PANEL_PORT=8042"
  echo "  # Panel URL will be: http://PANEL_HOST:PANEL_PORT/platform/install (default port 8080, use PANEL_PORT=8042 etc.)"
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
echo "    Open: http://${PANEL_HOST}:${PANEL_PORT}/platform/install"
echo "    (or https if PANEL_SCHEME=https)"
echo ""
