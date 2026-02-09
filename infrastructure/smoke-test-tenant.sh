#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_DIR="${BASE_DIR:-/var/www/tastypanel-sites}"
NGINX_AVAILABLE_DIR="${NGINX_AVAILABLE_DIR:-/etc/nginx/sites-available}"
SSH_HOST="${SMOKE_SSH_HOST:-127.0.0.1}"
SSH_TIMEOUT="${SMOKE_SSH_TIMEOUT:-8}"
KEEP_FAILED="${SMOKE_KEEP_FAILED:-0}"

FAIL=0
TENANT_ID=""
DOMAIN_ID=""
RUNNER_FILE=""
KEY_FILE=""
PUB_FILE=""

usage() {
  cat <<'EOF'
Usage:
  smoke-test-tenant.sh <tenant_key> <domain> [php_version]
    - quick verification for an existing tenant deployment

  smoke-test-tenant.sh flow [tenant_key] [domain] [theme_id]
    - full flow: create tenant + provision + SSH key login + rollback + cleanup
EOF
}

pass() {
  echo "[PASS] $1"
}

fail() {
  echo "[FAIL] $1"
  FAIL=$((FAIL + 1))
}

require_cmd() {
  local cmd="$1"
  if ! command -v "${cmd}" >/dev/null 2>&1; then
    echo "Missing required command: ${cmd}"
    exit 1
  fi
}

check_path() {
  local path="$1"
  local label="$2"
  if [[ -e "${path}" ]]; then
    pass "${label}: ${path}"
  else
    fail "${label} missing: ${path}"
  fi
}

json_get() {
  local json="$1"
  local key="$2"
  php -r '
    $data = json_decode(stream_get_contents(STDIN), true);
    $key = $argv[1];
    if (!is_array($data) || !array_key_exists($key, $data)) {
      exit(3);
    }
    $value = $data[$key];
    if (is_bool($value)) {
      echo $value ? "true" : "false";
      exit(0);
    }
    if (is_array($value) || is_object($value)) {
      echo json_encode($value, JSON_UNESCAPED_SLASHES);
      exit(0);
    }
    echo $value === null ? "" : (string) $value;
  ' "${key}" <<<"${json}"
}

make_json_payload() {
  local tenant_key="$1"
  local tenant_name="$2"
  local domain="$3"
  local theme_id="$4"
  php -r '
    $theme = $argv[4] === "" ? null : (int) $argv[4];
    echo json_encode([
      "tenant_key" => $argv[1],
      "tenant_name" => $argv[2],
      "domain" => $argv[3],
      "theme_id" => $theme,
    ], JSON_UNESCAPED_SLASHES);
  ' "${tenant_key}" "${tenant_name}" "${domain}" "${theme_id}"
}

make_simple_payload() {
  local key="$1"
  local value="$2"
  php -r '
    echo json_encode([$argv[1] => is_numeric($argv[2]) ? (int) $argv[2] : $argv[2]], JSON_UNESCAPED_SLASHES);
  ' "${key}" "${value}"
}

run_runner() {
  local action="$1"
  local payload="$2"
  TB_ROOT="${ROOT_DIR}" php "${RUNNER_FILE}" "${action}" "${payload}"
}

cleanup_flow_resources() {
  if [[ "${KEEP_FAILED}" == "1" && "${FAIL}" -gt 0 ]]; then
    echo "[INFO] Keeping smoke-test tenant for debugging (SMOKE_KEEP_FAILED=1)."
    echo "[INFO] tenant_id=${TENANT_ID} domain_id=${DOMAIN_ID}"
  elif [[ -n "${TENANT_ID}" && -n "${RUNNER_FILE}" && -f "${RUNNER_FILE}" ]]; then
    local payload
    payload="$(make_simple_payload tenant_id "${TENANT_ID}")"
    run_runner cleanup "${payload}" >/dev/null 2>&1 || true
  fi

  if [[ -n "${KEY_FILE}" && -f "${KEY_FILE}" ]]; then
    rm -f "${KEY_FILE}"
  fi
  if [[ -n "${PUB_FILE}" && -f "${PUB_FILE}" ]]; then
    rm -f "${PUB_FILE}"
  fi
  if [[ -n "${RUNNER_FILE}" && -f "${RUNNER_FILE}" ]]; then
    rm -f "${RUNNER_FILE}"
  fi
}

