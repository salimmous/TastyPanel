#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-provision}"
TENANT_KEY="${2:-}"
TENANT_ROOT="${3:-}"
SSH_USER="${4:-}"
SSH_PORT="${SSH_PORT:-22}"
SSH_PUBLIC_KEY="${SSH_PUBLIC_KEY:-}"
TENANT_ACCESS_AUTH_MODE="${TENANT_ACCESS_AUTH_MODE:-both}"
TENANT_ACCESS_SFTP_ONLY="${TENANT_ACCESS_SFTP_ONLY:-0}"
TENANT_ACCESS_ALLOWED_ROOT="${TENANT_ACCESS_ALLOWED_ROOT:-${TENANT_INSTANCES_ROOT:-}}"
TENANT_ACCESS_RELOAD_SSHD="${TENANT_ACCESS_RELOAD_SSHD:-1}"
TENANT_ACCESS_SSHD_DIR="${TENANT_ACCESS_SSHD_DIR:-/etc/ssh/sshd_config.d}"
SSHD_MATCH_FILE="${TENANT_ACCESS_SSHD_DIR}/99-tastypanel-${SSH_USER}.conf"

usage() {
  echo "Usage: provision-tenant-access.sh <provision|rotate-password|install-key|remove> <tenant_key> <tenant_root> <ssh_user>"
}

if [[ -z "${TENANT_KEY}" || -z "${TENANT_ROOT}" || -z "${SSH_USER}" ]]; then
  usage
  exit 1
fi

if [[ ! "${TENANT_ACCESS_AUTH_MODE}" =~ ^(keys|password|both)$ ]]; then
  echo "TENANT_ACCESS_AUTH_MODE must be one of: keys,password,both"
  exit 1
fi

if [[ "${ACTION}" != "remove" && ! -d "${TENANT_ROOT}" ]]; then
  echo "Tenant root not found: ${TENANT_ROOT}"
  exit 1
fi

if [[ -n "${TENANT_ACCESS_ALLOWED_ROOT}" && "${ACTION}" != "remove" ]]; then
  tenant_root_real="$(cd "${TENANT_ROOT}" 2>/dev/null && pwd -P || true)"
  allowed_root_real="$(cd "${TENANT_ACCESS_ALLOWED_ROOT}" 2>/dev/null && pwd -P || true)"
  if [[ -z "${tenant_root_real}" || -z "${allowed_root_real}" ]]; then
    echo "Unable to resolve tenant/allowed root path."
    exit 1
  fi
  if [[ "${tenant_root_real}" != "${allowed_root_real}" && "${tenant_root_real}" != "${allowed_root_real}/"* ]]; then
    echo "Tenant root is outside allowed root (${TENANT_ACCESS_ALLOWED_ROOT})."
    exit 1
  fi
fi

generate_password() {
  openssl rand -base64 18 | tr -d '\n'
}

ensure_user() {
  if ! id -u "${SSH_USER}" >/dev/null 2>&1; then
    useradd --create-home --shell /bin/bash "${SSH_USER}"
  fi
}

ensure_acl() {
  if command -v setfacl >/dev/null 2>&1; then
    setfacl -R -m "u:${SSH_USER}:rwX" "${TENANT_ROOT}"
    # Default ACL entries are only valid on directories.
    find "${TENANT_ROOT}" -type d -exec setfacl -m "d:u:${SSH_USER}:rwX" {} +
  else
    # Fallback: Add user to the group owning the directory
    local group_owner
    if [[ -d "${TENANT_ROOT}" ]]; then
        group_owner=$(stat -c '%G' "${TENANT_ROOT}")
        if [[ -n "${group_owner}" ]]; then
            usermod -aG "${group_owner}" "${SSH_USER}"
        fi
    fi
  fi
}

ensure_ssh_dir() {
  local ssh_dir="/home/${SSH_USER}/.ssh"
  local auth_file="${ssh_dir}/authorized_keys"
  mkdir -p "${ssh_dir}"
  touch "${auth_file}"
  chown -R "${SSH_USER}:${SSH_USER}" "${ssh_dir}"
  chmod 700 "${ssh_dir}"
  chmod 600 "${auth_file}"
}

set_password() {
  local password="$1"
  echo "${SSH_USER}:${password}" | chpasswd
}

password_auth_enabled() {
  [[ "${TENANT_ACCESS_AUTH_MODE}" == "password" || "${TENANT_ACCESS_AUTH_MODE}" == "both" ]]
}

pubkey_auth_enabled() {
  [[ "${TENANT_ACCESS_AUTH_MODE}" == "keys" || "${TENANT_ACCESS_AUTH_MODE}" == "both" ]]
}

