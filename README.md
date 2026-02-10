# TastyPanel

Multi-tenant Laravel control panel for managing isolated tenant sites.

## What this repo is now

- `TastyPanel` is platform-first: install opens at `/platform/install`, then login at `/platform/login`.
- Admin UI is Laravel Blade + CSS (no React/Node runtime required for platform admin pages).
- Site creation from platform is manual by default: no auto theme assignment and no auto provisioning unless explicitly enabled.
- Tenant sites stay isolated (`.env`, DB, files, logs, PHP-FPM pool, access user).

## Principal deployment model (VPS)

- `TastyPanel` is the principal and only control-plane app on the server.
- Deploy platform code in: `/var/www/tastypanel`
- Tenant projects are separate runtime instances in: `/var/www/tastypanel-sites/<tenant-key>`
- Never mix tenant code/files into `/var/www/tastypanel` (platform stays clean and stable).

## Quick start (Ubuntu VPS)

Full guide: `documentation/UBUNTU-24.04-INSTALL.md`

```bash
export REPO_URL="https://github.com/your/repo.git"
export APP_DIR="/var/www/tastypanel"
export PANEL_HOST="84.247.160.84"
export PANEL_SCHEME="http"
export PANEL_PORT="8080"
export DB_NAME="tastypanel"
export DB_USER="tastypanel"
export DB_PASS="change_this_password"

git clone "$REPO_URL" "$APP_DIR"
bash "$APP_DIR/infrastructure/install-ubuntu-24.04.sh"
```

Open:

```text
http://84.247.160.84:8080/platform/install
```

The installer page shows manual steps and environment checks, then creates the superadmin account.

## Architecture

### Platform app

```text
/var/www/tastypanel
```

Key folders:

- `app/` Laravel core code
- `routes/` platform + API routes
- `resources/views/` Blade UI
- `infrastructure/` provisioning/ops scripts
- `documentation/` operational guides
- `storage/` backups/log/runtime files

### Tenant instances

```text
/var/www/tastypanel-sites/<tenant-key>
```

Each tenant can have independent:

- runtime user
- `.env`
- MySQL DB
- storage path
- Nginx vhost
- PHP-FPM pool
- SSH/SFTP access policy

## Defaults that matter

- `AUTO_PROVISION_ON_TENANT_CREATE=false`
- `FRONTEND_AUTO=false`
- `APP_MODE=platform`
- `TENANT_MODE=false`

This means creating a tenant from platform does not auto-install a theme or deploy files unless you turn provisioning on.

## Tenant workflow (Laravel patch)

Create site → provision instance (Laravel clone) → admin access (SSH/SFTP) → setup site. Full order and scripts: **`documentation/TENANT-WORKFLOW.md`**. See also `documentation/TENANT-APP-REPO.md` for the tenant app template (theme + dashboard).

## Documentation index

- `documentation/UBUNTU-24.04-INSTALL.md` VPS installation
- `documentation/TENANT-WORKFLOW.md` tenant workflow (Laravel patch, provision, admin access)
- `documentation/TENANT-APP-REPO.md` tenant app repo (theme + dashboard)
- `documentation/PLATFORM-PRO-CHECKLIST.md` platform pro — all modules connected, config checklist, verification
- `documentation/CONTROL-PANEL.md` control panel capabilities
- `documentation/nginx/NGINX-SETUP.md` Nginx details
- `documentation/PROD-CHECKLIST.md` go-live checks
- `documentation/PERFORMANCE.md` performance checklist

## Local development (platform only)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Optional legacy frontend assets can remain in repo, but they are not required for platform-first Blade mode.

## Operations

- Scheduler:

```cron
* * * * * cd /var/www/tastypanel && php artisan schedule:run >> /dev/null 2>&1
```

- Preflight before launch:

```bash
cd /var/www/tastypanel
./infrastructure/preflight-prod.sh
```

- Optional end-to-end smoke flow:

```bash
sudo /var/www/tastypanel/infrastructure/smoke-test-tenant.sh flow
```
