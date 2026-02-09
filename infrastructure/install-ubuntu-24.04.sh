#!/usr/bin/env bash
set -euo pipefail

# ---- Editable defaults ----
APP_DIR="${APP_DIR:-/var/www/tastypanel}"
REPO_URL="${REPO_URL:-}"
TENANT_APP_REPO="${TENANT_APP_REPO:-${REPO_URL}}"
TENANT_APP_BRANCH="${TENANT_APP_BRANCH:-main}"
TENANT_INSTANCES_ROOT="${TENANT_INSTANCES_ROOT:-/var/www/tastypanel-sites}"
TENANT_PHP_VERSION="${TENANT_PHP_VERSION:-8.3}"
APP_DOMAIN="${APP_DOMAIN:-platform.example.com}"
PANEL_HOST="${PANEL_HOST:-${APP_DOMAIN}}"
PANEL_SCHEME="${PANEL_SCHEME:-http}"
PANEL_PORT="${PANEL_PORT:-8080}"
PANEL_SSL_SELF_SIGNED="${PANEL_SSL_SELF_SIGNED:-false}"
PANEL_CERT_PATH="${PANEL_CERT_PATH:-/etc/ssl/tastypanel/panel.crt}"
PANEL_KEY_PATH="${PANEL_KEY_PATH:-/etc/ssl/tastypanel/panel.key}"
PANEL_ALLOWED_IPS="${PANEL_ALLOWED_IPS:-}"
DB_NAME="${DB_NAME:-tastypanel}"
DB_USER="${DB_USER:-tastypanel}"
DB_PASS="${DB_PASS:-}"
PHP_VERSION="${PHP_VERSION:-8.3}"

SSL_AUTO="${SSL_AUTO:-false}"
SSL_CERTBOT_EMAIL="${SSL_CERTBOT_EMAIL:-}"
CLOUDFLARE_DNS_TOKEN="${CLOUDFLARE_DNS_TOKEN:-}"

AUTO_NGINX="${AUTO_NGINX:-true}"
PROVISIONING_LOCK_TTL_SECONDS="${PROVISIONING_LOCK_TTL_SECONDS:-1800}"
PROMETHEUS_ENABLED="${PROMETHEUS_ENABLED:-true}"
PROMETHEUS_TOKEN="${PROMETHEUS_TOKEN:-}"

# ---- Helpers ----
require_value() {
  local value="$1"
  local message="$2"
  if [[ -z "$value" ]]; then
    echo "$message"
    exit 1
  fi
}

generate_password() {
  openssl rand -base64 18 | tr -d '\n'
}

set_env() {
  local key="$1"
  local value="$2"
  local file="$3"
  if grep -qE "^${key}=" "$file"; then
    sed -i "s#^${key}=.*#${key}=${value}#g" "$file"
  else
    echo "${key}=${value}" >> "$file"
  fi
}

echo "==> Installing system dependencies"
sudo apt update
sudo apt install -y nginx git unzip zip software-properties-common curl ufw netcat-openbsd acl
sudo apt install -y "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mbstring" \
  "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip" \
  "php${PHP_VERSION}-sqlite3" "php${PHP_VERSION}-bcmath" "php${PHP_VERSION}-mysql"
sudo apt install -y certbot python3-certbot-dns-cloudflare clamav

sudo apt install -y mysql-server

echo "==> Installing Composer"
sudo apt install -y composer

if [[ -z "$DB_PASS" ]]; then
  DB_PASS="$(generate_password)"
fi

echo "==> Setting up MySQL database"
sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

echo "==> Project directory"
sudo mkdir -p "$APP_DIR"
# Skip clone if project already present (git repo or existing code e.g. composer.json)
if [[ -d "$APP_DIR/.git" ]]; then
  echo "    (git repo at $APP_DIR, skipping clone)"
elif [[ -f "$APP_DIR/composer.json" ]]; then
  echo "    (project already at $APP_DIR, skipping clone — no REPO_URL needed)"
else
  require_value "$REPO_URL" "REPO_URL is required when installing to an empty directory. Example: REPO_URL=https://github.com/salimmous/TastyPanel.git"
  sudo chown -R "$USER:$USER" "$APP_DIR" 2>/dev/null || true
  git clone "$REPO_URL" "$APP_DIR"
  sudo chown -R "$USER:$USER" "$APP_DIR"
fi

cd "$APP_DIR"

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

echo "==> Configuring environment"
set_env "APP_ENV" "production" ".env"
set_env "APP_DEBUG" "false" ".env"
set_env "APP_NAME" "\"TastyPanel\"" ".env"
PANEL_URL="${PANEL_SCHEME}://${PANEL_HOST}"
if [[ "${PANEL_SCHEME}" == "http" && "${PANEL_PORT}" != "80" ]]; then
  PANEL_URL="${PANEL_SCHEME}://${PANEL_HOST}:${PANEL_PORT}"
