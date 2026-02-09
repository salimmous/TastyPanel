#!/usr/bin/env bash
set -euo pipefail

TARGET="${1:-/var/www/tastypanel}"

if command -v clamscan >/dev/null 2>&1; then
  clamscan -r --infected --no-summary "$TARGET"
  exit $?
fi

if command -v clamdscan >/dev/null 2>&1; then
  clamdscan -r --infected --no-summary "$TARGET"
  exit $?
fi

if command -v maldet >/dev/null 2>&1; then
  maldet -a "$TARGET"
  exit $?
fi

echo "No supported scanner found (clamscan/clamdscan/maldet)."
exit 2
