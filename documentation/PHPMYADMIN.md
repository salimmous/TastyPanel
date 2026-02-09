# phpMyAdmin (Ubuntu/Nginx)

Goal: give each tenant access to phpMyAdmin, isolated by DB credentials (each user sees only their DB).

---

## Option A: Single URL (same host as panel) — recommended

One phpMyAdmin at the same host/port as the panel, e.g. **http://84.247.160.84:8443/phpmyadmin/**. All tenants use this URL and log in with their site’s MySQL user (or `pma_<slug>` after Setup).

### 1) Install phpMyAdmin once

```bash
apt update
apt install -y phpmyadmin
```

Do **not** enable the default Apache config.

### 2) Nginx: add location inside the panel server block

Edit the panel’s Nginx config (e.g. `/etc/nginx/sites-available/tastypanel-platform.conf`) and add a **location** for `/phpmyadmin` **before** the main `location /` block:

```nginx
    # phpMyAdmin (same host as panel)
    location /phpmyadmin {
        alias /usr/share/phpmyadmin;
        index index.php;

        location ~ ^/phpmyadmin/(.+\.php)$ {
            alias /usr/share/phpmyadmin/$1;
            include snippets/fastcgi-php.conf;
            fastcgi_param SCRIPT_FILENAME /usr/share/phpmyadmin/$1;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        }
        location ~* ^/phpmyadmin/(.+\.(ht|git|ini)) { deny all; }
    }
```

Or a simpler variant:

```nginx
    location /phpmyadmin/ {
        alias /usr/share/phpmyadmin/;
        index index.php;
        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        }
    }
```

Adjust `fastcgi_pass` to your PHP-FPM socket. Then:

```bash
nginx -t && systemctl reload nginx
```

### 3) .env

```env
PMA_URL=http://84.247.160.84:8443/phpmyadmin
```

Or use path only (app URL + path):

```env
APP_URL=http://84.247.160.84:8443
PMA_PATH=/phpmyadmin
```

### 4) Platform

In **Platform → Tenants → [site] → Database**, use **Open in panel** or **Open in new tab**. Log in with that tenant’s DB user or `pma_<slug>` after **Setup phpMyAdmin user**.

---

## Option B: Per-tenant subdomain (pma.&lt;domain&gt;)

## 1) Install phpMyAdmin once

```bash
apt update
apt install -y phpmyadmin
```

Do **not** enable the default Apache config. We front phpMyAdmin with Nginx + PHP-FPM.

---

## 2) Create MySQL users per tenant

For each tenant `<slug>` and database `<db>`:

```sql
CREATE USER 'pma_<slug>'@'%' IDENTIFIED BY 'STRONG_PASS';
GRANT ALL PRIVILEGES ON `<db>`.* TO 'pma_<slug>'@'%';
FLUSH PRIVILEGES;
```

- Use a strong password (e.g. `openssl rand -base64 18`).
- **Never** grant global privileges; limit to the tenant DB only.
- For MySQL on the same host, `@'localhost'` is more restrictive than `@'%'`; use `@'localhost'` when possible.

Variables (optional, for scripts):

```
TENANT_SLUG=<slug>
TENANT_DB=<db_name>
PMA_DB_USER=pma_${TENANT_SLUG}
PMA_DB_PASS=$(openssl rand -base64 18)
```

```bash
mysql -u root <<SQL
CREATE USER IF NOT EXISTS '${PMA_DB_USER}'@'%' IDENTIFIED BY '${PMA_DB_PASS}';
GRANT ALL PRIVILEGES ON \`${TENANT_DB}\`.* TO '${PMA_DB_USER}'@'%';
FLUSH PRIVILEGES;
SQL
```

---

## 3) Nginx vhost per tenant (`pma.<domain>`)

Create `/etc/nginx/sites-available/pma-<slug>.conf`:

```nginx
server {
    listen 80;
    server_name pma.<tenant-domain>;

    # Optional IP allowlist
    # allow 203.0.113.10;
    # deny all;

    # Basic auth (create with: htpasswd -c /etc/nginx/pma-<slug>.htpasswd admin)
    auth_basic "phpMyAdmin";
    auth_basic_user_file /etc/nginx/pma-<slug>.htpasswd;

    root /usr/share/phpmyadmin;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~* \.(ht|git) { deny all; }
}
```

Replace `<slug>` and `<tenant-domain>` with the tenant slug and actual domain (e.g. `pma.tenant1.example.com`).

Enable and reload:

```bash
ln -s /etc/nginx/sites-available/pma-<slug>.conf /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

## 4) Basic auth per tenant

```bash
apt install -y apache2-utils   # for htpasswd
htpasswd -c /etc/nginx/pma-<slug>.htpasswd admin
chown www-data:www-data /etc/nginx/pma-<slug>.htpasswd
```

Use a strong password for the web login. You can reuse the same password as the MySQL user or keep them separate (recommended: separate).

---

## 5) Point phpMyAdmin to correct DB host

If MySQL is local, phpMyAdmin can stay on `127.0.0.1`. If tenants use distinct hosts, set in `/usr/share/phpmyadmin/config.inc.php`:

```php
$cfg['Servers'][1]['host'] = '127.0.0.1';
```

Users log in with their per-tenant MySQL credentials (`pma_<slug>` / password), so they only see that tenant’s database.

---

## 6) HTTPS (recommended)

Use your existing Certbot/Cloudflare flow or a self-signed cert:

- Add `listen 443 ssl;` (and `http2` if desired).
- Set `ssl_certificate` and `ssl_certificate_key`.
- Redirect HTTP to HTTPS for `pma.<domain>`.

---

## 7) Quick checklist per tenant

- [ ] MySQL user created and limited to that tenant’s DB only.
- [ ] Basic auth enabled and htpasswd file secured.
- [ ] Optional IP allowlist enabled if required.
- [ ] HTTPS enforced and HTTP redirected to HTTPS.
- [ ] `nginx -t` passes and Nginx reloaded.

---

## 8) Clean-up / remove tenant phpMyAdmin

```bash
rm /etc/nginx/sites-enabled/pma-<slug>.conf
rm /etc/nginx/sites-available/pma-<slug>.conf
rm /etc/nginx/pma-<slug>.htpasswd
nginx -t && systemctl reload nginx
```

Then drop the MySQL user:

```sql
DROP USER 'pma_<slug>'@'%';
```

If you created the user with `@'localhost'`, use:

```sql
DROP USER 'pma_<slug>'@'localhost';
```

---

## Security notes

- Each `pma_<slug>` user is scoped to one database; do not reuse across tenants.
- Use an IP allowlist in the Nginx server block when possible.
- Keep phpMyAdmin updated via system packages (`apt update && apt upgrade phpmyadmin`).
