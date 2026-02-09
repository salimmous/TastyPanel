#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

echo "[gate] PHP syntax checks"
while IFS= read -r -d '' file; do
  php -l "${file}" >/dev/null
done < <(find app bootstrap config database routes -type f -name "*.php" -print0)

echo "[gate] Shell syntax checks"
while IFS= read -r -d '' file; do
  bash -n "${file}"
done < <(find infrastructure -type f -name "*.sh" -print0)

echo "[gate] Duplicate create-table migration guard"
python3 - <<'PY'
import glob
import os
import re
import sys

creates = {}
for path in sorted(glob.glob("database/migrations/*.php")):
    content = open(path, "r", encoding="utf-8", errors="ignore").read()
    for match in re.finditer(r"Schema::create\('([^']+)'", content):
        table = match.group(1)
        creates.setdefault(table, []).append(os.path.basename(path))

dups = {k: v for k, v in creates.items() if len(v) > 1}
if dups:
    print("Duplicate Schema::create tables detected:")
    for table, files in sorted(dups.items()):
        print(f"  {table}")
        for file in files:
            print(f"    - {file}")
    sys.exit(1)
print("No duplicate create-table migrations found.")
PY

echo "[gate] Route bootstrap check"
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan route:list --path=api/admin/tenants >/dev/null

if [[ "${RUN_SMOKE_FLOW:-0}" == "1" ]]; then
  echo "[gate] Running smoke flow check"
  if [[ "$(id -u)" -ne 0 ]]; then
    echo "RUN_SMOKE_FLOW=1 requires root privileges for SSH/system checks."
    exit 1
  fi
  ./infrastructure/smoke-test-tenant.sh flow
fi

echo "[gate] All checks passed."

