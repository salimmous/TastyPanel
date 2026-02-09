# Platform Control Panel

Blade admin pages are served under `/platform/*` (`/platform/login`, `/platform/dashboard`).
Operational control APIs remain under `/api/admin/platform/*`.

## Features
- Server status (load, memory, disk, services)
- Queue manager (restart workers, flush failed jobs)
- Platform Service Manager (start/stop/restart/status/logs)
- Queued tenant provisioning (`Add New Site`) with progress states
- Provisioning lock + idempotency (duplicate requests are skipped safely)
- Provisioning recovery actions (`Retry provisioning`, `Rollback`)
- Nginx safe deploy (backup + `nginx -t` + auto rollback)
- Backups (DB + storage archive + download)
- Tenant Ops (per-tenant backups, queue actions, logs)
- Security (IP allowlist, force 2FA, rate limits)
- WAF + quota profiles per tenant (rate/WAF/requests/storage/DB/CPU/RAM/workers)
- Tenant Secrets Manager (encrypted API keys/tokens)
- Plans & limits (posts/users/rate limits)
- Audit logs
- Alerts (SSL expiry + HTTP/3 issues)
- Disaster recovery drills (RPO/RTO snapshots)
- Prometheus metrics endpoint (`/metrics`)

## Add New Site
- Creating a site from `/platform/tenants/create` creates tenant + domain records.
- Default behavior is manual-safe: no theme auto-assignment and no auto-provisioning.
- If `AUTO_PROVISION_ON_TENANT_CREATE=true`, tenant creation can enqueue provisioning jobs.
- Provisioning can also be triggered explicitly from admin API/actions.

## Optional queued provisioning flow
- Provisioning runs as a step-based state machine:
  - tenant instance bootstrap
  - frontend install/service setup
  - Cloudflare DNS record
  - SSL provisioning (if enabled)
  - Nginx vhost apply
- If a step fails after partial success, automatic rollback runs in reverse order to clean leftovers:
  - Nginx remove
  - DNS record delete
  - instance cleanup (when the instance was created in the same run)
- Job states:
  - `queued`
  - `running`
  - `done`
  - `failed`
  - `rolled_back`
- If provisioning fails, use tenant actions:
  - `Retry provisioning`
  - `Rollback` (removes vhost and DNS record, reverts failed state)
- If provisioning is already running for the same tenant/domain, duplicate jobs are skipped by lock.
- If domain/instance is already in the desired state, provisioning returns idempotent success.
- API endpoints:
  - `GET /api/admin/tenants/{tenant}/provisioning-jobs`
  - `POST /api/admin/tenants/{tenant}/provisioning/retry`
  - `POST /api/admin/tenants/{tenant}/provisioning/rollback`

## Tenant Runtime Isolation
- Each tenant instance is restricted to the configured instances root (`TENANT_INSTANCES_ROOT`).
- Each tenant gets a dedicated runtime user (`instance_system_user`, default prefix `tbapp`).
- PHP-FPM pool runs per tenant key socket (`/run/php/php<version>-fpm-<tenant-key>.sock`).
- Pool limits are configurable:
  - `INSTANCE_FPM_MAX_CHILDREN`
  - `INSTANCE_FPM_MAX_REQUESTS`
  - `INSTANCE_FPM_MEMORY_LIMIT_MB`
- Full cleanup path is available via `deprovision-instance.sh` (instance files, DB, DB user, FPM pool, frontend service).

## Two‑Factor Authentication (2FA)
- Enable 2FA per user (superadmin only)
- If `force_2fa` is enabled, superadmins must enable 2FA to log in
- Codes are emailed to the user address

## Backups
- A manual backup creates:
  - `database.sql`
  - `storage.tar.gz` (themes + nginx configs)
  - `backup.zip` (combined)
- Files are stored in `storage/app/backups/<timestamp>/`
- Optional S3 upload (set AWS credentials + enable in Platform Settings)
- One‑click restore is available in the Backups section

