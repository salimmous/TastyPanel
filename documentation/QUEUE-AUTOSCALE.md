# Queue Autoscale (example)

Objective: use queue depth to scale workers per tenant.

Components:
- Decision logic: `app/Services/TenantQueueProfileService.php`
- CLI: `php artisan tenant:queue:autoscale` (optional `--tenant=<id>`)
- Profiles: table `tenant_queue_profiles` (min/max workers, thresholds)

Supervisor example (runs autoscale every minute via cron; supervisor manages workers per queue):
```
[program:tastypanel-autoscale]
command=/usr/bin/php /var/www/tastypanel/artisan tenant:queue:autoscale
autostart=true
autorestart=true
user=www-data
numprocs=1
stdout_logfile=/var/log/supervisor/autoscale.log
redirect_stderr=true
```

Cron example (decide & act via script):
```
* * * * * www-data php /var/www/tastypanel/artisan tenant:queue:autoscale >> /var/log/tastypanel-autoscale.log 2>&1
```

Note: actual worker scaling remains up to your process manager (supervisor/systemd/ecs). Use the autoscale output to adjust `numprocs` or desired replicas externally.
