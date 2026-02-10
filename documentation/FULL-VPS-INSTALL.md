# Full VPS install — nafs l7aja b7al CloudPanel

CloudPanel kay yakhod VPS kamal: one command 3la fresh server w t7ot panel. Hna nafs l7aja.

**Official repo:** https://github.com/salimmous/TastyPanel

## Idea

- **Dedicated VPS**: one server, 100% for TastyPanel (panel + tenant sites).
- **One-command install**: you run a single script on a **fresh Ubuntu 24.04** VPS; it installs Nginx, PHP, MySQL, Redis, clones the panel, configures everything, and sets cron/scheduler.
- After that, the VPS is “taken” by TastyPanel like CloudPanel does.

## Option 1: One command (bootstrap)

On a **new Ubuntu 24.04** VPS (root or sudo). REPO is default (salimmous/TastyPanel), ghir PANEL_HOST:

```bash
curl -sSL https://raw.githubusercontent.com/salimmous/TastyPanel/main/infrastructure/bootstrap-full-vps.sh | sudo bash -s -- PANEL_HOST=84.247.160.84
```

Replace `84.247.160.84` with your server IP or domain.

Optional: `PANEL_PORT=80`, `DB_PASS=...`, `PANEL_SSL_SELF_SIGNED=true`, `REPO_URL=...` (same as `install-ubuntu-24.04.sh`).

Then open: `http://PANEL_HOST:PORT/platform/install` and finish the web installer.

## Option 2: gh clone then install

```bash
# 1) Clone (gh CLI)
gh repo clone salimmous/TastyPanel /var/www/tastypanel
cd /var/www/tastypanel

# 2) Full install (takes the VPS)
sudo PANEL_HOST=84.247.160.84 bash infrastructure/install-ubuntu-24.04.sh
```

Or with git:

```bash
sudo git clone https://github.com/salimmous/TastyPanel.git /var/www/tastypanel
cd /var/www/tastypanel
sudo PANEL_HOST=84.247.160.84 bash infrastructure/install-ubuntu-24.04.sh
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
- **REPO_URL** only if the panel directory is empty (first install). If the code is already at `/var/www/tastypanel` (e.g. copied or extracted), the install script skips clone and does not require REPO_URL — **officially** works without git clone.

## Push and pull from the VPS

Bach t9der dir **push** w **pull** men nafs l-VPS (bla ma t-configure credentials f kol marra), khass deploy key dyal GitHub.

### 1) Generate SSH key on the VPS

```bash
ssh-keygen -t ed25519 -C "vps-tastypanel" -f ~/.ssh/id_ed25519_tastypanel -N ""
cat ~/.ssh/id_ed25519_tastypanel.pub
```

Copy l-output (line dyal `ssh-ed25519 AAAA... vps-tastypanel`).

### 2) Add deploy key on GitHub

- Repo **TastyPanel** → **Settings** → **Deploy keys** → **Add deploy key**
- **Title:** `vps-tastypanel` (aw ay smiya)
- **Key:** paste l-public key (daba li 9ra f `cat ...pub`)
- **Allow write access** → check (bach t-push)
- **Add key**

### 3) SSH config bach Git yst3mel had l-key

```bash
# Create or edit ~/.ssh/config (user li kay run git, 3adan root)
mkdir -p ~/.ssh
cat >> ~/.ssh/config << 'EOF'
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_ed25519_tastypanel
  IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config
```

### 4) Remote SSH w test

```bash
cd /var/www/tastypanel
git remote set-url origin git@github.com:salimmous/TastyPanel.git
git fetch origin
git pull origin main   # pull
git push origin main   # push (ila 3andak changes)
```

Men daba **push** w **pull** khdamin direct men l-VPS.

### Updates from Git (manual or cron)

- **Manual:** `git pull origin main` w ba3daha `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan config:clear`, `php artisan cache:clear`.
- **Script:** `infrastructure/update-from-git.sh` — kaydir pull (ff-only) + composer + migrate + clear caches. Ila bghiti auto-update:
  ```bash
  # Cron example (daily 4am)
  0 4 * * * /var/www/tastypanel/infrastructure/update-from-git.sh >> /var/log/tastypanel-update.log 2>&1
  ```

## Summary

- **CloudPanel** = one command → VPS kamal for the panel.
- **TastyPanel** = same thing: run **bootstrap-full-vps.sh** (or `gh repo clone salimmous/TastyPanel` + **install-ubuntu-24.04.sh**) on a fresh VPS → **dakchi ga3ma khaddam**, nafs l7aja.
- **Repo:** https://github.com/salimmous/TastyPanel — `gh repo clone salimmous/TastyPanel`
- **Push/pull from VPS:** deploy key (SSH) + `~/.ssh/config` → `git push` / `git pull` direct. See **Push and pull from the VPS** above.
