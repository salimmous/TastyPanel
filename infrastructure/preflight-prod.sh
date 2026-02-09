#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

STRICT=0
RUN_CI_GATES=1
RUN_NGINX_TEST=1
RUN_SYSTEMD=1
RUN_SMOKE_FLOW=0

usage() {
  cat <<'USAGE'
Usage: preflight-prod.sh [options]

Options:
  --strict         Exit non-zero when warnings exist.
  --no-ci-gates    Skip infrastructure/ci-gates.sh.
  --no-nginx-test  Skip sudo nginx -t.
  --no-systemd     Skip systemd service checks.
  --smoke-flow     Run infrastructure/smoke-test-tenant.sh flow (requires root).
  -h, --help       Show this help.
USAGE
}

while (( $# > 0 )); do
  case "$1" in
    --strict) STRICT=1 ;;
    --no-ci-gates) RUN_CI_GATES=0 ;;
    --no-nginx-test) RUN_NGINX_TEST=0 ;;
    --no-systemd) RUN_SYSTEMD=0 ;;
    --smoke-flow) RUN_SMOKE_FLOW=1 ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1"
      usage
      exit 1
      ;;
  esac
  shift
done

PASS_COUNT=0
WARN_COUNT=0
FAIL_COUNT=0

LOG_DIR="${ROOT_DIR}/storage/logs/preflight"
mkdir -p "${LOG_DIR}"
LOG_FILE="${LOG_DIR}/preflight-$(date +%Y%m%d-%H%M%S).log"
: > "${LOG_FILE}"

pass() {
  PASS_COUNT=$((PASS_COUNT + 1))
  printf '[PASS] %s\n' "$1"
}

warn() {
  WARN_COUNT=$((WARN_COUNT + 1))
  printf '[WARN] %s\n' "$1"
}

fail() {
  FAIL_COUNT=$((FAIL_COUNT + 1))
  printf '[FAIL] %s\n' "$1"
}

run_logged() {
  local label="$1"
  shift
  echo "\$ $*" >> "${LOG_FILE}"
  if "$@" >> "${LOG_FILE}" 2>&1; then
    pass "${label}"
  else
    fail "${label} (see ${LOG_FILE})"
  fi
}

env_value() {
  local key="$1"
  local line value
  line="$(grep -E "^${key}=" .env | tail -n 1 || true)"
  value="${line#*=}"
  value="${value%\"}"
  value="${value#\"}"
  value="${value%\'}"
  value="${value#\'}"
  printf '%s' "${value}"
}

is_true() {
  local v
  v="$(printf '%s' "${1:-}" | tr '[:upper:]' '[:lower:]')"
  [[ "${v}" == "1" || "${v}" == "true" || "${v}" == "yes" || "${v}" == "on" ]]
}

check_command() {
  local cmd="$1"
  local required="${2:-1}"
  if command -v "${cmd}" >/dev/null 2>&1; then
    pass "Command available: ${cmd}"
  else
    if [[ "${required}" -eq 1 ]]; then
      fail "Missing required command: ${cmd}"
    else
      warn "Missing optional command: ${cmd}"
    fi
  fi
}

check_file() {
  local path="$1"
  if [[ -f "${path}" ]]; then
    pass "File exists: ${path}"
  else
    fail "Missing file: ${path}"
  fi
}

check_executable() {
  local path="$1"
  if [[ ! -f "${path}" ]]; then
    fail "Missing script: ${path}"
  elif [[ ! -x "${path}" ]]; then
    fail "Script is not executable: ${path}"
  else
    pass "Script executable: ${path}"
  fi
}

check_writable_dir() {
  local path="$1"
  if [[ -d "${path}" && -w "${path}" ]]; then
    pass "Writable directory: ${path}"
  elif [[ -d "${path}" ]]; then
    fail "Directory is not writable: ${path}"
  else
    fail "Missing directory: ${path}"
  fi
}

echo "==> Preflight checks for production launch"
echo "Log file: ${LOG_FILE}"

