# Tenant workflow — theme + dashboard, install f backend, admin access, then site m9ad

Had l-workflow kaywaddi men "create tenant" 7ta "site m9ad fih dashboard w database w admin access". **Dima Laravel** — tenant app = Laravel (theme + dashboard). Order m9ad: **admin access 9bal setup site**, w install kay imchi f backend (terminal / queue) w man ba3d kay tla3 l-site jahiz.

## 1) L-patch: theme + dashboard dyal tenant (Laravel)

- **Patch** = wa7ad repo **Laravel** (tenant app) fih **theme** w **dashboard** dyal tenant. Nafs l-7aja li kay clone l-panel f kol site. Dima Laravel.
- Tansa2od t-export men l-platform (template) aw t-dir repo 3andi Laravel b theme w dashboard: `infrastructure/export-tenant-app.sh` → push l GitHub → `TENANT_APP_REPO` f `.env` dyal l-panel.
- Tafsil: `documentation/TENANT-APP-REPO.md`.

## 2) Workflow — order m9ad

| Step | Action | Kayn fayn | Notes |
|------|--------|-----------|--------|
| **1** | Create tenant | Platform UI → Sites → Add site (name + domain) | Tenant w domain yt-creaw; provisioning optional (auto_on_create). |
| **2** | Provision instance | Auto (ila `provisioning.auto_on_create=true`) aw manual: Control Center → Runbooks "Tenant: provision instance" | Script `provision-instance.sh` f terminal: clone `TENANT_APP_REPO` (aw "default" placeholder), DB, composer, migrate. Instance_root w DB jahzin. |
| **3** | Admin access | After instance ready: provision SSH/SFTP user | **9bal ma t-dir "setup site"** khass admin access (SSH/SFTP) ykon m3a l-tenant. Men Control Center: "Tenant: provision access" aw auto (ila `TENANT_ACCESS_AUTO=true`). Script: `provision-tenant-access.sh`. |
| **4** | Setup site / Install app | **Option A:** site already jahiz (ila TENANT_APP_REPO = Laravel theme+dashboard). **Option B:** Platform → Tenant → **Install Application** → Laravel (aw git repo Laravel). Dima Laravel. | Install kay imchi f **backend** (queue job → `install-tenant-app.sh`). T3ti admin_email, admin_user, admin_password → man ba3d kay tla3 site Laravel m9ad fih dashboard w database w admin user. |

## 3) Install f backend (terminal)

- **Provisioning:** `provision-instance.sh` — yt-run b sudo men l-panel (queue aw runbook). Clone **Laravel** repo (TENANT_APP_REPO), create DB, `composer install`, `php artisan migrate`, etc. Output yt-log f job / runbook.
- **Install app (Laravel):** `install-tenant-app.sh` — yt-run b sudo men queue job. Wipe instance root, install Laravel (aw clone repo Laravel), configure DB, create admin user. Dima Laravel. Logs f queue worker w `instance_last_error` ila fail.
- Ma t-direct run men terminal: `sudo /var/www/tastypanel/infrastructure/provision-instance.sh ...` (args from config) aw l-install t-wsel 3la l-site men Platform → Install Application.

## 4) Admin access 9bal setup site

- **Admin access** = SSH and/or SFTP user dyal tenant (chrooted l instance_root). Bach l-user (aw l-client) yqder y-dir setup aw y-connect l server.
- Order: 1) Tenant created. 2) Instance provisioned (Laravel clone, instance_root exists). 3) **Provision tenant access** (SSH/SFTP user). 4) Daba "setup site" — either site Laravel already live (TENANT_APP_REPO) aw t-click "Install Application" (Laravel) w t3ti admin credentials → install y-kmel f backend w kay tla3 site Laravel m9ad.

## 5) Site m9ad: dashboard + database + koulchi (Laravel)

- **Ila st3melt TENANT_APP_REPO** (Laravel theme + dashboard): after provisioning, site Laravel jahiz — dashboard w DB m3ah. Ila l-app ma 3andouch "first admin", tansa2od t-zid seed/command aw "Install Application" b type "git" (same repo Laravel) w t3ti admin_email/user/pass.
- **Ila st3melt "Install Application"**: Laravel (starter aw custom repo). Dima Laravel. After job y-kmel, site Laravel m9ad: app installed, DB configured, admin user created, dashboard ready.

## 6) Frontend (Next.js) — optional

- Ila bghiti frontend (Next.js) m3a tenant: `FRONTEND_AUTO=true` w scripts `provision-frontend.sh` / `deprovision-frontend.sh`. After provisioning, frontend yt-provision auto. Tafsil: `documentation/TENANT-FRONTEND-AUTO.md`.

## 7) One-click install (b7al WordPress, install + import)

Bach **one click** → site khedam **direct** (admin jahiz, optional demo/import): patch n9ad b **seed** (admin + optional demo), provision y-run seed, platform t-pass admin credentials. Tafsil: **`documentation/TENANT-ONE-CLICK-INSTALL.md`**.

## 8) Résumé — Workflow (dima Laravel)

1. **Patch** = tenant app repo **Laravel** (theme + dashboard). Export or custom repo → `TENANT_APP_REPO`.
2. **Create tenant** → **Provision instance** (Laravel clone f backend) → **Admin access** (SSH/SFTP) → **Setup site** (Laravel already live aw "Install Application" Laravel).
3. Install (provision + install app) kay imchi f backend/terminal; man ba3d kay tla3 site **Laravel** m9ad fih dashboard w database; 9bal setup site khass admin access ykon m3a l-tenant.
4. **One-click (goal):** provision + seed (admin + demo) → site direct khedam. See `TENANT-ONE-CLICK-INSTALL.md`.

**Workflow tani (4 steps):**

| # | Step              | Action |
|---|-------------------|--------|
| 1 | Create tenant     | Platform → Sites → Add site (name + domain). |
| 2 | Provision instance | Auto aw manual: clone **Laravel** repo (TENANT_APP_REPO), DB, composer, migrate. Install f backend. |
| 3 | Admin access      | Provision SSH/SFTP user (9bal setup site). |
| 4 | Setup site        | Site Laravel already jahiz men repo, aw "Install Application" (Laravel) b admin credentials → site m9ad. |

Files li kay t3taw f had l-workflow:
- `infrastructure/provision-instance.sh` — instance Laravel (clone, DB, composer, migrate)
- `infrastructure/provision-tenant-access.sh` — SSH/SFTP user
- `infrastructure/install-tenant-app.sh` — install Laravel + admin user
- `app/Jobs/InstallTenantAppJob.php` — queue job dyal install
- `config/services.php` — `instances.repo`, `provisioning.auto_on_create`, access script