ensure_sshd_match_block() {
  mkdir -p "${TENANT_ACCESS_SSHD_DIR}"

  local password_auth="no"
  local pubkey_auth="no"
  local permit_tty="yes"
  local force_command=""
  if password_auth_enabled; then
    password_auth="yes"
  fi
  if pubkey_auth_enabled; then
    pubkey_auth="yes"
  fi
  if [[ "${TENANT_ACCESS_SFTP_ONLY}" == "1" ]]; then
    permit_tty="no"
    force_command="ForceCommand internal-sftp -d ${TENANT_ROOT}"
  fi

  cat >"${SSHD_MATCH_FILE}" <<EOF
Match User ${SSH_USER}
    PubkeyAuthentication ${pubkey_auth}
    PasswordAuthentication ${password_auth}
    KbdInteractiveAuthentication no
    PermitEmptyPasswords no
    X11Forwarding no
    AllowTcpForwarding no
    AllowAgentForwarding no
    PermitTunnel no
    PermitTTY ${permit_tty}
$( [[ -n "${force_command}" ]] && echo "    ${force_command}" )
EOF

  chmod 600 "${SSHD_MATCH_FILE}"
  reload_sshd_if_needed
}

reload_sshd_if_needed() {
  if [[ "${TENANT_ACCESS_RELOAD_SSHD}" != "1" ]]; then
    return 0
  fi

  if command -v sshd >/dev/null 2>&1; then
    sshd -t
  fi

  if command -v systemctl >/dev/null 2>&1; then
    systemctl reload ssh 2>/dev/null || systemctl reload sshd 2>/dev/null || true
  elif command -v service >/dev/null 2>&1; then
    service ssh reload 2>/dev/null || service sshd reload 2>/dev/null || true
  fi
}

provision_access() {
  ensure_user
  ensure_acl
  ensure_ssh_dir
  ensure_sshd_match_block

  local home_dir="/home/${SSH_USER}"
  local link_path="${home_dir}/site"
  ln -sfn "${TENANT_ROOT}" "${link_path}"
  chown -h "${SSH_USER}:${SSH_USER}" "${link_path}"

  local password=""
  if password_auth_enabled; then
    password="$(generate_password)"
    set_password "${password}"
  fi

  echo "SSH_USER=${SSH_USER}"
  echo "SSH_HOME=${home_dir}"
  echo "SSH_SITE_PATH=${TENANT_ROOT}"
  echo "SSH_PORT=${SSH_PORT}"
  echo "TEMP_PASSWORD=${password}"
  echo "AUTH_MODE=${TENANT_ACCESS_AUTH_MODE}"
  echo "SFTP_ONLY=${TENANT_ACCESS_SFTP_ONLY}"
}

rotate_password() {
  ensure_user
  if ! password_auth_enabled; then
    echo "SSH_USER=${SSH_USER}"
    echo "SSH_PORT=${SSH_PORT}"
    echo "TEMP_PASSWORD="
    echo "AUTH_MODE=${TENANT_ACCESS_AUTH_MODE}"
    echo "PASSWORD_AUTH_DISABLED=true"
    return 0
  fi

  local password
  password="$(generate_password)"
  set_password "${password}"

  echo "SSH_USER=${SSH_USER}"
  echo "SSH_PORT=${SSH_PORT}"
  echo "TEMP_PASSWORD=${password}"
  echo "AUTH_MODE=${TENANT_ACCESS_AUTH_MODE}"
}

install_key() {
  ensure_user
  ensure_acl
  ensure_ssh_dir

  if [[ -z "${SSH_PUBLIC_KEY}" ]]; then
    echo "SSH_PUBLIC_KEY is required for install-key action."
    exit 1
  fi

  if [[ ! "${SSH_PUBLIC_KEY}" =~ ^ssh-(ed25519|rsa|ecdsa) ]]; then
    echo "SSH_PUBLIC_KEY format is invalid."
    exit 1
  fi

  local auth_file="/home/${SSH_USER}/.ssh/authorized_keys"
  if ! grep -qxF "${SSH_PUBLIC_KEY}" "${auth_file}"; then
    printf '%s\n' "${SSH_PUBLIC_KEY}" >> "${auth_file}"
  fi

  chown "${SSH_USER}:${SSH_USER}" "${auth_file}"
  chmod 600 "${auth_file}"
  ensure_sshd_match_block

  echo "SSH_USER=${SSH_USER}"
  echo "KEY_INSTALLED=true"
  echo "AUTH_MODE=${TENANT_ACCESS_AUTH_MODE}"
  echo "SFTP_ONLY=${TENANT_ACCESS_SFTP_ONLY}"
}

remove_access() {
  if command -v setfacl >/dev/null 2>&1 && [[ -d "${TENANT_ROOT}" ]]; then
    setfacl -R -x "u:${SSH_USER}" "${TENANT_ROOT}" || true
    find "${TENANT_ROOT}" -type d -exec setfacl -x "d:u:${SSH_USER}" {} + || true
  fi

  if [[ -f "${SSHD_MATCH_FILE}" ]]; then
    rm -f "${SSHD_MATCH_FILE}"
    reload_sshd_if_needed
  fi

  if id -u "${SSH_USER}" >/dev/null 2>&1; then
    userdel -r "${SSH_USER}" >/dev/null 2>&1 || userdel "${SSH_USER}" >/dev/null 2>&1 || true
  fi

  echo "SSH_USER=${SSH_USER}"
  echo "REMOVED=true"
}

case "${ACTION}" in
  provision)
    provision_access
    ;;
  rotate-password)
    rotate_password
    ;;
  install-key)
    install_key
    ;;
  remove)
    remove_access
    ;;
  *)
    usage
    exit 1
    ;;
esac
