# TastyPanel vs CloudPanel — nafs l-control

Comparison so the platform has the same kind of control as CloudPanel.

## Feature comparison

| Feature | CloudPanel | TastyPanel |
|--------|------------|------------|
| **Sites / tenants** | Create site, domain, docroot | ✓ Tenants, domains, provisioning |
| **Database** | Create DB + user, phpMyAdmin | ✓ One DB per tenant, phpMyAdmin (single URL) |
| **SSL** | Let's Encrypt, auto-renew | ✓ SSL provisioning, renewal |
| **SSH / SFTP** | User + key auth | ✓ Per-tenant user, SSH/SFTP |
| **Nginx Vhost** | Edit vhost (rewrites, redirects) | ✓ Vhost tab, edit & reload |
| **Cron** | Cron jobs per site | ✓ Cron tab (per-tenant jobs) |
| **File Manager** | In-panel, upload/edit/permissions | ○ Link to SFTP or external FM |
| **PHP version** | Choose per site (8.1, 8.2, …) | ○ One version per tenant (config) |
| **Logs** | Nginx, PHP error logs | ✓ PHP-FPM + Nginx error logs |
| **Backups** | DB + files, retention | ✓ Tenant backups, create + history |
| **Security** | Basic Auth, IP block | ✓ WAF, security profile |
| **Dashboard** | CPU, RAM, disk | ✓ Overview, quota, services |
| **Multi-tenant** | One server, many sites | ✓ Platform + tenant isolation |

✓ = done in platform  
○ = partial or planned

## Per-site (tenant) tabs in TastyPanel

Same idea as CloudPanel: one place per site with all controls.

| Tab | Content |
|-----|--------|
| **Overview** | Status, key, domains, disk |
| **Access** | SSH/SFTP host, user, port |
| **Database** | MySQL credentials + phpMyAdmin link |
| **Mail** | SMTP settings |
| **Security** | WAF, block SQLi/XSS |
| **Apps** | Installed apps (placeholder) |
| **Vhost** | Nginx config edit + save & reload |
| **Cron** | Per-tenant cron jobs (schedule + command) |
| **Secrets** | API keys / env secrets |
| **Logs** | PHP-FPM + Nginx error logs |
| **Backups** | Create backup, list, download |
| **Staging** | Staging env (separate page) |
| **Automation** | Rules (separate page) |

## Done for parity

- phpMyAdmin: single URL, open in panel, clean Database tab
- Vhost: already in place
- Cron: added as tab + store/apply per tenant
- Same “control surface”: one site → same tabs and actions as CloudPanel

## Optional next steps

- **File Manager**: “Open File Manager” link (e.g. to SFTP client with pre-filled host/user) or integrate a simple FM.
- **PHP version**: Dropdown per tenant to switch PHP-FPM pool (e.g. 8.2 vs 8.3).
- **Extra DBs**: Allow adding more MySQL DBs/users per tenant from the UI.