check_file ".env"
check_file "artisan"
check_file "composer.json"
check_writable_dir "storage"
check_writable_dir "bootstrap/cache"

check_executable "infrastructure/provision-instance.sh"
check_executable "infrastructure/deprovision-instance.sh"
check_executable "infrastructure/provision-nginx.sh"
check_executable "infrastructure/provision-tenant-access.sh"
check_executable "infrastructure/manage-tenant-mailbox.sh"
check_executable "infrastructure/sync-tenant-env.sh"
check_executable "infrastructure/backup-tenant.sh"
check_executable "infrastructure/restore-tenant.sh"
check_executable "infrastructure/queue-tenant.sh"
check_executable "infrastructure/manage-platform-service.sh"
check_executable "infrastructure/deploy-nginx-safe.sh"

check_command "bash" 1
check_command "php" 1
check_command "mysql" 1
check_command "nginx" 1
check_command "certbot" 0
check_command "redis-cli" 0
check_command "systemctl" 0

APP_ENV_VAL="$(env_value "APP_ENV")"
APP_KEY_VAL="$(env_value "APP_KEY")"
APP_DEBUG_VAL="$(env_value "APP_DEBUG")"
DB_CONNECTION_VAL="$(env_value "DB_CONNECTION")"
DB_DATABASE_VAL="$(env_value "DB_DATABASE")"
DB_USERNAME_VAL="$(env_value "DB_USERNAME")"
SSL_AUTO_VAL="$(env_value "SSL_AUTO")"
CLOUDFLARE_DNS_TOKEN_VAL="$(env_value "CLOUDFLARE_DNS_TOKEN")"
PANEL_ALLOWED_IPS_VAL="$(env_value "PANEL_ALLOWED_IPS")"
TENANT_INSTANCES_ROOT_VAL="$(env_value "TENANT_INSTANCES_ROOT")"
TENANT_ACCESS_AUTH_MODE_VAL="$(env_value "TENANT_ACCESS_AUTH_MODE")"
TENANT_ACCESS_SFTP_ONLY_VAL="$(env_value "TENANT_ACCESS_SFTP_ONLY")"
TENANT_MAILBOX_ROOT_VAL="$(env_value "TENANT_MAILBOX_ROOT")"
TENANT_MAILBOX_USERS_FILE_VAL="$(env_value "TENANT_MAILBOX_USERS_FILE")"
PROMETHEUS_ENABLED_VAL="$(env_value "PROMETHEUS_ENABLED")"
PROMETHEUS_TOKEN_VAL="$(env_value "PROMETHEUS_TOKEN")"

if [[ -n "${APP_KEY_VAL}" ]]; then
  pass "APP_KEY is set"
else
  fail "APP_KEY is empty"
fi

if [[ "${APP_ENV_VAL}" == "production" ]]; then
  pass "APP_ENV=production"
else
  warn "APP_ENV is '${APP_ENV_VAL:-empty}', expected 'production'"
fi

if is_true "${APP_DEBUG_VAL}"; then
  fail "APP_DEBUG=true is not allowed for production"
else
  pass "APP_DEBUG is disabled"
fi

if [[ -z "${DB_CONNECTION_VAL}" ]]; then
  fail "DB_CONNECTION is empty"
else
  pass "DB_CONNECTION=${DB_CONNECTION_VAL}"
fi

if [[ -z "${DB_DATABASE_VAL}" || -z "${DB_USERNAME_VAL}" ]]; then
  fail "DB_DATABASE / DB_USERNAME must be set"
else
  pass "Database credentials are configured"
fi

if [[ -n "${TENANT_INSTANCES_ROOT_VAL}" ]]; then
  if [[ -d "${TENANT_INSTANCES_ROOT_VAL}" ]]; then
    pass "TENANT_INSTANCES_ROOT exists: ${TENANT_INSTANCES_ROOT_VAL}"
  else
    fail "TENANT_INSTANCES_ROOT missing: ${TENANT_INSTANCES_ROOT_VAL}"
  fi