fi
if [[ "${PANEL_SCHEME}" == "https" && "${PANEL_PORT}" != "443" ]]; then
  PANEL_URL="${PANEL_SCHEME}://${PANEL_HOST}:${PANEL_PORT}"
fi
set_env "APP_URL" "${PANEL_URL}" ".env"
set_env "APP_MODE" "platform" ".env"
set_env "TENANT_MODE" "false" ".env"
set_env "TENANT_LOCK_ID" "" ".env"
set_env "VITE_APP_MODE" "platform" ".env"
set_env "DB_CONNECTION" "mysql" ".env"
set_env "DB_HOST" "127.0.0.1" ".env"
set_env "DB_PORT" "3306" ".env"
set_env "DB_DATABASE" "$DB_NAME" ".env"
set_env "DB_USERNAME" "$DB_USER" ".env"
set_env "DB_PASSWORD" "$DB_PASS" ".env"

set_env "SSL_AUTO" "$SSL_AUTO" ".env"
set_env "SSL_CERTBOT_EMAIL" "$SSL_CERTBOT_EMAIL" ".env"
set_env "CLOUDFLARE_DNS_TOKEN" "$CLOUDFLARE_DNS_TOKEN" ".env"
set_env "PANEL_ALLOWED_IPS" "$PANEL_ALLOWED_IPS" ".env"
set_env "PLATFORM_RATE_LIMIT" "120" ".env"
set_env "PROVISIONING_LOCK_TTL_SECONDS" "$PROVISIONING_LOCK_TTL_SECONDS" ".env"
set_env "AUTO_PROVISION_ON_TENANT_CREATE" "false" ".env"
set_env "PROMETHEUS_ENABLED" "$PROMETHEUS_ENABLED" ".env"
set_env "PROMETHEUS_TOKEN" "$PROMETHEUS_TOKEN" ".env"

