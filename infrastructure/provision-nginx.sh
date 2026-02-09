#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${1:-}"
CONFIG_PATH="${2:-}"
MODE="${3:-apply}"
AVAILABLE_DIR_ARG="${4:-}"
ENABLED_DIR_ARG="${5:-}"

if [[ "$MODE" != "apply" && "$MODE" != "test" && "$MODE" != "remove" ]]; then
  AVAILABLE_DIR_ARG="${3:-}"
  ENABLED_DIR_ARG="${4:-}"
  MODE="apply"
fi

if [[ -z "$DOMAIN" ]]; then
  echo "Usage: provision-nginx.sh <domain> <config_path> [apply|test|remove] [available_dir] [enabled_dir]"
  exit 1
fi

AVAILABLE_DIR="${AVAILABLE_DIR_ARG:-${NGINX_AVAILABLE_DIR:-/etc/nginx/sites-available}}"
ENABLED_DIR="${ENABLED_DIR_ARG:-${NGINX_ENABLED_DIR:-/etc/nginx/sites-enabled}}"
TARGET_CONFIG="$AVAILABLE_DIR/$DOMAIN.conf"
TARGET_LINK="$ENABLED_DIR/$DOMAIN.conf"

if [[ "$MODE" != "remove" && -z "$CONFIG_PATH" ]]; then
  echo "Usage: provision-nginx.sh <domain> <config_path> [apply|test|remove] [available_dir] [enabled_dir]"
  exit 1
fi

if [[ "$MODE" != "remove" && ! -f "$CONFIG_PATH" ]]; then
  echo "Config file not found: $CONFIG_PATH"
  exit 1
fi

install -d "$AVAILABLE_DIR" "$ENABLED_DIR"
BACKUP_CONFIG=""
HAD_CONFIG="false"

if [[ "$MODE" == "remove" ]]; then
  if [[ -f "$TARGET_CONFIG" ]]; then
    BACKUP_CONFIG="${TARGET_CONFIG}.bak.$(date +%s)"
    cp "$TARGET_CONFIG" "$BACKUP_CONFIG"
  fi

  rm -f "$TARGET_LINK" "$TARGET_CONFIG"

  if ! nginx -t; then
    echo "nginx -t failed while removing config. Rolling back..."
    if [[ -n "$BACKUP_CONFIG" && -f "$BACKUP_CONFIG" ]]; then
      mv "$BACKUP_CONFIG" "$TARGET_CONFIG"
      ln -sfn "$TARGET_CONFIG" "$TARGET_LINK"
      nginx -t || true
    fi
    exit 1
  fi

  systemctl reload nginx

  if [[ -n "$BACKUP_CONFIG" && -f "$BACKUP_CONFIG" ]]; then
    rm -f "$BACKUP_CONFIG"
  fi

  echo "Nginx config removed for $DOMAIN"
  exit 0
fi

if [[ -f "$TARGET_CONFIG" ]]; then
  BACKUP_CONFIG="${TARGET_CONFIG}.bak.$(date +%s)"
  cp "$TARGET_CONFIG" "$BACKUP_CONFIG"
  HAD_CONFIG="true"
fi

install -m 644 "$CONFIG_PATH" "$TARGET_CONFIG"
ln -sfn "$TARGET_CONFIG" "$ENABLED_DIR/$DOMAIN.conf"

if ! nginx -t; then
  echo "nginx -t failed. Rolling back..."
  if [[ -n "$BACKUP_CONFIG" && -f "$BACKUP_CONFIG" ]]; then
    mv "$BACKUP_CONFIG" "$TARGET_CONFIG"
    ln -sfn "$TARGET_CONFIG" "$ENABLED_DIR/$DOMAIN.conf"
    nginx -t || true
  else
    rm -f "$TARGET_CONFIG" "$ENABLED_DIR/$DOMAIN.conf"
  fi
  exit 1
fi

if [[ "$MODE" == "test" ]]; then
  if [[ "$HAD_CONFIG" == "true" && -n "$BACKUP_CONFIG" && -f "$BACKUP_CONFIG" ]]; then
    mv "$BACKUP_CONFIG" "$TARGET_CONFIG"
    ln -sfn "$TARGET_CONFIG" "$ENABLED_DIR/$DOMAIN.conf"
  else
    rm -f "$TARGET_CONFIG" "$ENABLED_DIR/$DOMAIN.conf"
  fi
  echo "Nginx config test passed for $DOMAIN"
  exit 0
fi

systemctl reload nginx

if [[ -n "$BACKUP_CONFIG" && -f "$BACKUP_CONFIG" ]]; then
  rm -f "$BACKUP_CONFIG"
fi

echo "Nginx provisioned for $DOMAIN"
