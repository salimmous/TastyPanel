# TastyPanel - Production Deployment Guide

## 🚀 Deployment Options

### Option 1: Quick Install (Recommended for VPS)
```bash
sudo ./install.sh --domain=tastypanel.example.com
```

### Option 2: Docker Compose
```bash
docker-compose -f docker-compose.prod.yml up -d
```

### Option 3: Kubernetes
```bash
kubectl apply -f k8s/
```

---

## 📋 Pre-Deployment Checklist

### Server Requirements
- [ ] Ubuntu 22.04 LTS or Debian 12
- [ ] 4GB RAM minimum (8GB recommended)
- [ ] 20GB SSD storage
- [ ] Domain configured with DNS pointing to server

### Security
- [ ] SSH key-only authentication
- [ ] Firewall configured (UFW)
- [ ] Fail2ban installed
- [ ] Non-root user with sudo

---

## 🐳 Docker Deployment (Recommended)

### docker-compose.prod.yml
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.prod
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=db
      - REDIS_HOST=redis
    volumes:
      - storage:/var/www/html/storage
    depends_on:
      - db
      - redis
    networks:
      - tastypanel

  web:
    image: nginx:alpine
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.prod.conf:/etc/nginx/conf.d/default.conf
      - ./ssl:/etc/nginx/ssl
      - storage:/var/www/html/storage
    depends_on:
      - app
    networks:
      - tastypanel

  db:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - tastypanel

  redis:
    image: redis:7-alpine
    restart: unless-stopped
    volumes:
      - redis_data:/data
    networks:
      - tastypanel

  queue:
    build:
      context: .
      dockerfile: Dockerfile.prod
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3
    depends_on:
      - db
      - redis
    networks:
      - tastypanel

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile.prod
    restart: unless-stopped
    command: sh -c "while true; do php artisan schedule:run; sleep 60; done"
    depends_on:
      - db
      - redis
    networks:
      - tastypanel

volumes:
  storage:
  mysql_data:
  redis_data:

networks:
  tastypanel:
    driver: bridge
```

### Dockerfile.prod
```dockerfile
FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    nginx supervisor \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev icu-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip intl bcmath opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copy application
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# PHP production settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-custom.ini"

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

---

## 🔧 CI/CD Pipeline

### GitHub Actions (.github/workflows/deploy.yml)
```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, bcmath, redis
          
      - name: Install Dependencies
        run: composer install --no-dev
        
      - name: Run Tests
        run: php artisan test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/tastypanel
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo supervisorctl restart tastypanel-worker:*
```

---

## 🔒 Production Security

### Environment Variables
```env
# .env.production
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_SECURE_KEY

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=tastypanel_prod
DB_USERNAME=tastypanel
DB_PASSWORD=SECURE_PASSWORD_HERE

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Security
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
ADMIN_IP_WHITELIST=YOUR_IP,OFFICE_IP

# Performance
OPCACHE_ENABLE=1
```

### Nginx Security Headers
```nginx
# Add to server block
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;

# HTTPS only
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

---

## 📊 Monitoring

### Health Check Endpoint
```bash
curl https://your-domain.com/api/health
```

### Log Locations
```
/var/www/tastypanel/storage/logs/laravel.log
/var/www/tastypanel/storage/logs/worker.log
/var/log/nginx/access.log
/var/log/nginx/error.log
```

### Supervisor Commands
```bash
# Check status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart tastypanel-worker:*

# View logs
sudo tail -f /var/www/tastypanel/storage/logs/worker.log
```

---

## 🔄 Backup Strategy

### Automated Backups (Cron)
```bash
# /etc/cron.d/tastypanel-backup
0 3 * * * www-data cd /var/www/tastypanel && php artisan backup:run
0 4 * * 0 www-data cd /var/www/tastypanel && php artisan backup:clean
```

### Manual Backup
```bash
# Database
mysqldump -u root tastypanel_prod | gzip > backup_$(date +%Y%m%d).sql.gz

# Files
tar -czf storage_$(date +%Y%m%d).tar.gz storage/
```

---

## 🆙 Zero-Downtime Updates

```bash
#!/bin/bash
# deploy.sh

cd /var/www/tastypanel

# Maintenance mode
php artisan down --retry=60

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue workers
sudo supervisorctl restart tastypanel-worker:*

# Exit maintenance mode
php artisan up

echo "✅ Deployment complete!"
```

---

## 🌍 Multi-Region Deployment

### DNS Load Balancing
```
tastypanel.com      A    1.2.3.4 (US)
tastypanel.com      A    5.6.7.8 (EU)
api.tastypanel.com  A    1.2.3.4
*.tastypanel.com    CNAME tastypanel.com
```

### Database Replication
```yaml
# Primary (Write)
DB_HOST=primary.db.internal

# Read Replicas
DB_HOST_READ=replica1.db.internal,replica2.db.internal
```

---

## ✅ Post-Deployment Checklist

- [ ] SSL certificate active and auto-renewing
- [ ] Admin login working
- [ ] Queue workers running
- [ ] Scheduler active
- [ ] Backups configured
- [ ] Monitoring enabled
- [ ] Error reporting configured
- [ ] First tenant created
- [ ] Email sending working
- [ ] Security headers verified

---

## 🆘 Troubleshooting

### 502 Bad Gateway
```bash
sudo systemctl restart php8.2-fpm nginx
```

### Queue Not Processing
```bash
sudo supervisorctl restart tastypanel-worker:*
tail -f storage/logs/worker.log
```

### Permission Denied
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Clear All Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```
