# Full VPS install — nafs l7aja b7al CloudPanel

CloudPanel kay yakhod VPS kamal: one command 3la fresh server w t7ot panel. Hna nafs l7aja.

## Idea

- **Dedicated VPS**: one server, 100% for TastyPanel (panel + tenant sites).
- **One-command install**: you run a single script on a **fresh Ubuntu 24.04** VPS; it installs Nginx, PHP, MySQL, Redis, clones the panel, configures everything, and sets cron/scheduler.
- After that, the VPS is “taken” by TastyPanel like CloudPanel does.

## Option 1: One command (bootstrap)

On a **new Ubuntu 24.04** VPS (root or sudo):

```bash
curl -sSL https://raw.githubusercontent.com/YOUR_ORG/tastypanel/main/infrastructure/bootstrap-full-vps.sh | sudo bash -s -- \
  REPO_URL=https://github.com/YOUR_ORG/tastypanel.git \
  PANEL_HOST=84.247.160.84
```

Replace:
- `YOUR_ORG/tastypanel` with your repo.
- `PANEL_HOST` with your server IP or domain (e.g. `panel.example.com`).

Optional: `PANEL_PORT=80`, `DB_PASS=...`, `PANEL_SSL_SELF_SIGNED=true`, etc. (same as `install-ubuntu-24.04.sh`).

Then open: `http://PANEL_HOST:PORT/platform/install` and finish the web installer.

## Option 2: Two steps (clone then install)

Same idea, two commands:

```bash
# 1) Clone
sudo git clone https://github.com/YOUR_ORG/tastypanel.git /var/www/tastypanel
cd /var/www/tastypanel

# 2) Full install (takes the VPS)
sudo REPO_URL=https://github.com/YOUR_ORG/tastypanel.git PANEL_HOST=84.247.160.84 bash infrastructure/install-ubuntu-24.04.sh
```

Then open `/platform/install` in the browser.

## What gets installed (nafs l7aja)

- Nginx, PHP-FPM, MySQL, Redis
- Panel app in `/var/www/tastypanel`
- Tenant sites root: `/var/www/tastypanel-sites`
- Nginx vhost for the panel (port 80 or the one you set)
- MySQL DB + user for the panel
- Cron for Laravel scheduler
- Sudoers so the panel can run provisioning scripts
- (Optional) SSL, Cloudflare, firewall — see `install-ubuntu-24.04.sh` and `.env`

After that, you create sites from the platform; each tenant gets its own dir, DB, PHP-FPM pool, SSH user, etc. — **nafs control, nafs koulchi**, on one VPS.

## Requirements

- **Fresh Ubuntu 24.04** (or 22.04) VPS
- Root or sudo
- Min 1GB RAM, 10GB disk (2GB+ RAM recommended)
- `REPO_URL` must be a public repo, or use SSH key so git can clone

## Summary

- **CloudPanel** = one command → VPS kamal for the panel.
- **TastyPanel** = same thing: run **bootstrap-full-vps.sh** (or clone + **install-ubuntu-24.04.sh**) on a fresh VPS → **dakchi ga3ma khaddam**, nafs l7aja.