set_env "AUTO_NGINX" "$AUTO_NGINX" ".env"
set_env "NGINX_PROVISION_SCRIPT" "${APP_DIR}/infrastructure/provision-nginx.sh" ".env"
set_env "NGINX_USE_SUDO" "true" ".env"
set_env "TENANT_WEB_ROOT" "${APP_DIR}/public" ".env"
set_env "TENANT_FILES_ROOT" "${APP_DIR}/storage/tenant-files" ".env"
set_env "PHP_FPM_SOCKET" "/run/php/php${PHP_VERSION}-fpm.sock" ".env"
set_env "TENANT_INSTANCES_ROOT" "${TENANT_INSTANCES_ROOT}" ".env"
set_env "TENANT_APP_REPO" "${TENANT_APP_REPO}" ".env"
set_env "TENANT_APP_BRANCH" "${TENANT_APP_BRANCH}" ".env"
set_env "TENANT_PHP_VERSION" "${TENANT_PHP_VERSION}" ".env"
set_env "INSTANCE_PROVISION_SCRIPT" "${APP_DIR}/infrastructure/provision-instance.sh" ".env"
set_env "INSTANCE_USE_SUDO" "true" ".env"
set_env "INSTANCE_DEPROVISION_SCRIPT" "${APP_DIR}/infrastructure/deprovision-instance.sh" ".env"
set_env "INSTANCE_DEPROVISION_USE_SUDO" "true" ".env"
set_env "INSTANCE_SYSTEM_USER_PREFIX" "tbapp" ".env"
set_env "INSTANCE_FPM_MAX_CHILDREN" "10" ".env"
set_env "INSTANCE_FPM_MAX_REQUESTS" "500" ".env"
set_env "INSTANCE_FPM_MEMORY_LIMIT_MB" "256" ".env"
set_env "INSTANCE_ORCHESTRATE_SCRIPT" "${APP_DIR}/infrastructure/orchestrate-tenant.sh" ".env"
set_env "INSTANCE_ORCHESTRATE_USE_SUDO" "true" ".env"
set_env "INSTANCE_CLONE_SCRIPT" "${APP_DIR}/infrastructure/clone-tenant.sh" ".env"
set_env "INSTANCE_CLONE_USE_SUDO" "true" ".env"
set_env "TENANT_ENV_SYNC_SCRIPT" "${APP_DIR}/infrastructure/sync-tenant-env.sh" ".env"
set_env "TENANT_ENV_SYNC_USE_SUDO" "true" ".env"
set_env "FRONTEND_AUTO" "false" ".env"
set_env "FRONTEND_PROVISION_SCRIPT" "${APP_DIR}/infrastructure/provision-frontend.sh" ".env"
set_env "FRONTEND_DEPROVISION_SCRIPT" "${APP_DIR}/infrastructure/deprovision-frontend.sh" ".env"
set_env "FRONTEND_PROVISION_USE_SUDO" "true" ".env"
set_env "FRONTEND_PLATFORM_API_BASE" "${PANEL_URL}/api" ".env"
set_env "TENANT_ACCESS_SCRIPT" "${APP_DIR}/infrastructure/provision-tenant-access.sh" ".env"
set_env "TENANT_ACCESS_USE_SUDO" "true" ".env"
set_env "TENANT_ACCESS_AUTH_MODE" "both" ".env"
set_env "TENANT_ACCESS_SFTP_ONLY" "false" ".env"
set_env "TENANT_MAILBOX_SCRIPT" "${APP_DIR}/infrastructure/manage-tenant-mailbox.sh" ".env"
set_env "TENANT_MAILBOX_USE_SUDO" "true" ".env"
set_env "TENANT_MAILBOX_ROOT" "/var/mail/tastypanel" ".env"
set_env "TENANT_MAILBOX_USERS_FILE" "/etc/dovecot/tastypanel-users" ".env"
set_env "TENANT_MAILBOX_OS_USER" "vmail" ".env"
set_env "TENANT_MAILBOX_OS_GROUP" "vmail" ".env"
set_env "TENANT_MAIL_DEFAULT_DAILY_LIMIT" "500" ".env"
set_env "TENANT_MAIL_DEFAULT_PER_MINUTE_LIMIT" "30" ".env"
set_env "TENANT_BACKUP_ROOT" "${APP_DIR}/storage/app/tenant-backups" ".env"
set_env "TENANT_BACKUP_SCRIPT" "${APP_DIR}/infrastructure/backup-tenant.sh" ".env"
set_env "TENANT_RESTORE_SCRIPT" "${APP_DIR}/infrastructure/restore-tenant.sh" ".env"
set_env "TENANT_BACKUP_USE_SUDO" "false" ".env"
set_env "TENANT_QUEUE_SCRIPT" "${APP_DIR}/infrastructure/queue-tenant.sh" ".env"
set_env "TENANT_QUEUE_USE_SUDO" "false" ".env"
set_env "NGINX_ACCESS_LOG_TEMPLATE" "/var/log/nginx/%s-access.log" ".env"
set_env "NGINX_ERROR_LOG_TEMPLATE" "/var/log/nginx/%s-error.log" ".env"
set_env "PHP_FPM_LOG" "/var/log/php/php${PHP_VERSION}-fpm.log" ".env"
set_env "SECURITY_SCAN_SCRIPT" "${APP_DIR}/infrastructure/security-scan.sh" ".env"
set_env "SECURITY_SCAN_USE_SUDO" "true" ".env"
set_env "SECURITY_AUDIT_SCRIPT" "${APP_DIR}/infrastructure/security-audit.sh" ".env"
set_env "FIREWALL_SCRIPT" "${APP_DIR}/infrastructure/firewall-apply.sh" ".env"
set_env "FIREWALL_USE_SUDO" "true" ".env"
set_env "PLATFORM_SERVICE_MANAGER_SCRIPT" "${APP_DIR}/infrastructure/manage-platform-service.sh" ".env"
set_env "PLATFORM_SERVICE_MANAGER_USE_SUDO" "true" ".env"
set_env "PLATFORM_SERVICE_NGINX" "nginx" ".env"
set_env "PLATFORM_SERVICE_PHP_FPM" "php${PHP_VERSION}-fpm" ".env"
set_env "PLATFORM_SERVICE_DB" "mysql" ".env"
set_env "PLATFORM_SERVICE_REDIS" "redis-server" ".env"
set_env "PLATFORM_SERVICE_QUEUE" "tastypanel-queue.service" ".env"
set_env "PLATFORM_SERVICE_SCHEDULER" "tastypanel-scheduler.service" ".env"
set_env "NGINX_SAFE_DEPLOY_SCRIPT" "${APP_DIR}/infrastructure/deploy-nginx-safe.sh" ".env"
set_env "NGINX_SAFE_DEPLOY_USE_SUDO" "true" ".env"
set_env "NGINX_DEPLOY_BACKUP_ROOT" "/var/backups/tastypanel-nginx" ".env"

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Skipping Node.js frontend build (Laravel Blade mode)"

echo "==> Laravel setup"
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link || true

mkdir -p storage/tenant-files
mkdir -p storage/app/tenant-backups
sudo mkdir -p "${TENANT_INSTANCES_ROOT}"
sudo chown -R www-data:www-data storage bootstrap/cache