## Tenant Backups
- Each tenant can run backups independently (DB + tenant files).
- Backups are stored in `storage/app/tenant-backups/<tenant-id>/<timestamp>/`.
- Optional S3 upload per tenant, plus one‑click restore.

## Tenant SSH / SFTP Access
- Every tenant can have a dedicated Linux access user (default: `tb<tenant_id>`).
- Access is isolated to tenant files via per-user ACL on the tenant root.
- Per-user SSH policy is generated under `sshd_config.d` with:
  - auth mode (`keys`, `password`, `both`)
  - `SFTP-only` mode (optional)
  - port-forwarding disabled for tenant users
- Supported protocols:
  - `SSH` (shell)
  - `SFTP` (secure FTP)
- Tenant UI actions:
  - `Setup Access` (creates user + applies SSH policy + optional temporary password)
  - `Reset Password` (rotates temporary password when password auth is enabled)
  - `Add SSH Key` (adds public key to `~/.ssh/authorized_keys`)
- API endpoints:
  - `GET /api/admin/tenants/{tenant}/access`
  - `POST /api/admin/tenants/{tenant}/access/provision`
  - `POST /api/admin/tenants/{tenant}/access/password`
  - `POST /api/admin/tenants/{tenant}/access/key`

## Tenant Local Mail (SMTP + Mailboxes)
- Each tenant can use isolated mail settings (`mail_host`, `mail_port`, `mail_from_*`, limits).
- Local mailbox operations are available in tenant details:
  - `Configure SMTP`
  - `Test SMTP`
  - `Create Mailbox`
  - `Reset Password`
  - `Refresh Usage`
  - `Delete`
- Per-tenant mail throttling:
  - daily limit (`mail_daily_limit`)
  - per-minute limit (`mail_per_minute_limit`)
- Mail events are stored for audit and troubleshooting (`success`, `failed`, `throttled`).
- API endpoints:
  - `GET /api/admin/tenants/{tenant}/mail/settings`
  - `PUT /api/admin/tenants/{tenant}/mail/settings`
  - `POST /api/admin/tenants/{tenant}/mail/test`
  - `GET /api/admin/tenants/{tenant}/mailboxes`
  - `POST /api/admin/tenants/{tenant}/mailboxes`
  - `POST /api/admin/tenants/{tenant}/mailboxes/{mailbox}/password`
  - `POST /api/admin/tenants/{tenant}/mailboxes/{mailbox}/usage`
  - `DELETE /api/admin/tenants/{tenant}/mailboxes/{mailbox}`
  - `GET /api/admin/tenants/{tenant}/mail/events`

## Rate limits
- Default rate limit is from `PLATFORM_RATE_LIMIT`
- Per‑tenant plans can override `rate_limit_per_minute`

## WAF & Quotas
- Tenant security profile supports:
  - `rate_limit_per_minute`
  - `waf_enabled`, `waf_mode`
  - `waf_block_sqli`, `waf_block_xss`, `waf_block_lfi`
  - `max_monthly_requests`, `max_storage_mb`, `max_db_size_mb`
  - `max_cpu_percent`, `max_memory_mb`, `max_worker_processes`
  - `quota_alert_threshold_percent`
- Public traffic enforcement pipeline:
  - `tenant.security` (blocklists + WAF signatures)
  - `tenant.throttle` (per-minute limit)
  - `tenant.quota` (monthly/storage/DB/runtime hard checks)
- API endpoints:
  - `GET /api/admin/tenants/{tenant}/security-profile`
  - `PUT /api/admin/tenants/{tenant}/security-profile`

## Tenant Secrets Manager
- Secrets are encrypted at rest per tenant (`tenant_secrets` table).
- Supported actions:
  - list secret metadata
  - set/update secret value (version increments on rotation)
  - sync existing secret to tenant `.env`
  - remove key from tenant `.env`
  - delete secret
