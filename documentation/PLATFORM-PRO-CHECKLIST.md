# Platform Pro — kolha fonction, khdama m3a ba3ditya, m9ad w clean

Had l-doc kay résumé: **wach kol 7aja khedama**, **kifach t-connectée m3a ba9i**, w **config bach t-kon pro**.

---

## 1) Modules w kifach khdamin m3a ba9i

| Module | Route | Connection m3a |
|--------|--------|-----------------|
| **Dashboard** | `/platform` | Sites (tenants), recent activity, quick links → Control, Deploy, Domains, Monitoring. |
| **Overview** | `/platform/overview` | Metrics, tenants, high-level status. |
| **Control Center** | `/platform/control` | Runbooks (tenant, domain, firewall). Domain actions → Domain Center. Bulk ops → Sites. Audit → Audit Logs. |
| **Deploy Center** | `/platform/deploy` | Per-tenant deploy (runbooks). Audit. |
| **Sites** | `/platform/tenants` | Create site → provisioning (instance, DNS, SSL). Tenant card → Domain Center (domains), phpMyAdmin, Staging, Preview, Install App, Secrets, Backups. |
| **Domain Center** | `/platform/domains` | Domains list (prod/staging/preview). DNS/SSL/HTTP3 actions. Cloudflare purge, renew SSL. Run → Control Center runbook. |
| **Users** | `/platform/users` | Platform users (superadmin). |
| **Roles & Permissions** | `/platform/roles` | Access control. |
| **Security Center** | `/platform/security` | IP allowlist, 2FA, sessions, emergency lock. |
| **Monitoring Center** | `/platform/monitoring` | Uptime, SSL, backups, HTTP/3. Alerts → Monitoring Rules. |
| **Monitoring Rules** | `/platform/monitoring/rules` | Per-tenant alert rules (emails, Slack). |
| **Analytics** | `/platform/analytics` | Platform analytics. |
| **System Status** | `/platform/system` | Health. |
| **Backups** | `/platform/backups` | Platform + tenant backups. |
| **DR Drills** | `/platform/drills` | Disaster recovery. |
| **Audit Logs** | `/platform/audit-logs` | Kol actions (runbooks, login, etc.). |
| **Themes / Plugins** | `/platform/themes`, `/platform/plugins` | Tenant app. |
| **Settings** | `/platform/settings` | Platform config (email, rate limit, etc.). |

**Flow:** Create site (Sites) → provisioning (instance + optional DNS Cloudflare + SSL) → Domain Center (inventory, SSL renew, purge) → Control Center (runbooks) → Audit Logs (trace). Monitoring + Rules = alerts. Security = 2FA + IP. **Kolha m-connectée.**

---

## 2) Config bach kolha khedama (pro)

| Config | Ach | Ila ma 3andekch |
|--------|-----|------------------|
| **APP_KEY** | Laravel encryption | 500, sessions ma y-khedamch. |
| **DB_*** | Database platform | Migrate ma y-khedamch, install ma y-kmel. |
| **CLOUDFLARE_TOKEN** + **CLOUDFLARE_ZONE_ID** | DNS + purge Domain Center | DNS create/purge ma y-khedamch; provisioning DNS skip. |
| **CLOUDFLARE_DNS_TOKEN** + **SSL_AUTO** + **SSL_CERTBOT_EMAIL** | SSL automatic | SSL ma y-provisionich auto. |
| **TENANT_APP_REPO** | Tenant app (Laravel patch) | Instance = placeholder "default" (or clone fallback). |
| **INSTANCE_PROVISION_SCRIPT** + sudoers | Provisioning | Instance ma y-createch. |
| **NGINX_***, CERTBOT** | Nginx + SSL | Domain Nginx/SSL ma y-khedamch. |
| **Queue worker** (`php artisan queue:work`) | Jobs (provisioning, install app, backups) | Jobs y-bqaw f queue, ma y-executawch. |
| **Scheduler** (cron) | Schedule (renewals, alerts, cleanup) | Cron ma y-runich → renewals/alerts ma y-diruch. |

**Pro:** 7ot APP_KEY, DB_*, Cloudflare (token + zone_id + dns_token), SSL_AUTO + email, TENANT_APP_REPO, sudoers, queue worker, cron. Tafsil: `.env.example`, `documentation/CLOUDFLARE-PLATFORM.md`, `documentation/FULL-VPS-INSTALL.md`.

---

## 3) Quick verification (kolha ok)

- [ ] Login `/platform/login` → Dashboard.
- [ ] Sites → Create site → (optional) provisioning runs; tenant card shows.
- [ ] Domain Center → domains list (or empty); filter, "Renew expiring SSL" modal.
- [ ] Control Center → Runbooks (tenant/domain) → output + Audit Logs.
- [ ] Deploy Center → tenant deploy actions.
- [ ] Monitoring → uptime, SSL, backups counts; Monitoring Rules → save.
- [ ] Security → IP, 2FA, emergency lock.
- [ ] Settings → save; test email ila configured.
- [ ] Audit Logs → entries after runbooks/login.

---

## 4) Résumé

- **Kolha fonction** = kol routes w views mawjodin; config (.env + queue + cron) = kolha khedama.
- **Khdama m3a ba3ditya** = Sites ↔ Domains ↔ Control ↔ Deploy ↔ Monitoring ↔ Audit ↔ Security; one flow.
- **M9ad w clean w pro** = config kamel, queue running, cron set, doc CLOUDFLARE + TENANT-WORKFLOW + had checklist.
