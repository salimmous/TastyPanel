# Monitoring Setup Guide

## Overview

TastyPanel includes comprehensive monitoring for production environments:

- **Health Checks**: System status endpoints
- **Error Tracking**: Automatic error logging with alerts
- **Performance Monitoring**: Response time and resource tracking
- **Alerting**: Email notifications for critical issues

---

## Health Check Endpoints

### Available Endpoints

```bash
# Overall health
GET /health

# Individual services
GET /health/database
GET /health/redis
GET /health/storage
GET /health/queue
```

### Response Format

```json
{
  "status": "healthy|degraded|down",
  "timestamp": "2024-02-06T13:24:17Z",
  "checks": {
    "database": {
      "status": "up",
      "response_time": "5ms",
      "message": "Database is accessible"
    },
    "redis": {
      "status": "up",
      "response_time": "2ms"  
    },
    "storage": {
      "status": "up",
      "free_space": "15GB",
      "used_percent": "45%"
    },
    "queue": {
      "status": "up",
      "pending_jobs": 5,
      "failed_jobs": 0
    }
  }
}
```

---

## Error Tracking

### Automatic Error Logging

All errors are automatically logged to the `error_logs` table with:

- Error type and message
- Stack trace
- User context
- Request details (URL, IP, user agent)
- Environment information

### Email Alerts

Critical errors trigger automatic email alerts when:
- Error level is `critical`
- 5+ errors occur within 5 minutes

### Configuration

Edit `config/monitoring.php`:

```php
'error_tracking' => [
    'enabled' => true,
    'alert_threshold' => 5, // errors in 5 minutes
    'alert_email' => env('ADMIN_EMAIL'),
],
```

### View Error Logs

```bash
# Via Tinker
php artisan tinker
>>> App\Models\ErrorLog::latest()->take(10)->get();

# Get error stats
>>> app(App\Services\ErrorTrackerService::class)->getStats(24);
```

---

## Performance Monitoring

### Automatic Metrics Collection

Every request is tracked with:
- Response time (ms)
- Memory usage
- Database query count
- Cache hits/misses

### Slow Request Detection

Requests slower than 1000ms are flagged as `is_slow`.

### Configuration

```php
'performance' => [
    'enabled' => true,
    'slow_threshold' => 1000, // milliseconds
    'cleanup_after_days' => 30,
],
```

### Performance Stats

```bash
php artisan tinker
>>> $service = app(App\Services\PerformanceMonitorService::class);
>>> $service->getStats(24); // Last 24 hours
>>> $service->getSlowestEndpoints(10);
```

---

## Health Monitoring Command

### Manual Check

```bash
php artisan monitor:health
```

Output:
```
✓ database: Database is accessible
  Response Time: 5ms
✓ redis: Redis is accessible
  Response Time: 2ms
✓ storage: Storage is healthy
✓ queue: Queue is healthy

Overall Status: healthy
```

### With Alerts

```bash
php artisan monitor:health --alert
```

Sends email alert if status is degraded or down.

### Automated Monitoring

Add to crontab:

```bash
# Check health every 5 minutes
*/5 * * * * cd /var/www/tastypanel && php artisan monitor:health --alert >> /dev/null 2>&1
```

---

## Uptime Monitoring

### External Services (Recommended)

Use third-party services for best reliability:

**UptimeRobot** (Free):
- Monitor: `https://yourdomain.com/health`
- Interval: 5 minutes
- Alerts: Email, SMS, Slack

**Pingdom**:
- Monitor health endpoint
- Global monitoring locations
- Detailed performance reports

**BetterUptime**:
- Incident management
- Status pages
- Advanced alerting

### Configuration

1. Create account on chosen service
2. Add monitor for: `https://yourdomain.com/health`
3. Set check interval: 5 minutes
4. Configure alerts (email, Slack, etc.)

---

## Log Management

### Laravel Logs

```bash
# View logs
tail -f storage/logs/laravel.log

# Error logs only
grep "ERROR" storage/logs/laravel.log

# Search for specific error
grep "DatabaseException" storage/logs/laravel.log
```

### Log Rotation

Automatic daily rotation is configured in `config/logging.php`.

Logs are kept for 14 days by default.

### Manual Cleanup

```bash
# Clear old logs
find storage/logs -name "*.log" -mtime +30 -delete

# Clear error logs older than 30 days
php artisan tinker
>>> App\Models\ErrorLog::where('created_at', '<', now()->subDays(30))->delete();

# Clear performance metrics
>>> App\Models\PerformanceMetric::where('created_at', '<', now()->subDays(30))->delete();
```

---

## Metrics Dashboard (Optional)

### Using Grafana + Prometheus

1. **Install Prometheus Exporter**:
```bash
composer require spatie/laravel-prometheus
```

2. **Configure metrics endpoint**:
```php
// routes/web.php
Route::get('/metrics', function () {
    return app(\Spatie\Prometheus\PrometheusManager::class)->render();
});
```

3. **Setup Grafana**:
- Import Laravel dashboard
- Add data source: Prometheus
- Configure alerts

---

## Alert Configuration

### Email Alerts

Set in `.env`:

```env
ADMIN_EMAIL=admin@yourdomain.com
ERROR_ALERT_THRESHOLD=5
ALERTS_EMAIL_ENABLED=true
```

### Slack Alerts (Optional)

```env
ALERTS_SLACK_ENABLED=true
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

Create `AlertService` integration:

```php
// config/monitoring.php
'alerts' => [
    'slack_enabled' => env('ALERTS_SLACK_ENABLED', false),
    'slack_webhook' => env('SLACK_WEBHOOK_URL'),
],
```

---

## Troubleshooting

### Health Check Fails

```bash
# Check services
sudo systemctl status mysql
sudo systemctl status redis
sudo systemctl status nginx

# Check disk space
df -h

# Check queue workers
sudo supervisorctl status tastypanel-worker:*
```

### High Error Rate

```bash
# View recent errors
php artisan tinker
>>> App\Models\ErrorLog::where('created_at', '>', now()->subHour())->get();

# Check specific error
>>> App\Models\ErrorLog::where('type', 'DatabaseException')->latest()->first();
```

### Performance Issues

```bash
# Check slow endpoints
php artisan tinker
>>> app(App\Services\PerformanceMonitorService::class)->getSlowestEndpoints();

# Enable query logging
>>> DB::enableQueryLog();
>>> // Run your request
>>> DB::getQueryLog();
```

---

## Best Practices

1. **Monitor Health Endpoints**: Use external monitoring service
2. **Review Error Logs Daily**: Check for patterns
3. **Set Up Alerts**: Email + Slack for critical issues
4. **Regular Cleanup**: Remove old logs/metrics
5. **Performance Audits**: Review slow endpoints monthly
6. **Test Alerts**: Verify email delivery works
7. **Document Incidents**: Track issues and resolutions

---

## Support

- Health Status: `/health`
- Error Logs: Database table `error_logs`
- Performance Metrics: Database table `performance_metrics`
- Command: `php artisan monitor:health --alert`
