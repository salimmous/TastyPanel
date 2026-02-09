#!/usr/bin/env bash
set -euo pipefail

TARGET="${1:-}"

echo "==> Security audit"

if [[ -n "$TARGET" && -d "$TARGET" ]]; then
  cd "$TARGET"
fi

if command -v composer >/dev/null 2>&1 && [ -f "composer.lock" ]; then
  echo "-- composer audit"
  composer audit --format=json || true
fi

if command -v npm >/dev/null 2>&1 && [ -f "package-lock.json" ]; then
  echo "-- npm audit"
  npm audit --json || true
fi

if command -v apt >/dev/null 2>&1; then
  echo "-- OS updates"
  apt list --upgradable 2>/dev/null | head -n 50 || true
fi

if [ -n "$TARGET" ]; then
  echo "-- target: $TARGET"
fi

exit 0
