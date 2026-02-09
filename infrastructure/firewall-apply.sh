#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-}"
PROTO="${2:-}"
PORT="${3:-}"
SOURCE="${4:-}"
COMMENT="${5:-}"

if [[ -z "$ACTION" || -z "$PROTO" || -z "$PORT" ]]; then
  echo "Usage: firewall-apply.sh <allow|deny> <tcp|udp> <port|range> [source] [comment]"
  exit 1
fi

if [[ -n "$SOURCE" ]]; then
  if [[ -n "$COMMENT" ]]; then
    ufw "$ACTION" from "$SOURCE" to any port "$PORT" proto "$PROTO" comment "$COMMENT"
  else
    ufw "$ACTION" from "$SOURCE" to any port "$PORT" proto "$PROTO"
  fi
else
  if [[ -n "$COMMENT" ]]; then
    ufw "$ACTION" "$PORT/$PROTO" comment "$COMMENT"
  else
    ufw "$ACTION" "$PORT/$PROTO"
  fi
fi