else
  fail "TENANT_INSTANCES_ROOT is empty"
fi

if [[ -z "${TENANT_ACCESS_AUTH_MODE_VAL}" ]]; then
  warn "TENANT_ACCESS_AUTH_MODE is empty (defaulting to script default)"
elif [[ "${TENANT_ACCESS_AUTH_MODE_VAL}" == "both" || "${TENANT_ACCESS_AUTH_MODE_VAL}" == "keys" || "${TENANT_ACCESS_AUTH_MODE_VAL}" == "password" ]]; then
  pass "TENANT_ACCESS_AUTH_MODE=${TENANT_ACCESS_AUTH_MODE_VAL}"
else
  fail "TENANT_ACCESS_AUTH_MODE is invalid: ${TENANT_ACCESS_AUTH_MODE_VAL}"
fi

if [[ -z "${TENANT_ACCESS_SFTP_ONLY_VAL}" ]]; then
  warn "TENANT_ACCESS_SFTP_ONLY is empty (defaulting to script default)"
elif is_true "${TENANT_ACCESS_SFTP_ONLY_VAL}" || [[ "${TENANT_ACCESS_SFTP_ONLY_VAL}" == "0" || "${TENANT_ACCESS_SFTP_ONLY_VAL}" == "false" || "${TENANT_ACCESS_SFTP_ONLY_VAL}" == "no" || "${TENANT_ACCESS_SFTP_ONLY_VAL}" == "off" ]]; then
  pass "TENANT_ACCESS_SFTP_ONLY=${TENANT_ACCESS_SFTP_ONLY_VAL}"
else
  fail "TENANT_ACCESS_SFTP_ONLY is invalid: ${TENANT_ACCESS_SFTP_ONLY_VAL}"
fi

if [[ -z "${TENANT_MAILBOX_ROOT_VAL}" ]]; then
  warn "TENANT_MAILBOX_ROOT is empty (mailbox script default will be used)"
elif [[ -d "${TENANT_MAILBOX_ROOT_VAL}" ]]; then
  pass "TENANT_MAILBOX_ROOT exists: ${TENANT_MAILBOX_ROOT_VAL}"
else
  warn "TENANT_MAILBOX_ROOT does not exist yet: ${TENANT_MAILBOX_ROOT_VAL}"
fi

if [[ -z "${TENANT_MAILBOX_USERS_FILE_VAL}" ]]; then
  warn "TENANT_MAILBOX_USERS_FILE is empty (mailbox script default will be used)"
else
  pass "TENANT_MAILBOX_USERS_FILE is configured"
fi

if [[ -n "${PANEL_ALLOWED_IPS_VAL}" ]]; then
  pass "PANEL_ALLOWED_IPS is configured"
else
  warn "PANEL_ALLOWED_IPS is empty (panel not IP-restricted)"
fi

if is_true "${SSL_AUTO_VAL}"; then
  if [[ -n "${CLOUDFLARE_DNS_TOKEN_VAL}" ]]; then
    pass "SSL auto mode enabled with CLOUDFLARE_DNS_TOKEN"
  else
    fail "SSL_AUTO=true but CLOUDFLARE_DNS_TOKEN is empty"
  fi
fi

if is_true "${PROMETHEUS_ENABLED_VAL}"; then
  if [[ -n "${PROMETHEUS_TOKEN_VAL}" ]]; then
    pass "PROMETHEUS_TOKEN is configured"
  else
    warn "PROMETHEUS_ENABLED=true but PROMETHEUS_TOKEN is empty"
  fi
fi

run_logged "PHP syntax sanity" php -v
run_logged "Laravel bootstrap" php artisan --version
run_logged "Laravel routes load" php artisan route:list --path=api/admin/tenants
run_logged "Database connection (migrate status)" php artisan migrate:status

if [[ "${RUN_CI_GATES}" -eq 1 ]]; then
  run_logged "CI gates" ./infrastructure/ci-gates.sh
