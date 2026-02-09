# Troubleshooting Guide

## Common Issues & Solutions

---

## Installation Issues

### Issue: Composer Install Fails

**Symptoms:**
```
Your requirements could not be resolved to an installable set of packages.
```

**Solutions:**
```bash
# Update composer
composer self-update

# Clear cache
composer clear-cache

# Try with --no-scripts
composer install --no-scripts --no-dev

# Check PHP version
php -v  # Must be 8.1+
```

### Issue: NPM Build Fails

**Symptoms:**
```
npm ERR! ENOENT: no such file or directory
```

**Solutions:**
```bash
# Clear npm cache
npm cache clean --force

# Delete node_modules and reinstall
rm -rf node_modules package-lock.json
npm install

# Try with legacy peer deps
npm install --legacy-peer-deps
```

---

## Database Issues

### Issue: Connection Refused

**Symptoms:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Solutions:**
```bash
# Check MySQL is running
sudo systemctl status mysql
sudo systemctl start mysql

# Verify credentials in .env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tastypanel
DB_USERNAME=tastypanel
DB_PASSWORD=your_password

# Test connection
mysql -u tastypanel -p tastypanel
```

### Issue: Migration Fails

**Symptoms:**
```
SQLSTATE[42S01]: Base table or view already exists
```

**Solutions:**
```bash
# Reset migrations (⚠️ DELETES ALL DATA)
php artisan migrate:fresh

# Or rollback and re-run
php artisan migrate:rollback
php artisan migrate

# Check migration status
php artisan migrate:status
```

### Issue: Foreign Key Constraint Fails

**Solutions:**
```bash
# Ensure migrations run in correct order
# Check migration files are properly timestamped

# Disable foreign key checks temporarily (use with caution)
php artisan tinker
>>> DB::statement('SET FOREIGN_KEY_CHECKS=0;');
>>> // Run your migration/seeder
>>> DB::statement('SET FOREIGN_KEY_CHECKS=1;');
```

---

## Redis Issues

### Issue: Connection Timeout

**Symptoms:**
```
Connection to Redis failed: Connection timed out
```

**Solutions:**
```bash
# Check Redis is running
sudo systemctl status redis
sudo systemctl start redis

# Test connection
redis-cli ping
# Should return: PONG

# Check .env configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Clear Redis cache
redis-cli FLUSHALL
```

---

## Queue Issues

### Issue: Jobs Not Processing

**Symptoms:**
- Jobs stuck in queue
- No worker output

**Solutions:**
```bash
# Check supervisor status
sudo supervisorctl status tastypanel-worker:*

# Restart workers
sudo supervisorctl restart tastypanel-worker:*

# Check worker logs
tail -f storage/logs/worker.log

# Manually process queue
php artisan queue:work --once

# Clear failed jobs
php artisan queue:flush
```

### Issue: Too Many Failed Jobs

**Solutions:**
```bash
# View failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry JOB_ID

# Delete all failed jobs
php artisan queue:flush
```

---

## Permission Issues

### Issue: Storage Not Writable

**Symptoms:**
```
file_put_contents(): failed to open stream: Permission denied
```

**Solutions:**
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/tastypanel
sudo chmod -R 755 /var/www/tastypanel
sudo chmod -R 775 /var/www/tastypanel/storage
sudo chmod -R 775 /var/www/tastypanel/bootstrap/cache

# Create storage link
php artisan storage:link
```

---

## Email Issues

### Issue: Emails Not Sending

**Solutions:**
```bash
# Test email configuration
php artisan tinker
>>> Mail::raw('Test email', function($msg) {
...   $msg->to('test@example.com')->subject('Test');
... });

# Check .env settings
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_app_password  # Use App Password for Gmail
MAIL_ENCRYPTION=tls

# Check mail queue
php artisan queue:work --queue=emails

# View mail logs
tail -f storage/logs/laravel.log | grep "Swift_TransportException"
```

### Issue: Gmail "Less Secure Apps" Error

**Solution:**
Use App Password instead:
1. Enable 2FA on Gmail
2. Generate App Password
3. Use App Password in MAIL_PASSWORD

---

## Performance Issues

### Issue: Slow Page Load

**Solutions:**
```bash
# Enable OPcache
# Edit /etc/php/8.1/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=256

# Clear and rebuild caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check slow queries
php artisan tinker
>>> DB::enableQueryLog();
>>> // Run your page request
>>> DB::getQueryLog();

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Issue: High Memory Usage

