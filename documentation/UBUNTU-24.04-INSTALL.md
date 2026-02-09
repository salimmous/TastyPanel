# TastyPanel Install (Ubuntu 24.04)

This installs TastyPanel on a clean Ubuntu 24.04 VPS using Laravel Blade admin pages, MySQL, and Nginx.  
Default panel access is HTTP on custom port and starts with the manual install wizard:

`http://<SERVER_IP>:<PORT>/platform/install`

## Deployment rule (important)

- Platform is principal on VPS:
- `/var/www/tastypanel` = control panel source code and runtime.
- `/var/www/tastypanel-sites/<tenant-key>` = tenant site instances only.
- Keep strict separation: no tenant files inside platform folder.

## 1) SSH into the VPS

```bash
ssh root@your_ip_address
```

## 2) Update system

```bash
apt update && apt -y upgrade && apt -y install curl wget sudo git
```

## 3) Clone and run installer

```bash
export REPO_URL="https://github.com/your/repo.git"
export APP_DIR="/var/www/tastypanel"
export PANEL_HOST="84.247.160.84"
export PANEL_SCHEME="http"
export PANEL_PORT="8080"
export PANEL_ALLOWED_IPS=""         # optional, comma-separated
export DB_NAME="tastypanel"
export DB_USER="tastypanel"
export DB_PASS="change_this_password"
export TENANT_INSTANCES_ROOT="/var/www/tastypanel-sites"
export TENANT_PHP_VERSION="8.3"
export AUTO_NGINX="true"
export AUTO_PROVISION_ON_TENANT_CREATE="false"
export FRONTEND_AUTO="false"

sudo mkdir -p "$APP_DIR"
sudo chown -R "$USER:$USER" "$APP_DIR"
git clone "$REPO_URL" "$APP_DIR"
bash "$APP_DIR/infrastructure/install-ubuntu-24.04.sh"
```

Installer behavior:

- Installs PHP, MySQL, Nginx, Composer, certbot tools, security packages.
- Does not build Node/React assets for platform admin.
- Configures `.env` in platform mode (`APP_MODE=platform`, `TENANT_MODE=false`).
- Configures Nginx panel vhost on your selected port.
- Sets cron for Laravel scheduler.
- Keeps tenant provisioning disabled by default on tenant creation.

## 4) Open the manual install page

```text
http://84.247.160.84:8080/platform/install
```

On this page:

1. Review environment checks (`.env`, key, DB, migrations).
2. Create superadmin account.
3. Set panel scheme/host/port.
4. Finish install and continue to `/platform/login`.

## 5) First login

```text
http://84.247.160.84:8080/platform/login
```

After login, platform dashboard is at:

```text
http://84.247.160.84:8080/platform/dashboard
```

## 6) Tenant creation mode (important)

Default behavior for new sites:

- tenant record is created
- primary domain is created in pending state
- no theme is auto-installed
- no full provisioning is auto-triggered

If you want queued auto-provisioning later, set:

```env
AUTO_PROVISION_ON_TENANT_CREATE=true
```

## 7) Optional HTTPS panel mode

If you want panel HTTPS at install time:

```bash
export PANEL_SCHEME="https"
export PANEL_PORT="8443"
export PANEL_SSL_SELF_SIGNED="true"   # or false with real cert/key paths
```

Then open:

```text
https://<SERVER_IP>:8443/platform/install
```

## 8) Post-install checks

Scheduler:

```cron
* * * * * cd /var/www/tastypanel && php artisan schedule:run >> /dev/null 2>&1
```

Preflight:

```bash
cd /var/www/tastypanel
./infrastructure/preflight-prod.sh
```

Optional smoke flow:

```bash
sudo /var/www/tastypanel/infrastructure/smoke-test-tenant.sh flow
```

## 9) SSH/SFTP isolation per tenant

Tenant access is managed with:

- `TENANT_ACCESS_SCRIPT=/var/www/tastypanel/infrastructure/provision-tenant-access.sh`
- `TENANT_ACCESS_AUTH_MODE` (`both`, `keys`, `password`)
- `TENANT_ACCESS_SFTP_ONLY` (`true` or `false`)

Each tenant can have its own Linux access user and isolated file permissions.