create_php_runner() {
  RUNNER_FILE="$(mktemp /tmp/tastypanel-smoke-runner.XXXXXX.php)"
  cat >"${RUNNER_FILE}" <<'PHP'
<?php
declare(strict_types=1);

$root = getenv('TB_ROOT') ?: '';
if ($root === '') {
    fwrite(STDERR, "TB_ROOT is required\n");
    exit(2);
}

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$action = $argv[1] ?? '';
$payload = json_decode($argv[2] ?? '{}', true);
if (!is_array($payload)) {
    $payload = [];
}

$emit = static function (array $data): void {
    echo json_encode($data, JSON_UNESCAPED_SLASHES) . PHP_EOL;
};

try {
    if ($action === 'create_and_provision') {
        $tenantKey = trim((string) ($payload['tenant_key'] ?? ''));
        $tenantName = trim((string) ($payload['tenant_name'] ?? ''));
        $domainHost = trim((string) ($payload['domain'] ?? ''));
        $themeIdRaw = $payload['theme_id'] ?? null;
        $themeId = is_numeric((string) $themeIdRaw) ? (int) $themeIdRaw : null;

        if ($tenantKey === '' || $tenantName === '' || $domainHost === '') {
            $emit([
                'success' => false,
                'error' => 'tenant_key, tenant_name and domain are required.',
            ]);
            exit(0);
        }

        $tenant = \App\Models\Tenant::create([
            'name' => $tenantName,
            'slug' => $tenantKey,
            'theme_id' => $themeId,
            'status' => 'active',
        ]);

        $tenant->settings()->create([
            'environment' => 'production',
            'data' => [
                'smoke_test' => true,
                'created_at' => now()->toIso8601String(),
            ],
        ]);

        $domain = $tenant->domains()->create([
            'hostname' => strtolower($domainHost),
            'is_primary' => true,
            'status' => 'pending',
            'cf_zone_id' => null,
            'environment' => 'production',
        ]);

        $provisioning = app(\App\Services\ProvisioningService::class);
        $result = $provisioning->provisionDomainWithState($domain);

        $tenant = $tenant->fresh();
        $domain = $domain->fresh();

        $access = app(\App\Services\TenantAccessService::class)->ensureAccess($tenant);
        $tenant = $tenant->fresh();

        $emit([
            'success' => true,
            'tenant_id' => $tenant->id,
            'domain_id' => $domain->id,
            'tenant_key' => $tenant->slug,
            'domain' => $domain->hostname,
            'domain_status' => $domain->status,
            'provision_success' => (bool) ($result['success'] ?? false),
            'provision_blocked' => (bool) ($result['blocked'] ?? false),
            'completed_steps' => $result['completed_steps'] ?? [],
            'failed_step' => $result['failed_step'] ?? null,
            'provision_errors' => $result['errors'] ?? [],
            'instance_root' => $tenant->instance_root,
            'ssh_user' => $tenant->instance_ssh_user,
            'ssh_port' => $tenant->instance_ssh_port ?: 22,
            'access_success' => (bool) ($access['success'] ?? false),
            'access_sftp_only' => ((string) (($access['meta']['SFTP_ONLY'] ?? '0')) === '1'),
            'access_output' => $access['output'] ?? null,
        ]);
        exit(0);
    }

    if ($action === 'install_key') {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $publicKey = trim((string) ($payload['public_key'] ?? ''));
        $tenant = \App\Models\Tenant::query()->find($tenantId);

        if (!$tenant || $publicKey === '') {
            $emit([
                'success' => false,
                'error' => 'tenant_id and public_key are required.',
            ]);
            exit(0);
        }

        $result = app(\App\Services\TenantAccessService::class)->installPublicKey($tenant, $publicKey);
        $emit([
            'success' => (bool) ($result['success'] ?? false),
            'output' => $result['output'] ?? null,
        ]);
        exit(0);
    }

    if ($action === 'rollback') {
        $domainId = (int) ($payload['domain_id'] ?? 0);
        $domain = \App\Models\Domain::query()->find($domainId);
        if (!$domain) {
            $emit([
                'success' => false,
                'error' => 'domain not found',
            ]);
            exit(0);
        }

        $result = app(\App\Services\ProvisioningService::class)->rollbackDomain($domain);
        $emit([
            'success' => (bool) ($result['success'] ?? false),
            'errors' => $result['errors'] ?? [],
        ]);
        exit(0);
    }

    if ($action === 'cleanup') {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        if ($tenantId > 0) {
            $tenant = \App\Models\Tenant::query()->find($tenantId);
            if ($tenant) {
                $tenant->delete();
            }
        }

        $emit([
            'success' => true,
        ]);
        exit(0);
    }

    $emit([
        'success' => false,
        'error' => 'unknown action',
    ]);
    exit(0);
} catch (\Throwable $e) {
    $emit([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
    exit(0);
}
PHP
  chmod 600 "${RUNNER_FILE}"
}

run_verify_mode() {
  local tenant_key="$1"
  local domain="$2"
  local php_version="${3:-8.3}"

  local frontend_service="tastypanel-${tenant_key}-frontend.service"
  local instance_root="${BASE_DIR}/${tenant_key}"
  local frontend_dir="${instance_root}/frontend"
  local php_socket="/run/php/php${php_version}-fpm-${tenant_key}.sock"
  local nginx_conf="${NGINX_AVAILABLE_DIR}/${domain}.conf"

  check_path "${instance_root}" "Instance root"
  check_path "${frontend_dir}" "Frontend directory"
  check_path "${nginx_conf}" "Nginx config"

  if [[ -S "${php_socket}" ]]; then
    pass "PHP-FPM socket: ${php_socket}"
  else
    fail "PHP-FPM socket missing: ${php_socket}"
  fi

  if systemctl is-active --quiet "${frontend_service}"; then
    pass "Frontend service active: ${frontend_service}"
  else
    fail "Frontend service inactive: ${frontend_service}"
  fi

  if nginx -t >/dev/null 2>&1; then
    pass "nginx -t"
  else
    fail "nginx -t failed"
  fi

  if curl -skI --resolve "${domain}:443:127.0.0.1" "https://${domain}/" >/dev/null 2>&1; then
    pass "HTTPS response ok for ${domain}"
  elif curl -sI --resolve "${domain}:80:127.0.0.1" "http://${domain}/" >/dev/null 2>&1; then
    pass "HTTP response ok for ${domain}"
  else
    fail "No HTTP/HTTPS response for ${domain}"
  fi
}

run_flow_mode() {
  require_cmd php
  require_cmd ssh
  require_cmd sftp
  require_cmd ssh-keygen

  create_php_runner
  trap cleanup_flow_resources EXIT

  local tenant_key="${1:-}"
  local domain="${2:-}"
  local theme_id="${3:-}"

  local ts
  ts="$(date +%s)"
  if [[ -z "${tenant_key}" ]]; then
    tenant_key="smoke-${ts}"
  fi
  if [[ -z "${domain}" ]]; then
    domain="${tenant_key}.smoke.local"
  fi
  local tenant_name="Smoke ${ts}"

  local payload
  payload="$(make_json_payload "${tenant_key}" "${tenant_name}" "${domain}" "${theme_id}")"

  local create_json
  create_json="$(run_runner create_and_provision "${payload}")"

  local create_ok
  create_ok="$(json_get "${create_json}" success || echo "false")"
  if [[ "${create_ok}" != "true" ]]; then
    fail "Create/provision bootstrap failed: ${create_json}"
    return
  fi

  TENANT_ID="$(json_get "${create_json}" tenant_id || true)"
  DOMAIN_ID="$(json_get "${create_json}" domain_id || true)"

  local provision_success provision_blocked access_success access_sftp_only ssh_user ssh_port instance_root
  provision_success="$(json_get "${create_json}" provision_success || echo "false")"
  provision_blocked="$(json_get "${create_json}" provision_blocked || echo "false")"
  access_success="$(json_get "${create_json}" access_success || echo "false")"
  access_sftp_only="$(json_get "${create_json}" access_sftp_only || echo "false")"
  ssh_user="$(json_get "${create_json}" ssh_user || true)"
  ssh_port="$(json_get "${create_json}" ssh_port || echo "22")"
  instance_root="$(json_get "${create_json}" instance_root || true)"

  if [[ "${provision_success}" == "true" ]]; then
    pass "Provisioning success for tenant ${TENANT_ID} domain ${DOMAIN_ID}"
  else
    fail "Provisioning failed: ${create_json}"
  fi

  if [[ "${provision_blocked}" == "true" ]]; then
    pass "Provisioning is blocked (Cloudflare not configured), instance path still validated."
  fi

  if [[ "${access_success}" == "true" && -n "${ssh_user}" ]]; then
    pass "Tenant access user provisioned: ${ssh_user}"
  else
    fail "Tenant SSH access provisioning failed: ${create_json}"
  fi

  if [[ "${access_success}" == "true" && -n "${ssh_user}" ]]; then
    KEY_FILE="$(mktemp /tmp/tastypanel-smoke-key.XXXXXX)"
    PUB_FILE="${KEY_FILE}.pub"
    ssh-keygen -q -t ed25519 -N "" -f "${KEY_FILE}"

    local pub_key key_payload key_json key_ok
    pub_key="$(cat "${PUB_FILE}")"
    key_payload="$(php -r '
      echo json_encode([
        "tenant_id" => (int) $argv[1],
        "public_key" => $argv[2],
      ], JSON_UNESCAPED_SLASHES);
    ' "${TENANT_ID}" "${pub_key}")"
    key_json="$(run_runner install_key "${key_payload}")"
    key_ok="$(json_get "${key_json}" success || echo "false")"

    if [[ "${key_ok}" == "true" ]]; then
      pass "SSH public key installed for ${ssh_user}"
    else
      fail "SSH public key install failed: ${key_json}"
    fi

    if [[ "${key_ok}" == "true" ]]; then
      if [[ "${access_sftp_only}" == "true" ]]; then
        if sftp \
          -o BatchMode=yes \
          -o StrictHostKeyChecking=no \
          -o UserKnownHostsFile=/dev/null \
          -o ConnectTimeout="${SSH_TIMEOUT}" \
          -i "${KEY_FILE}" \
          -P "${ssh_port}" \
          "${ssh_user}@${SSH_HOST}" \
          <<< $'pwd\nquit' >/dev/null 2>&1; then
          pass "SFTP login succeeded for ${ssh_user}@${SSH_HOST}:${ssh_port}"
        else
          fail "SFTP login failed for ${ssh_user}@${SSH_HOST}:${ssh_port}"
        fi
      else
        local whoami_output
        if whoami_output="$(ssh \
          -o BatchMode=yes \
          -o StrictHostKeyChecking=no \
          -o UserKnownHostsFile=/dev/null \
          -o ConnectTimeout="${SSH_TIMEOUT}" \
          -i "${KEY_FILE}" \
          -p "${ssh_port}" \
          "${ssh_user}@${SSH_HOST}" \
          "id -un" 2>/dev/null)"; then
          if [[ "$(echo "${whoami_output}" | tr -d '\r' | tail -n 1)" == "${ssh_user}" ]]; then
            pass "SSH login succeeded for ${ssh_user}@${SSH_HOST}:${ssh_port}"
          else
            fail "SSH connected but returned unexpected user: ${whoami_output}"
          fi
        else
          fail "SSH login failed for ${ssh_user}@${SSH_HOST}:${ssh_port}"
        fi
      fi
    fi
  fi

  if [[ -n "${DOMAIN_ID}" ]]; then
    local rollback_payload rollback_json rollback_ok
    rollback_payload="$(make_simple_payload domain_id "${DOMAIN_ID}")"
    rollback_json="$(run_runner rollback "${rollback_payload}")"
    rollback_ok="$(json_get "${rollback_json}" success || echo "false")"
    if [[ "${rollback_ok}" == "true" ]]; then
      pass "Rollback success for domain ${DOMAIN_ID}"
    else
      fail "Rollback failed: ${rollback_json}"
    fi
  else
    fail "Rollback skipped because domain_id is missing."
  fi

  if [[ -n "${instance_root}" ]]; then
    if [[ ! -d "${instance_root}" ]]; then
      pass "Instance root cleaned: ${instance_root}"
    else
      fail "Instance root still exists after rollback: ${instance_root}"
    fi
  fi

  if [[ -n "${ssh_user}" ]]; then
    if id -u "${ssh_user}" >/dev/null 2>&1; then
      fail "SSH user still exists after rollback: ${ssh_user}"
    else
      pass "SSH user removed after rollback: ${ssh_user}"
    fi
  fi
}

MODE="${1:-}"
if [[ -z "${MODE}" ]]; then
  usage
  exit 1
fi

if [[ "${MODE}" == "flow" ]]; then
  run_flow_mode "${2:-}" "${3:-}" "${4:-}"
else
  TENANT_KEY="${1:-}"
  DOMAIN="${2:-}"
  PHP_VERSION="${3:-8.3}"

  if [[ -z "${TENANT_KEY}" || -z "${DOMAIN}" ]]; then
    usage
    exit 1
  fi

  run_verify_mode "${TENANT_KEY}" "${DOMAIN}" "${PHP_VERSION}"
fi

if [[ "${FAIL}" -gt 0 ]]; then
  echo "Smoke test finished with ${FAIL} failure(s)."
  exit 1
fi

echo "Smoke test passed."