- API endpoints:
  - `GET /api/admin/tenants/{tenant}/secrets`
  - `POST /api/admin/tenants/{tenant}/secrets`
  - `POST /api/admin/tenants/{tenant}/secrets/sync`
  - `DELETE /api/admin/tenants/{tenant}/secrets/sync`
  - `DELETE /api/admin/tenants/{tenant}/secrets/{secretKey}` (`confirm=DELETE_SECRET`)

## Platform Service Manager
- System services can be controlled from the platform UI (`/platform/settings`) and admin APIs.
- Supports:
  - status listing for nginx/php-fpm/mysql/redis/queue/scheduler
  - `start` / `stop` / `restart`
  - recent logs view
- API endpoints:
  - `GET /api/admin/platform/services`
  - `POST /api/admin/platform/services/{service}/action`
  - `GET /api/admin/platform/services/{service}/logs`
  - `POST /api/admin/platform/nginx/deploy-safe`

## Alerts
- SSL expiry checks use the certificate in `storage/app/certbot` when available
- HTTP/3 issues are flagged for enabled domains with errors
- Optional notifications can be sent via email or Slack webhook
- Alert interval and recipients are configurable in Platform Settings

## Scheduler (cron)
Set a cron entry on the server:
```
* * * * * cd /var/www/tastypanel && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs:
- HTTP/3 health checks
- SSL renew checks
- Automatic backups
- Backup cleanup (retention)
- Alert notifications (SSL/HTTP3/backup failures)
- Uptime checks
- Integrity checks
- Disaster recovery drills (`drill:run --all-tenants`) weekly

Schedule intervals are controlled in Platform Settings:
- HTTP/3 check interval (minutes)
- SSL check interval (hours)
- Backup interval (hours)
- Backup retention (days)
- Alert interval (hours)
- Analytics interval (hours)
- Uptime check interval (minutes)
- Integrity check interval (hours)
- `Cron Jobs` toggle (`cron_enabled`)
- Scheduler timezone (`cron_timezone`)

## Analytics
- Traffic metrics are collected from Nginx access logs
- Default log path template: `/var/log/nginx/%s-access.log`
- After updating the vhost template, re‑provision domains to enable per‑domain logs
- Request performance metrics are captured per tenant (response time, query count/time, 5xx rate, slow requests).
- Tenant analytics include observability status and anomaly detection (latency/error spikes, unresolved critical errors).

## Ops Center
- Log viewer (PHP‑FPM + domain access/error logs)
- Security scans (ClamAV / Maldet)
- Security audit (composer/npm/OS update summary)
- File integrity baselines + checks
- Firewall rules (UFW apply + status)

## Monitoring
- Uptime checks per tenant (HTTP status + response time)
- Scheduler runs checks automatically
- Prometheus endpoint: `GET /metrics`
- Optional bearer token protection via `PROMETHEUS_TOKEN`

## SSO
- OIDC provider support (Google/Microsoft)
- Enforce SSO login and auto‑create users (optional)

## Integrations
- API keys per tenant (rate limit, expiry, rotate/revoke)
- Webhooks per tenant (article/recipe events + retries)
- Partner API endpoints are under `/api/partner/*` and require `X-API-Key` or `Authorization: Bearer`

## AI & Content Studio
- OpenAI draft generation
- Automation Studio with topics, voice, and scheduling
- Scheduled automation runs with history
- Content scoring (SEO + readability)

## File Manager
- Per‑tenant file browser (uploads/assets)
- Create folders, upload, rename, delete, and download
- Root path defaults to `TENANT_FILES_ROOT`

## Theme Marketplace
- Marketplace catalog filters (category, search, featured)
- Install themes per tenant from the marketplace
- Template Manager supports metadata (category/tags/author/version) + preview images

## Staging
- Enable staging mode per tenant
- Staging domains with separate theme/settings
- Sync production → staging and promote staging → production
- Content snapshots (create + restore to staging/production)
- Switch environment in the admin header (Production / Staging) to edit the right data set

## SAML SSO
- Google Workspace / Okta / Azure AD support
- Metadata URL or XML + attribute mapping
- Optional auto‑create users + enforce SAML login
