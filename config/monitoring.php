<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Error Tracking
    |--------------------------------------------------------------------------
    */

    'error_tracking' => [
        'enabled' => env('ERROR_TRACKING_ENABLED', true),
        'log_levels' => ['debug', 'info', 'warning', 'error', 'critical'],
        'alert_threshold' => env('ERROR_ALERT_THRESHOLD', 5), // errors in 5 minutes
        'alert_email' => env('ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
        'slow_threshold' => env('SLOW_REQUEST_THRESHOLD', 1000), // milliseconds
        'log_queries' => env('LOG_QUERIES', false),
        'cleanup_after_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    */

    'health' => [
        'enabled' => env('HEALTH_CHECKS_ENABLED', true),
        'database_timeout' => 5, // seconds
        'redis_timeout' => 3, // seconds
        'storage_warning_threshold' => 90, // percent
        'queue_warning_threshold' => 1000, // pending jobs
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerts
    |--------------------------------------------------------------------------
    */

    'alerts' => [
        'email_enabled' => env('ALERTS_EMAIL_ENABLED', true),
        'slack_enabled' => env('ALERTS_SLACK_ENABLED', false),
        'slack_webhook' => env('SLACK_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prometheus Exporter
    |--------------------------------------------------------------------------
    */

    'prometheus' => [
        'enabled' => env('PROMETHEUS_ENABLED', true),
        'token' => env('PROMETHEUS_TOKEN', ''),
    ],

];
