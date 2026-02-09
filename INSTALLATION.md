# TastyPanel Installation Guide

## Install goal

This install flow gives you:

- Platform is principal on VPS (`/var/www/tastypanel`) and tenant sites are separate instances (`/var/www/tastypanel-sites/<tenant-key>`).
- Laravel platform admin at `/platform/*`
- manual installer page at `/platform/install`
- custom panel host + custom panel port
- Blade/CSS admin mode (no Node build required)

## Quick install

```bash
sudo ./install.sh --domain=84.247.160.84 --port=8080 --db-name=tastypanel
```

Then open:

```text
http://84.247.160.84:8080/platform/install
```

Complete admin setup from the installer UI.

## Installer options

- `--domain=` panel host or IP (default `tastypanel.local`)
- `--port=` panel port (default `8080`)
- `--db-name=` MySQL database name (default `tastypanel`)
- `--admin-email=` optional display email in final output
- `--skip-ssl` skip certbot (recommended when using custom non-80 port)

## What gets installed

- PHP + required extensions
- MySQL
- Redis
- Nginx
- Composer
- Laravel dependencies (`composer install`)
- Nginx vhost for your selected host/port
- scheduler cron
- queue worker supervisor config

Not installed for platform mode:

- Node.js/NPM runtime build for admin UI
- React SPA requirement for platform login/dashboard

## Manual install alternative

If you prefer full manual:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server composer git unzip
```

```bash
cd /var/www
git clone https://github.com/YOUR_REPO/tastypanel.git
cd tastypanel
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Configure `.env`:

```env
APP_MODE=platform
TENANT_MODE=false
APP_URL=http://YOUR_IP:8080
AUTO_PROVISION_ON_TENANT_CREATE=false
FRONTEND_AUTO=false
```

Run Nginx with `root /var/www/tastypanel/public` and chosen `listen` port, then open:

```text
http://YOUR_IP:8080/platform/install
```

## After installation

- login: `/platform/login`
- dashboard: `/platform/dashboard`
- create site: `/platform/tenants/create`

Default tenant creation is safe/manual:

- creates tenant + primary domain record
- does not auto-install theme
- does not auto-provision full stack unless enabled

To enable auto provisioning later:

```env
AUTO_PROVISION_ON_TENANT_CREATE=true
```

## Required production cron

```cron
* * * * * cd /var/www/tastypanel && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting quick checks

```bash
cd /var/www/tastypanel
php artisan config:clear
php artisan cache:clear
php artisan migrate --force
php artisan route:list | grep platform
```

If Nginx fails:

```bash
nginx -t
systemctl status nginx --no-pager
```
