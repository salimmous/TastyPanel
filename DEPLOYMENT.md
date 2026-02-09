# Production Deployment Guide

## Prerequisites

- Ubuntu 20.04+ or Debian 11+
- Root/sudo access
- Domain name pointed to server
- 2GB+ RAM recommended

---

## Quick Deploy (Recommended)

### One-Command Installation

```bash
curl -sSL https://install.tastypanel.site | sudo bash
```

Or with custom domain:

```bash
sudo bash install.sh --domain=yourdomain.com --admin-email=admin@yourdomain.com
```

**Installation time:** < 10 minutes

---

## Manual Deployment

### 1. Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y git curl wget unzip
```

### 2. Install Stack

```bash
# PHP 8.1+
sudo add-apt-repository ppa:ondrej/php
sudo apt install -y php8.1-fpm php8.1-cli php8.1-mysql php8.1-redis \
  php8.1-mbstring php8.1-xml php8.1-bcmath php8.1-curl php8.1-zip \
  php8.1-gd php8.1-intl

# MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Redis
sudo apt install -y redis-server

# Nginx
sudo apt install -y nginx

# Node.js 18+
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo bash -
sudo apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Database Setup

```bash
sudo mysql -e "CREATE DATABASE tastypanel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'tastypanel'@'localhost' IDENTIFIED BY 'secure_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON tastypanel.* TO 'tastypanel'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### 4. Application Setup

```bash
# Clone/upload project
cd /var/www
sudo git clone https://github.com/YOUR_REPO/tastypanel.git
cd tastypanel

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run build

# Environment
sudo cp .env.example .env
sudo nano .env  # Configure database, mail, etc.

# Generate key
sudo -u www-data php artisan key:generate

# Run migrations
sudo -u www-data php artisan migrate --force

# Seed demo data (optional)
sudo -u www-data php artisan db:seed --class=DemoDataSeeder

# Link storage
sudo -u www-data php artisan storage:link

# Cache config
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### 5. Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/tastypanel
```

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/tastypanel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/tastypanel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 6. SSL Certificate

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com --agree-tos -m admin@yourdomain.com
```

### 7. Queue Workers

```bash
sudo apt install supervisor

sudo nano /etc/supervisor/conf.d/tastypanel-worker.conf
```

```ini
[program:tastypanel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/tastypanel/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/tastypanel/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start tastypanel-worker:*
```

### 8. Task Scheduler

```bash
crontab -e
```

Add:
```
* * * * * cd /var/www/tastypanel && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Permissions

```bash
sudo chown -R www-data:www-data /var/www/tastypanel
sudo chmod -R 755 /var/www/tastypanel
sudo chmod -R 775 /var/www/tastypanel/storage /var/www/tastypanel/bootstrap/cache
```

---

## Environment Configuration

### Required `.env` Variables

```env
APP_NAME="TastyPanel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tastypanel
DB_USERNAME=tastypanel
DB_PASSWORD=your_secure_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Post-Deployment Checks

### 1. Health Check

```bash
curl https://yourdomain.com/health
```

Expected response:
```json
{
  "status": "healthy",
  "checks": {
    "database": {"status": "up"},
    "redis": {"status": "up"},
    "storage": {"status": "up"},
    "queue": {"status": "up"}
  }
}
```

### 2. Test Application

- Visit: `https://yourdomain.com`
- Login as admin
- Create a test recipe
- Test file upload
- Check email sending

### 3. Monitor Logs

```bash
tail -f /var/www/tastypanel/storage/logs/laravel.log
tail -f /var/www/tastypanel/storage/logs/worker.log
```

---

## Updating the Application

```bash
cd /var/www/tastypanel

# Backup database
sudo -u www-data php artisan backup:run

# Pull latest code
git pull

# Update dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run build

# Run migrations
sudo -u www-data php artisan migrate --force

# Clear & rebuild cache
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Restart workers
sudo supervisorctl restart tastypanel-worker:*
```

---

## Performance Tuning

### OPcache Configuration

Edit `/etc/php/8.1/fpm/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

### Nginx Optimization

```nginx
# In http block
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

client_max_body_size 20M;
fastcgi_buffers 16 16k;
fastcgi_buffer_size 32k;
```

---

## Security Hardening

### Firewall

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Fail2ban

```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
```

---

## Monitoring Setup

### Health Monitoring Cron

```bash
*/5 * * * * cd /var/www/tastypanel && php artisan monitor:health --alert >> /dev/null 2>&1
```

### Log Rotation

Create `/etc/logrotate.d/tastypanel`:

```
/var/www/tastypanel/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

---

## Backup Strategy

### Automated Backups

```bash
# Daily database backup
0 2 * * * cd /var/www/tastypanel && php artisan backup:run >> /dev/null 2>&1
```

### Manual Backup

```bash
# Database
sudo -u www-data php artisan backup:run

# Files
tar -czf tastypanel-backup-$(date +%Y%m%d).tar.gz /var/www/tastypanel
```

---

## Support

- Documentation: https://docs.tastypanel.site
- Health Status: https://yourdomain.com/health
- Issues: support@tastypanel.site
