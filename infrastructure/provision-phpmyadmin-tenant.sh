#!/usr/bin/env bash
# Provision per-tenant phpMyAdmin: MySQL user, Nginx vhost, basic auth.
# Usage: provision-phpmyadmin-tenant.sh <tenant_slug> <primary_domain> <db_name>
# Env: MYSQL_ROOT_PASSWORD (optional), PMA_PHP_FPM_SOCK (default /run/php/php8.3-fpm.sock)

set -euo pipefail

TENANT_SLUG="${1:-}"
PRIMARY_DOMAIN="${2:-}"
DB_NAME="${3:-}"
PMA_PHP_FPM_SOCK="${PMA_PHP_FPM_SOCK:-/run/php/php8.3-fpm.sock}"
PMA_ROOT="${PMA_ROOT:-/usr/share/phpmyadmin}"
NGINX_AVAILABLE="${NGINX_AVAILABLE:-/etc/nginx/sites-available}"
NGINX_ENABLED="${NGINX_ENABLED:-/etc/nginx/sites-enabled}"
HTPASSWD_DIR="${HTPASSWD_DIR:-/etc/nginx}"

usage() {
  echo "Usage: provision-phpmyadmin-tenant.sh <tenant_slug> <primary_domain> <db_name>"
  echo "  tenant_slug   e.g. my-site"
  echo "  primary_domain e.g. example.com (used as server_name pma.example.com)"
  echo "  db_name       tenant MySQL database name"
}

if [[ -z "${TENANT_SLUG}" || -z "${PRIMARY_DOMAIN}" || -z "${DB_NAME}" ]]; then
  usage
  exit 1
fi

# Sanitize slug for filenames and MySQL user
SAFE_SLUG="${TENANT_SLUG//[^a-zA-Z0-9_-]/}"
if [[ -z "${SAFE_SLUG}" ]]; then
  echo "Invalid tenant_slug (must contain alphanumeric, - or _)."
  exit 1
fi

PMA_USER="pma_${SAFE_SLUG}"
PMA_DB_PASS="$(openssl rand -base64 18 | tr -d '\n')"
PMA_WEB_PASS="$(openssl rand -base64 12 | tr -d '\n')"
SERVER_NAME="pma.${PRIMARY_DOMAIN}"
CONF_FILE="${NGINX_AVAILABLE}/pma-${SAFE_SLUG}.conf"
HTPASSWD_FILE="${HTPASSWD_DIR}/pma-${SAFE_SLUG}.htpasswd"

# MySQL: create user limited to tenant DB only (use MYSQL_PWD to avoid password in argv)
mysql_cmd() {
  if [[ -n "${MYSQL_ROOT_PASSWORD:-}" ]]; then
    export MYSQL_PWD="${MYSQL_ROOT_PASSWORD}"
  fi
  mysql -u root "$@"
  local ret=$?
  unset MYSQL_PWD 2>/dev/null || true
  return $ret
}

echo "Creating MySQL user ${PMA_USER} for database ${DB_NAME}..."
mysql_cmd <<SQL
CREATE USER IF NOT EXISTS '${PMA_USER}'@'localhost' IDENTIFIED BY '${PMA_DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${PMA_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# Nginx vhost
echo "Writing Nginx vhost ${CONF_FILE}..."
cat > "${CONF_FILE}" <<NGINX
server {
    listen 80;
    server_name ${SERVER_NAME};

    auth_basic "phpMyAdmin";
    auth_basic_user_file ${HTPASSWD_FILE};

    root ${PMA_ROOT};
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PMA_PHP_FPM_SOCK};
    }

    location ~* \\.(ht|git) { deny all; }
}
NGINX

# Basic auth (htpasswd)
if ! command -v htpasswd >/dev/null 2>&1; then
  echo "htpasswd not found. Install apache2-utils: apt install apache2-utils"
  exit 1
fi
mkdir -p "$(dirname "${HTPASSWD_FILE}")"
htpasswd -cb "${HTPASSWD_FILE}" "admin" "${PMA_WEB_PASS}"
chown www-data:www-data "${HTPASSWD_FILE}" 2>/dev/null || true

# Enable site
if [[ ! -L "${NGINX_ENABLED}/pma-${SAFE_SLUG}.conf" ]]; then
  ln -sf "${CONF_FILE}" "${NGINX_ENABLED}/pma-${SAFE_SLUG}.conf"
fi
nginx -t
systemctl reload nginx

# Output for platform (passwords shown once)
echo "PMA_USER=${PMA_USER}"
echo "PMA_DB_PASS=${PMA_DB_PASS}"
echo "PMA_WEB_USER=admin"
echo "PMA_WEB_PASS=${PMA_WEB_PASS}"
echo "PMA_URL=https://${SERVER_NAME}"
echo "PROVISIONED=1"