echo "==> Nginx panel vhost"
PANEL_ALLOW_BLOCK=""
if [[ -n "$PANEL_ALLOWED_IPS" ]]; then
  PANEL_ALLOW_BLOCK=$'    # Panel allowlist\n'
  IFS=',' read -ra ALLOWLIST <<< "$PANEL_ALLOWED_IPS"
  for ip in "${ALLOWLIST[@]}"; do
    ip="$(echo "$ip" | xargs)"
    if [[ -n "$ip" ]]; then
      PANEL_ALLOW_BLOCK="${PANEL_ALLOW_BLOCK}    allow ${ip};"$'\n'
    fi
  done
  PANEL_ALLOW_BLOCK+=$'    deny all;\n'
fi

if [[ "${PANEL_SCHEME}" == "https" ]]; then
  echo "==> Panel SSL configuration"
  PANEL_CERT_DIR="$(dirname "${PANEL_CERT_PATH}")"
  PANEL_CERT="${PANEL_CERT_PATH}"
  PANEL_KEY="${PANEL_KEY_PATH}"
  if [[ "${PANEL_SSL_SELF_SIGNED}" == "true" ]]; then
    sudo mkdir -p "${PANEL_CERT_DIR}"
    if [[ ! -f "${PANEL_CERT}" || ! -f "${PANEL_KEY}" ]]; then
      if [[ "${PANEL_HOST}" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        PANEL_SAN="IP:${PANEL_HOST}"
      else
        PANEL_SAN="DNS:${PANEL_HOST}"
      fi
      sudo openssl req -x509 -nodes -newkey rsa:2048 -days 3650 \
        -keyout "${PANEL_KEY}" \
        -out "${PANEL_CERT}" \
        -subj "/CN=${PANEL_HOST}" \
        -addext "subjectAltName=${PANEL_SAN}"
    fi
  else
    if [[ ! -f "${PANEL_CERT}" || ! -f "${PANEL_KEY}" ]]; then
      echo "Panel cert/key not found. Set PANEL_SSL_SELF_SIGNED=true or provide PANEL_CERT_PATH/PANEL_KEY_PATH."
      exit 1
    fi
  fi

  sudo tee /etc/nginx/sites-available/tastypanel-platform.conf >/dev/null <<CONF
server {
    listen 80;
    server_name ${PANEL_HOST};
    return 301 https://${PANEL_HOST}:${PANEL_PORT}\$request_uri;
}

server {
    listen ${PANEL_PORT} ssl;
    server_name ${PANEL_HOST};
    root ${APP_DIR}/public;
    index index.php index.html;

    client_max_body_size 50M;

    ssl_certificate ${PANEL_CERT};
    ssl_certificate_key ${PANEL_KEY};

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
    }

${PANEL_ALLOW_BLOCK}
}
CONF
else
  sudo tee /etc/nginx/sites-available/tastypanel-platform.conf >/dev/null <<CONF
server {
    listen ${PANEL_PORT};
    server_name ${PANEL_HOST};
    root ${APP_DIR}/public;
    index index.php index.html;

    client_max_body_size 50M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
    }

${PANEL_ALLOW_BLOCK}
}
CONF
fi

sudo ln -sfn /etc/nginx/sites-available/tastypanel-platform.conf /etc/nginx/sites-enabled/tastypanel-platform.conf
sudo nginx -t
sudo systemctl reload nginx

echo "==> Sudoers for platform automation"
sudo APP_DIR="${APP_DIR}" WEB_USER="www-data" "${APP_DIR}/infrastructure/setup-sudoers.sh"

echo "==> Sudoers for instance provisioning"
sudo tee /etc/sudoers.d/tastypanel-instances >/dev/null <<EOF
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/provision-instance.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/deprovision-instance.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/orchestrate-tenant.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/clone-tenant.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/provision-tenant-access.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/sync-tenant-env.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/backup-tenant.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/restore-tenant.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/queue-tenant.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/manage-platform-service.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/deploy-nginx-safe.sh
EOF

echo "==> Sudoers for security/firewall automation"
sudo tee /etc/sudoers.d/tastypanel-security >/dev/null <<EOF
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/security-scan.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/security-audit.sh
www-data ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/firewall-apply.sh
EOF

echo "==> Firewall (ufw) allow panel ports if ufw is installed"
if command -v ufw >/dev/null 2>&1; then
  if [[ "${PANEL_SCHEME}" == "https" ]]; then
    sudo ufw allow 443/tcp || true
    sudo ufw allow 443/udp || true
    sudo ufw allow 80/tcp || true
  fi
  sudo ufw allow ${PANEL_PORT}/tcp || true
fi

echo "==> Scheduler (cron)"
sudo tee /etc/cron.d/tastypanel-schedule >/dev/null <<EOF
* * * * * www-data cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1
EOF

echo "==> Done."
echo "DB_PASSWORD=${DB_PASS}"
echo "Panel installer: ${PANEL_URL}/platform/install"
echo "Next: open installer URL, complete manual steps, then login at ${PANEL_URL}/platform/login."
