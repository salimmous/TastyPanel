#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/tastypanel}"
WEB_USER="${WEB_USER:-www-data}"
SUDOERS_FILE="/etc/sudoers.d/tastypanel-platform"

cat <<EOF >/tmp/tastypanel-platform.sudoers
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/provision-nginx.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/provision-instance.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/deprovision-instance.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/clone-tenant.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/orchestrate-tenant.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/provision-frontend.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/deprovision-frontend.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/provision-tenant-access.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/manage-tenant-mailbox.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/sync-tenant-env.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/manage-platform-service.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/deploy-nginx-safe.sh
${WEB_USER} ALL=(root) NOPASSWD:${APP_DIR}/infrastructure/install-tenant-app.sh
EOF

install -m 440 /tmp/tastypanel-platform.sudoers "${SUDOERS_FILE}"
visudo -cf "${SUDOERS_FILE}"
rm -f /tmp/tastypanel-platform.sudoers

echo "Sudoers configured: ${SUDOERS_FILE}"
