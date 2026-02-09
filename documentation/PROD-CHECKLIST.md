# Production Checklist (One-Click Tenant Sites)

## 0) Preflight (recommended before go-live)
Run the automated pre-launch checks:

```bash
cd /var/www/tastypanel
./infrastructure/preflight-prod.sh
```

Useful options:
```bash
./infrastructure/preflight-prod.sh --strict
./infrastructure/preflight-prod.sh --smoke-flow
./infrastructure/preflight-prod.sh --no-ci-gates
```

## 0.1) Service manager + safe deploy checks
Verify platform service endpoints are available:
```bash
php artisan route:list | grep "platform/services\\|platform/nginx/deploy-safe"
```
From the UI (`/admin/platform`):
- test one service action (`restart` on a non-critical service in staging)
- open service logs for nginx/php-fpm
- run `Run Safe Deploy` and confirm output includes `SUCCESS=true`

## 1) Required env
- `FRONTEND_AUTO=true`
- `FRONTEND_PROVISION_SCRIPT=/var/www/tastypanel/infrastructure/provision-frontend.sh`
- `FRONTEND_DEPROVISION_SCRIPT=/var/www/tastypanel/infrastructure/deprovision-frontend.sh`
- `FRONTEND_PROVISION_USE_SUDO=true`
- `FRONTEND_PLATFORM_API_BASE=https://<platform-domain>/api`

## 2) Sudoers setup
- Run once on server:
```bash
sudo APP_DIR=/var/www/tastypanel WEB_USER=www-data /var/www/tastypanel/infrastructure/setup-sudoers.sh
```
- Validate:
```bash
sudo -l -U www-data
```

## 3) Provisioning flow test
- Create tenant from admin UI (`Add New Site`).
- Confirm:
  - provisioning job moves `queued -> running -> done`
  - service `tastypanel-<tenant-key>-frontend.service` is running
  - nginx vhost exists for domain
  - domain serves content
- Force-fail one run (invalid Cloudflare token) and confirm:
  - job ends `rolled_back` or `failed`
  - no stale DNS/vhost remains when rollback succeeds

## 4) Queue worker required
Provisioning runs in queue, so make sure worker is running:
```bash
php artisan queue:work --queue=default --tries=1
```
If queue worker is down, jobs remain in `queued`.

## 5) Smoke test command
```bash
sudo /var/www/tastypanel/infrastructure/smoke-test-tenant.sh <tenant-key> <domain> 8.3
```
```bash
sudo /var/www/tastypanel/infrastructure/smoke-test-tenant.sh flow [tenant-key] [domain] [theme-id]
```
- `flow` mode runs end-to-end lifecycle:
  - create tenant + primary domain
  - run provisioning + access setup
  - install temporary SSH key + test SSH login
  - rollback + cleanup tenant record
- To keep failed flow resources for debugging:
```bash
sudo SMOKE_KEEP_FAILED=1 /var/www/tastypanel/infrastructure/smoke-test-tenant.sh flow
```

## 6) Retry / rollback checks
- In tenant card, use:
  - `Retry provisioning` after a failed run
  - `Rollback` to clean failed DNS/vhost changes
- API checks:
```bash
php artisan route:list | grep provisioning
```

## 6.1) Instance isolation checks
- Verify tenant runtime user exists:
```bash
id tbapp<tenant_id>
```
- Verify pool file exists and runs with dedicated user:
```bash
sudo cat /etc/php/8.3/fpm/pool.d/<tenant-key>.conf | grep -E "^(user|group|pm.max_children|pm.max_requests)"
```
- Verify tenant root ownership:
```bash
sudo ls -ld /var/www/tastypanel-sites/<tenant-key>
```

## 7) Common fixes
- Frontend build fails:
  - check Node version `node -v` (18+)
  - check npm logs in tenant frontend folder
- Service not running:
  - `sudo systemctl status tastypanel-<tenant-key>-frontend.service`
- Nginx issue:
  - `sudo nginx -t` then reload
- Queue stuck:
  - `php artisan queue:restart`
  - check `failed_jobs` table and worker logs

## 8) SSH/SFTP per-tenant checks
- In tenant details, run `Setup Access` once.
- Validate on server:
```bash
id tb<tenant_id>
getfacl /var/www/tastypanel-sites/<tenant-key> | grep tb<tenant_id>
sudo cat /etc/ssh/sshd_config.d/99-tastypanel-tb<tenant_id>.conf
```
- Test login:
```bash
ssh tb<tenant_id>@<server-ip>
```
- For FTP clients, use `SFTP` protocol (not plain FTP).
- Confirm policy:
  - `TENANT_ACCESS_AUTH_MODE=both|keys|password`
  - `TENANT_ACCESS_SFTP_ONLY=true|false`

## 8.1) Tenant local mail checks
- In tenant details, run:
  - `Configure SMTP` (use local SMTP `127.0.0.1` if self-hosted mail server)
  - `Test SMTP`
  - `Create Mailbox`
- Validate on server:
```bash
sudo test -x /var/www/tastypanel/infrastructure/manage-tenant-mailbox.sh
sudo ls -la /var/mail/tastypanel
sudo test -f /etc/dovecot/tastypanel-users && sudo wc -l /etc/dovecot/tastypanel-users
```
- Validate API routes:
```bash
php artisan route:list | grep "tenants/.*/mail"
```
- Confirm tenant mail limits are applied:
  - `mail_daily_limit`
  - `mail_per_minute_limit`
  - recent events show `success/failed/throttled`

## 9) Observability checks
- Confirm headers:
  - `X-Response-Time`
  - `X-Cache-Status` (when cache middleware is used)
- Confirm tenant observability data appears in `/admin/analytics`:
  - status (`healthy/degraded/critical`)
  - avg/p95 response time
  - 5xx rate
  - anomaly list

## 10) WAF + quota profile checks
- Open tenant security profile API and verify values persist:
```bash
php artisan route:list | grep security-profile
```
- Verify WAF block mode:
  - send test SQLi payload and confirm blocked response.
- Verify quota mode:
  - set low `max_monthly_requests` and confirm API returns `429` after threshold.
  - set low `max_cpu_percent` / `max_memory_mb` / `max_worker_processes` and confirm runtime limit returns `429`.
  - confirm alerts appear when usage exceeds `quota_alert_threshold_percent`.

## 11) Secrets manager checks
- Set a test secret and verify metadata:
```bash
php artisan route:list | grep tenants/.*/secrets
```
- Delete secret using `confirm=DELETE_SECRET`.
- Confirm secrets are not returned in plaintext by list endpoint.

## 12) DR drill checks
- Run platform + tenant drill:
```bash
php artisan drill:run
```
- Run full weekly style drill:
```bash
php artisan drill:run --all-tenants
```
- Confirm records in `/api/admin/platform/drills` include:
  - `status` (`passed`/`failed`)
  - `rpo_hours`
  - `rto_seconds`

## 13) Prometheus checks
- Validate endpoint:
```bash
curl -H "Authorization: Bearer <PROMETHEUS_TOKEN>" http://127.0.0.1:8000/metrics
```
- Start local stack:
```bash
cd /var/www/tastypanel/infrastructure/observability
docker compose up -d
```

## 14) CI gates
- Run local gates:
```bash
./infrastructure/ci-gates.sh
```
- Optional privileged smoke flow gate:
```bash
sudo RUN_SMOKE_FLOW=1 ./infrastructure/ci-gates.sh
```
