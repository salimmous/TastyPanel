#!/usr/bin/env bash
# Provision tenant instance: clone Laravel patch (TENANT_APP_REPO = theme + dashboard), DB, composer, migrate.
# See documentation/TENANT-WORKFLOW.md
set -euo pipefail

SITE_KEY="${1:-}"
ROOT_DIR="${2:-}"
REPO_URL="${3:-}"
REPO_BRANCH="${4:-main}"
DB_NAME="${5:-}"
DB_USER="${6:-}"
DB_PASS="${7:-}"
PHP_VERSION="${8:-8.3}"
APP_URL="${9:-http://localhost}"
SYSTEM_USER="${10:-}"
ADMIN_EMAIL="${11:-}"
ADMIN_USER="${12:-}"
ADMIN_PASS="${13:-}"
FPM_PM_MAX_CHILDREN="${FPM_PM_MAX_CHILDREN:-10}"
FPM_PM_START_SERVERS="${FPM_PM_START_SERVERS:-2}"
FPM_PM_MIN_SPARE_SERVERS="${FPM_PM_MIN_SPARE_SERVERS:-2}"
FPM_PM_MAX_SPARE_SERVERS="${FPM_PM_MAX_SPARE_SERVERS:-5}"
FPM_PM_MAX_REQUESTS="${FPM_PM_MAX_REQUESTS:-500}"
FPM_MEMORY_LIMIT_MB="${FPM_MEMORY_LIMIT_MB:-256}"

usage() {
  echo "Usage: provision-instance.sh <site_key> <root_dir> <repo_url> <repo_branch> <db_name> <db_user> <db_pass> <php_version> <app_url> <system_user> [admin_email] [admin_user] [admin_pass]"
}

if [[ -z "${SITE_KEY}" || -z "${ROOT_DIR}" || -z "${REPO_URL}" || -z "${DB_NAME}" || -z "${DB_USER}" || -z "${DB_PASS}" ]]; then
  usage
fi

if [[ -z "${SYSTEM_USER}" ]]; then
  SYSTEM_USER="tbapp_${SITE_KEY//[^a-zA-Z0-9]/_}"
fi

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

echo "==> Provisioning instance ${SITE_KEY}"

mkdir -p "${ROOT_DIR}"

if ! id -u "${SYSTEM_USER}" >/dev/null 2>&1; then
  useradd --system --create-home --shell /usr/sbin/nologin "${SYSTEM_USER}"
fi

if [[ "${REPO_URL}" == "default" ]]; then
  echo "==> initializing default project"
  mkdir -p "${ROOT_DIR}/public"
  cat > "${ROOT_DIR}/public/index.php" <<PHP
<?php
// Site initialized by TastyPanel
?>
$(cat /var/www/tastypanel/resources/views/placeholders/default-index.html)
PHP
  # Create basic structure usually needed
  mkdir -p "${ROOT_DIR}/storage/logs"
  mkdir -p "${ROOT_DIR}/bootstrap/cache"
elif [[ ! -d "${ROOT_DIR}/.git" ]]; then
  echo "==> Cloning tenant app repo"
  git clone --branch "${REPO_BRANCH}" "${REPO_URL}" "${ROOT_DIR}"
else
  echo "==> Updating tenant app repo"
  cd "${ROOT_DIR}"
  git fetch --all --prune
  git checkout "${REPO_BRANCH}"
  # Using full path to git to avoid alias issues, though not usually a problem in scripts
  /usr/bin/git pull origin "${REPO_BRANCH}"
fi

cd "${ROOT_DIR}"