**Solutions:**
```bash
# Increase PHP memory limit
# Edit php.ini
memory_limit = 512M

# Optimize autoloader
composer dump-autoload --optimize

# Clear unused data
php artisan cache:clear
php artisan view:clear
```

---

## SSL/HTTPS Issues

### Issue: SSL Certificate Not Working

**Solutions:**
```bash
# Renew certificate
sudo certbot renew

# Force HTTPS redirect in Nginx
# Add to server block:
if ($scheme != "https") {
    return 301 https://$host$request_uri;
}

# Restart Nginx
sudo systemctl reload nginx

# Check certificate
sudo certbot certificates
```

### Issue: Mixed Content (HTTP/HTTPS)

**Solutions:**
```bash
# Force HTTPS in .env
APP_URL=https://yourdomain.com

# Add to .env
ASSET_URL=https://yourdomain.com

# Clear config
php artisan config:cache
```

---

## Import/Export Issues

### Issue: Import Timeout

**Solutions:**
```bash
# Increase PHP timeout
# Edit php.ini
max_execution_time = 600
max_input_time = 600

# Process imports in queue
# Ensure queue workers are running
sudo supervisorctl status tastypanel-worker:*

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Issue: File Upload Limit

**Solutions:**
```bash
# Increase upload limits
# Edit php.ini
upload_max_filesize = 50M
post_max_size = 50M

# Update Nginx
# Edit site config
client_max_body_size 50M;

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl reload nginx
```

---

## Search Issues

### Issue: Meilisearch Not Working

**Solutions:**
```bash
# Check Meilisearch is running
curl http://127.0.0.1:7700/health

# Restart Meilisearch
sudo systemctl restart meilisearch

# Reindex data
php artisan scout:import "App\Models\Recipe"

# Clear search index
php artisan scout:flush "App\Models\Recipe"
php artisan scout:import "App\Models\Recipe"
```

---

## Session Issues

### Issue: Logged Out Frequently

**Solutions:**
```bash
# Check session driver in .env
SESSION_DRIVER=redis  # or database, file

# Increase session lifetime
# Edit config/session.php
'lifetime' => 120,  # minutes

# Clear sessions
php artisan session:flush

# Restart Redis (if using redis driver)
sudo systemctl restart redis
```

---

## 404 Errors

### Issue: Routes Not Found

**Solutions:**
```bash
# Clear route cache
php artisan route:clear
php artisan route:cache

# Check Nginx configuration
sudo nginx -t

# Verify public directory
# Nginx root should point to /var/www/tastypanel/public

# Check .htaccess (if using Apache)
# Ensure mod_rewrite is enabled
```

---

## Health Check Failures

### Issue: /health Returns 503

**Solutions:**
```bash
# Run health check manually
php artisan monitor:health

# Check individual services
curl http://localhost/health/database
curl http://localhost/health/redis
curl http://localhost/health/storage
curl http://localhost/health/queue

# Check service status
sudo systemctl status mysql
sudo systemctl status redis
sudo systemctl status nginx
```

---

## Debugging Tips

### Enable Debug Mode (NEVER in production)

```env
APP_DEBUG=true
```

### View Detailed Errors

```bash
tail -f storage/logs/laravel.log
```

### Use Tinker for Testing

```bash
php artisan tinker

# Test database
>>> DB::connection()->getPdo();

# Test Redis
>>> Redis::ping();

# Test model
>>> App\Models\Recipe::count();

# Test service
>>> app(App\Services\SearchService::class)->search('pasta');
```

### Clear All Caches

```bash
php artisan optimize:clear
```

This clears:
- Config cache
- Route cache
- View cache
- Event cache
- Compiled classes

---

## Getting Help

### Collect Information

Before asking for help, gather:

```bash
# PHP version
php -v

# Laravel version
php artisan --version

# Environment info
php artisan env

# Error logs (last 50 lines)
tail -50 storage/logs/laravel.log

# System info
df -h  # Disk space
free -h  # Memory
uname -a  # System info
```

### Support Channels

- GitHub Issues: [YOUR_REPO/issues]
- Email: support@tastypanel.site
- Documentation: https://docs.tastypanel.site

---

## Prevention

### Regular Maintenance

```bash
# Weekly tasks
php artisan cache:clear
php artisan queue:flush  # Clear failed jobs
php artisan backup:run

# Monthly tasks
# Review error logs
# Update dependencies
composer update
npm update

# Monitor disk space
df -h

# Clean old logs
find storage/logs -name "*.log" -mtime +30 -delete
```

### Monitoring

```bash
# Setup automated health checks
crontab -e

# Add
*/5 * * * * cd /var/www/tastypanel && php artisan monitor:health --alert
```
