# One-click install dyal site — b7al WordPress (install + direct khedam, optional import)

## Fikra

**Goal:** T-click **Install** (aw Create site + provision) w **direct** l-site y-khedam: dashboard m9ad, admin user jahiz, optional **demo data** aw **import data** (b7al WordPress: install → import content). Bla ma t-dir 7aja manuelle ba3d.

**Exemple:** WordPress → t-install → t-open site → dashboard + "Hello World" post. Ila bghiti: import WXR/data → t-import w koulchi jahiz. Hna nfs l-7aja: **one-click** → site Laravel (patch) jahiz b admin w optional seed/import.

---

## Daba wach 3andna

| Step | Daba | One-click (goal) |
|------|------|-------------------|
| Create site | Platform → Sites → Add site (name + domain) | Nafs 7aja. |
| Provision | Clone TENANT_APP_REPO, DB, composer, migrate. **Ma 3andna seed** f provision. | Provision + **seed** (admin + optional demo) = site jahiz direct. |
| Admin user | Ila patch ma 3andouch User::create f seed: t-st3mel "Install Application" (Laravel/git) w t3ti email/user/pass. | **Seed** f patch y-create admin (platform t-pass credentials aw env). |
| Demo / import | Tenant app 3andha Import (CSV, JSON, WordPress WXR) men admin UI. | Optional: **seed** y-7ot demo data; aw first-run **wizard** "Import data" b7al WordPress. |

**Conclusion:** Bach one-click y-wassal: **patch n9ad** (theme + dashboard) + **seed** (admin + optional demo) + **provision** y-run seed. Optional: wizard import f tenant app.

---

## Plan bach n-waslu (patch n9ad + one-click)

### 1) Patch (tenant app repo) n9ad

- **Seeder f repo Laravel:**
  - `DatabaseSeeder` aw `TenantFirstRunSeeder`: create **first admin user** (email, name, password). L-values y-jiw men **env** (e.g. `TENANT_ADMIN_EMAIL`, `TENANT_ADMIN_PASSWORD`) li l-platform t-7ot 9bal ma y-run `php artisan db:seed`.
  - Optional: **demo data** (categories, posts, pages) b7al WordPress "Sample content".
- **Ila tenant app ma 3andouch users table:** t-zid migration + User model w seed.

### 2) Provision y-run seed

- **provision-instance.sh** (aw step ba3dah f platform): 9bal ma y-endi, t-7ot env vars dyal admin (from platform: email, password) f `.env` dyal tenant, w t-run:
  ```bash
  php artisan db:seed --force
  ```
- Platform: men "Create site" (aw "Install") form, t-collect admin email + password; 9bal provisioning t-pass them (env aw file) l script. Script y-write `TENANT_ADMIN_EMAIL` w `TENANT_ADMIN_PASSWORD` f tenant `.env`, y-run migrate, y-run seed → admin created.

### 3) Optional: Import data (b7al WordPress)

- **Option A:** Seed f patch y-7ot **demo content** (posts, categories) — one-click = site + demo.
- **Option B:** Tenant app 3andha déjà **Import** (CSV/JSON/WXR). First visit y-offri **wizard**: "Import your data" (upload file). B7al WordPress import.
- **Option C:** Platform UI: "Install with demo data" checkbox → provision + seed demo; "Install only" → provision + seed admin 7ta 7da.

### 4) Platform UI

- **Sites → Add site:** form (name, domain, **admin email**, **admin password**, optional "Install demo data"). Submit → create tenant + domain + **provision with seed** (credentials passed). Man ba3d: site live, admin jahiz, optional demo.
- Aw: **Sites → [tenant] → Install Application** → choice "Laravel (patch)" + admin email/pass → nafs l-flow (provision clone + seed).

---

## Résumé

- **Fikra:** One-click install = site khedam direct (admin + optional demo/import), b7al WordPress.
- **Daba:** Provision = clone + migrate, bla seed; admin t-create men "Install Application" aw manually.
- **Plan:** (1) Patch n9ad b **seed** (admin from env + optional demo). (2) Provision script y-write admin env w y-run `db:seed`. (3) Platform form t-collect admin (w optional "demo") w t-pass l provision. (4) Optional: wizard import f tenant app.
- **Result:** T-click Install (aw Create site b admin fields) → backend provision + seed → site live, admin connecté, optional demo data — **direct, m9ad, pro**.