if [[ "${REPO_URL}" != "default" ]]; then
  if [[ ! -f .env ]]; then
    cp .env.example .env
  fi

  echo "==> Configuring .env"
  set_env "APP_ENV" "production" ".env"
  set_env "APP_DEBUG" "false" ".env"
  set_env "APP_URL" "${APP_URL}" ".env"
  set_env "APP_MODE" "tenant" ".env"
  set_env "TENANT_MODE" "true" ".env"
  set_env "TENANT_LOCK_ID" "" ".env"
  set_env "VITE_APP_MODE" "tenant" ".env"
  set_env "DB_CONNECTION" "mysql" ".env"
  set_env "DB_HOST" "127.0.0.1" ".env"
  set_env "DB_PORT" "3306" ".env"
  set_env "DB_DATABASE" "${DB_NAME}" ".env"
  set_env "DB_USERNAME" "${DB_USER}" ".env"
  set_env "DB_PASSWORD" "${DB_PASS}" ".env"

  echo "==> Creating database"
  mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
  mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

  echo "==> Installing dependencies"
  composer install --no-dev --optimize-autoloader

  if [[ -f package.json ]]; then
    npm install
    npm run build
  fi

  echo "==> Laravel setup"
  if ! grep -qE "^APP_KEY=" ".env" || [[ -z "$(grep -E '^APP_KEY=' .env | cut -d= -f2-)" ]]; then
    php artisan key:generate --force
  fi

  # Ensure storage permissions before migration
  chown -R "${SYSTEM_USER}:www-data" "${ROOT_DIR}"
  chmod -R 775 "${ROOT_DIR}/storage" "${ROOT_DIR}/bootstrap/cache"

  php artisan migrate --force
  php artisan storage:link || true

  if [[ -n "${ADMIN_EMAIL}" ]]; then
    echo "==> Creating admin user: ${ADMIN_EMAIL}"
    # Use strict error handling inside tinker code block
    sudo -u "${SYSTEM_USER}" php artisan tinker --execute="
      try {
          \$user = \App\Models\User::where('email', '${ADMIN_EMAIL}')->first();
          if (!\$user) {
              \App\Models\User::create([
                  'name' => '${ADMIN_USER:-Admin}',
                  'email' => '${ADMIN_EMAIL}',
                  'password' => \Illuminate\Support\Facades\Hash::make('${ADMIN_PASS:-password}'),
                  'email_verified_at' => now(),
              ]);
              echo 'Admin user created successfully.';
          } else {
              echo 'Admin user already exists.';
          }
      } catch (\Throwable \$e) {
          echo 'Error creating admin user: ' . \$e->getMessage();
          exit(1);
      }
    "
  fi

else
    # Minimal Setup for default
    echo "==> Minimal setup for default repo"
    
    echo "==> Creating database"
    mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"
fi

echo "==> PHP-FPM pool"
mkdir -p "${ROOT_DIR}/storage/logs"
touch "${ROOT_DIR}/storage/logs/php-fpm.log"
chown -R "${SYSTEM_USER}:www-data" "${ROOT_DIR}/storage/logs" || true
FPM_POOL="/etc/php/${PHP_VERSION}/fpm/pool.d/${SITE_KEY}.conf"
SOCKET="/run/php/php${PHP_VERSION}-fpm-${SITE_KEY}.sock"
cat > "${FPM_POOL}" <<INI
[${SITE_KEY}]
user = ${SYSTEM_USER}
group = ${SYSTEM_USER}
listen = ${SOCKET}
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = ${FPM_PM_MAX_CHILDREN}
pm.start_servers = ${FPM_PM_START_SERVERS}
pm.min_spare_servers = ${FPM_PM_MIN_SPARE_SERVERS}
pm.max_spare_servers = ${FPM_PM_MAX_SPARE_SERVERS}
pm.max_requests = ${FPM_PM_MAX_REQUESTS}
php_admin_value[error_log] = ${ROOT_DIR}/storage/logs/php-fpm.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = ${FPM_MEMORY_LIMIT_MB}M
chdir = /
INI

systemctl restart "php${PHP_VERSION}-fpm" || service "php${PHP_VERSION}-fpm" restart

echo "==> Permissions"
chown -R "${SYSTEM_USER}:www-data" "${ROOT_DIR}"
chmod -R 775 "${ROOT_DIR}/storage" "${ROOT_DIR}/bootstrap/cache"

echo "==> Instance provisioned"
