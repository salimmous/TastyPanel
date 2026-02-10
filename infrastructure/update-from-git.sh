#!/usr/bin/env bash
# Auto-update TastyPanel from GitHub: pull code, composer, artisan.
# Run manually or via cron so the server stays up to date (code + docs).
# Cron example (daily 4am): 0 4 * * * /var/www/tastypanel/infrastructure/update-from-git.sh >> /var/log/tastypanel-update.log 2>&1

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/tastypanel}"
BRANCH="${TASTYPANEL_BRANCH:-main}"
LOG="${TASTYPANEL_UPDATE_LOG:-}"

cd "$APP_DIR"
if [[ ! -d .git ]]; then
  echo "[$(date -Iseconds)] Not a git repo, skip." >&2
  exit 0
fi

run() {
  if [[ -n "$LOG" ]]; then
    echo "[$(date -Iseconds)] $*" >> "$LOG"
  fi
  "$@"
}

run git fetch origin
BEFORE=$(git rev-parse HEAD 2>/dev/null || true)
run git pull origin "$BRANCH" --ff-only || { echo "[$(date -Iseconds)] git pull failed or non-fast-forward" >&2; exit 1; }
AFTER=$(git rev-parse HEAD 2>/dev/null || true)

if [[ "$BEFORE" == "$AFTER" ]]; then
  if [[ -n "$LOG" ]]; then echo "[$(date -Iseconds)] No changes (already up to date)." >> "$LOG"; fi
  exit 0
fi

run composer install --no-dev --optimize-autoloader
run php artisan migrate --force
run php artisan config:clear
run php artisan cache:clear

if [[ -n "$LOG" ]]; then
  echo "[$(date -Iseconds)] Updated $BEFORE -> $AFTER. Docs in documentation/ are also updated." >> "$LOG"
fi
exit 0