else
  warn "Skipping ci-gates (--no-ci-gates)"
fi

if command -v pgrep >/dev/null 2>&1; then
  if pgrep -f "artisan queue:work" >/dev/null 2>&1 || pgrep -f "artisan horizon" >/dev/null 2>&1; then
    pass "Queue worker process detected"
  else
    warn "No queue worker detected (queue:work/horizon)"
  fi
fi

SCHEDULE_FOUND=0
if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
  SCHEDULE_FOUND=1
fi
if grep -R "artisan schedule:run" /etc/cron.d /etc/crontab >/dev/null 2>&1; then
  SCHEDULE_FOUND=1
fi
if [[ "${SCHEDULE_FOUND}" -eq 1 ]]; then
  pass "Scheduler cron entry found"
else
  warn "No scheduler cron entry found for 'artisan schedule:run'"
fi

if command -v df >/dev/null 2>&1; then
  DISK_USE="$(df -Pk "${ROOT_DIR}" | awk 'NR==2 {gsub("%", "", $5); print $5}')"
  if [[ -n "${DISK_USE}" ]]; then
    if (( DISK_USE >= 90 )); then
      fail "Disk usage is ${DISK_USE}% (>=90%)"
    elif (( DISK_USE >= 80 )); then
      warn "Disk usage is ${DISK_USE}%"
    else
      pass "Disk usage is ${DISK_USE}%"
    fi
  fi
fi

HAS_SUDO=0
if sudo -n true >/dev/null 2>&1; then
  HAS_SUDO=1
  pass "Passwordless sudo available"
else
  warn "Passwordless sudo not available (root checks skipped)"
fi

if [[ "${RUN_NGINX_TEST}" -eq 1 ]]; then
  if [[ "${HAS_SUDO}" -eq 1 ]]; then
    run_logged "Nginx config test (sudo nginx -t)" sudo -n nginx -t
  else
    warn "Skipping nginx -t (no passwordless sudo)"
  fi
fi

if [[ "${RUN_SYSTEMD}" -eq 1 ]]; then
  if command -v systemctl >/dev/null 2>&1; then
    if [[ "${HAS_SUDO}" -eq 1 ]]; then
      if sudo -n systemctl is-active --quiet nginx; then
        pass "Service active: nginx"
      else
        fail "Service inactive: nginx"
      fi

      DB_SERVICE="mysql"
      if ! sudo -n systemctl list-unit-files | grep -q "^mysql\\.service"; then
        DB_SERVICE="mariadb"
      fi
      if sudo -n systemctl is-active --quiet "${DB_SERVICE}"; then
        pass "Service active: ${DB_SERVICE}"
      else
        fail "Service inactive: ${DB_SERVICE}"
      fi

      if sudo -n systemctl list-unit-files | grep -q "^redis-server\\.service"; then
        if sudo -n systemctl is-active --quiet redis-server; then
          pass "Service active: redis-server"
        else
          warn "Service inactive: redis-server"
        fi
      fi
    else
      warn "Skipping systemd checks (no passwordless sudo)"
    fi
  else
    warn "systemctl not available, skipping service checks"
  fi
fi

if [[ "${RUN_SMOKE_FLOW}" -eq 1 ]]; then
  if [[ "$(id -u)" -ne 0 ]]; then
    fail "--smoke-flow requires root user"
  else
    run_logged "Smoke flow (create/provision/ssh/rollback)" ./infrastructure/smoke-test-tenant.sh flow
  fi
fi

echo
echo "==> Preflight summary"
printf 'PASS: %s | WARN: %s | FAIL: %s\n' "${PASS_COUNT}" "${WARN_COUNT}" "${FAIL_COUNT}"
echo "Log: ${LOG_FILE}"

if (( FAIL_COUNT > 0 )); then
  exit 1
fi

if (( STRICT == 1 && WARN_COUNT > 0 )); then
  exit 2
fi

exit 0
