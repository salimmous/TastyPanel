#!/usr/bin/env bash
set -euo pipefail

MODE="${1:-status}"
SERVICE_KEY="${2:-}"
ARG="${3:-}"

if [[ -z "${SERVICE_KEY}" ]]; then
  echo "Usage: manage-platform-service.sh <status|action|logs> <service_key> [action|lines]"
  exit 1
fi

declare -A SERVICE_UNITS=(
  ["nginx"]="${PLATFORM_SERVICE_NGINX:-nginx}"
  ["php_fpm"]="${PLATFORM_SERVICE_PHP_FPM:-php8.3-fpm}"
  ["mysql"]="${PLATFORM_SERVICE_DB:-mysql}"
  ["redis"]="${PLATFORM_SERVICE_REDIS:-redis-server}"
  ["queue"]="${PLATFORM_SERVICE_QUEUE:-}"
  ["scheduler"]="${PLATFORM_SERVICE_SCHEDULER:-}"
)

if [[ -z "${SERVICE_UNITS[$SERVICE_KEY]+x}" ]]; then
  echo "Unknown service key: ${SERVICE_KEY}"
  exit 1
fi

UNIT="${SERVICE_UNITS[$SERVICE_KEY]}"

cron_enabled() {
  if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
    return 0
  fi
  if grep -R "artisan schedule:run" /etc/cron.d /etc/crontab >/dev/null 2>&1; then
    return 0
  fi
  return 1
}

queue_running() {
  pgrep -f "artisan queue:work" >/dev/null 2>&1 || pgrep -f "artisan horizon" >/dev/null 2>&1
}

unit_exists() {
  local unit="$1"
  if [[ -z "${unit}" ]]; then
    return 1
  fi
  local load_state
  load_state="$(systemctl show "${unit}" --property=LoadState --value 2>/dev/null || true)"
  [[ -n "${load_state}" && "${load_state}" != "not-found" ]]
}

service_status() {
  echo "SERVICE_KEY=${SERVICE_KEY}"
  echo "UNIT=${UNIT}"

  if [[ -z "${UNIT}" ]]; then
    if [[ "${SERVICE_KEY}" == "queue" ]]; then
      if queue_running; then
        echo "MANAGED=false"
        echo "STATE=active"
        echo "DETAIL=Queue process detected (pgrep)"
      else
        echo "MANAGED=false"
        echo "STATE=inactive"
        echo "DETAIL=No queue worker process detected"
      fi
      return 0
    fi
    if [[ "${SERVICE_KEY}" == "scheduler" ]]; then
      if cron_enabled; then
        echo "MANAGED=false"
        echo "STATE=active"
        echo "DETAIL=Scheduler cron entry found"
      else
        echo "MANAGED=false"
        echo "STATE=inactive"
        echo "DETAIL=No scheduler cron entry found"
      fi
      return 0
    fi
    echo "MANAGED=false"
    echo "STATE=unknown"
    echo "DETAIL=No unit configured"
    return 0
  fi

  if ! unit_exists "${UNIT}"; then
    echo "MANAGED=false"
    echo "STATE=missing"
    echo "DETAIL=systemd unit not found"
    return 0
  fi

  local state
  state="$(systemctl is-active "${UNIT}" 2>/dev/null || true)"
  if [[ -z "${state}" ]]; then
    state="unknown"
  fi

  echo "MANAGED=true"
  echo "STATE=${state}"
  echo "DETAIL=systemd-managed"
}

service_action() {
  local action="${ARG:-}"
  if [[ "${action}" != "start" && "${action}" != "stop" && "${action}" != "restart" ]]; then
    echo "Invalid action. Allowed: start|stop|restart"
    exit 1
  fi

  if [[ -z "${UNIT}" ]]; then
    echo "Service '${SERVICE_KEY}' does not have a systemd unit configured."
    exit 2
  fi

  if ! unit_exists "${UNIT}"; then
    echo "Unit not found: ${UNIT}"
    exit 2
  fi

  systemctl "${action}" "${UNIT}"
  echo "Action '${action}' executed for ${UNIT}"
  systemctl is-active "${UNIT}" 2>/dev/null || true
}

service_logs() {
  local lines="${ARG:-120}"
  if ! [[ "${lines}" =~ ^[0-9]+$ ]]; then
    lines=120
  fi
  if (( lines < 10 )); then
    lines=10
  fi
  if (( lines > 500 )); then
    lines=500
  fi

  if [[ -z "${UNIT}" ]]; then
    if [[ "${SERVICE_KEY}" == "queue" ]]; then
      if [[ -f "/var/www/tastypanel/storage/logs/laravel.log" ]]; then
        tail -n "${lines}" "/var/www/tastypanel/storage/logs/laravel.log"
        exit 0
      fi
      echo "No unit configured and laravel.log not found."
      exit 2
    fi
    echo "No logs source configured for ${SERVICE_KEY}."
    exit 2
  fi

  if ! unit_exists "${UNIT}"; then
    echo "Unit not found: ${UNIT}"
    exit 2
  fi

  journalctl -u "${UNIT}" -n "${lines}" --no-pager
}

case "${MODE}" in
  status)
    service_status
    ;;
  action)
    service_action
    ;;
  logs)
    service_logs
    ;;
  *)
    echo "Unknown mode: ${MODE}"
    echo "Usage: manage-platform-service.sh <status|action|logs> <service_key> [action|lines]"
    exit 1
    ;;
esac
