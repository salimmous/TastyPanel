#!/usr/bin/env bash
# Add phpMyAdmin location block to the panel Nginx config (for existing installs).
# Run on the server: sudo /var/www/tastypanel/infrastructure/add-phpmyadmin-nginx.sh

set -euo pipefail

CONF="${NGINX_PANEL_CONF:-/etc/nginx/sites-available/tastypanel-platform.conf}"

if [[ ! -f "$CONF" ]]; then
  echo "Config not found: $CONF. Set NGINX_PANEL_CONF if your file is elsewhere."
  exit 1
fi

if grep -q 'location /phpmyadmin' "$CONF"; then
  echo "phpMyAdmin location already present in $CONF. Nothing to do."
  exit 0
fi

# Detect PHP-FPM socket (e.g. php8.3-fpm.sock or php8.4-fpm.sock)
PHP_SOCKET=""
for s in /run/php/php*-fpm.sock; do
  [[ -S "$s" ]] && PHP_SOCKET="$s" && break
done
if [[ -z "$PHP_SOCKET" ]]; then
  echo "No PHP-FPM socket found under /run/php/. Install php-fpm first."
  exit 1
fi
PHP_SOCKET="${PHP_SOCKET#/run/php/}"

# Block to insert (before first "location / {")
read -r -d '' BLOCK <<BLOCK || true

    # phpMyAdmin (added by add-phpmyadmin-nginx.sh)
    location /phpmyadmin/ {
        alias /usr/share/phpmyadmin/;
        index index.php;
        location ~ \\\\.php\\\$ {
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME \\\$request_filename;
            fastcgi_pass unix:/run/php/${PHP_SOCKET};
        }
        location ~* \\\\.(ht|git|ini)\\\$ { deny all; }
    }

BLOCK

# Insert before the first "location / {" (with optional spaces)
# Use a temp file and awk/sed to avoid escaping issues
TMP=$(mktemp)
awk -v block="$BLOCK" '
  /location \/ \{/ && !done {
    print block
    done=1
  }
  { print }
' "$CONF" > "$TMP"
mv "$TMP" "$CONF"

echo "Added phpMyAdmin location to $CONF (PHP socket: $PHP_SOCKET)."
if nginx -t 2>/dev/null; then
  systemctl reload nginx || service nginx reload
  echo "Nginx reloaded. Open /phpmyadmin/ on your panel URL."
else
  echo "nginx -t failed. Check $CONF and run: sudo nginx -t"
  exit 1
fi
