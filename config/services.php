<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'phpmyadmin' => [
        // Single URL for phpMyAdmin (same host as panel, e.g. http://84.247.160.84:8443/phpmyadmin/). When set, all tenants use this URL and log in with their DB user.
        'url' => env('PMA_URL'),
        // If PMA_URL is not set, path is appended to app URL (e.g. /phpmyadmin → http://panel:8443/phpmyadmin).
        'path' => env('PMA_PATH', '/phpmyadmin'),
        // Legacy: per-tenant subdomain template (e.g. "https://pma.:domain"). Ignored when PMA_URL or PMA_PATH is used.
        'url_template' => env('PMA_URL_TEMPLATE'),
        // Provision script (creates MySQL user pma_<slug>, optional Nginx vhost). For single-URL mode only MySQL user is needed.
        'provision_script' => env('PMA_PROVISION_SCRIPT', base_path('infrastructure/provision-phpmyadmin-tenant.sh')),
        'provision_use_sudo' => env('PMA_PROVISION_USE_SUDO', true),
    ],

    'openai' => [
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    'canva' => [
        'client_id' => env('CANVA_CLIENT_ID'),
        'client_secret' => env('CANVA_CLIENT_SECRET'),
        'redirect_uri' => env('CANVA_REDIRECT_URI'),
        'auth_url' => env('CANVA_AUTH_URL', 'https://www.canva.com/api/oauth/authorize'),
        'token_url' => env('CANVA_TOKEN_URL', 'https://api.canva.com/rest/v1/oauth/token'),
        'api_base' => env('CANVA_API_BASE', 'https://api.canva.com/rest/v1'),
        'scopes' => env('CANVA_SCOPES', 'design:meta:read asset:read asset:write folder:read comment:write'),
    ],

    'discord' => [
        'base_url' => env('DISCORD_API_BASE', 'https://discord.com/api/v10'),
    ],

    'pinterest' => [
        'base_url' => env('PINTEREST_API_BASE', 'https://api.pinterest.com/v5'),
    ],

    'cloudflare' => [
        'token' => env('CLOUDFLARE_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'target_ip' => env('TENANT_TARGET_IP', '127.0.0.1'),
        'dns_token' => env('CLOUDFLARE_DNS_TOKEN', env('CLOUDFLARE_TOKEN')),
    ],

    'ssl' => [
        'auto' => env('SSL_AUTO', false),
        'provider' => env('SSL_PROVIDER', 'letsencrypt'),
        'certbot_path' => env('SSL_CERTBOT_PATH', 'certbot'),
        'certbot_email' => env('SSL_CERTBOT_EMAIL'),
        'propagation_seconds' => env('SSL_PROPAGATION_SECONDS', 30),
    ],

    'infrastructure' => [
        'auto_nginx' => env('AUTO_NGINX', false),
        'nginx_script' => env('NGINX_PROVISION_SCRIPT', base_path('infrastructure/provision-nginx.sh')),
        'nginx_use_sudo' => env('NGINX_USE_SUDO', true),
        'nginx_available_dir' => env('NGINX_AVAILABLE_DIR', '/etc/nginx/sites-available'),
        'nginx_enabled_dir' => env('NGINX_ENABLED_DIR', '/etc/nginx/sites-enabled'),
        'web_root' => env('TENANT_WEB_ROOT', '/var/www/tastypanel/public'),
        'php_fpm_socket' => env('PHP_FPM_SOCKET', '/run/php/php8.3-fpm.sock'),
        'nginx_safe_deploy_script' => env('NGINX_SAFE_DEPLOY_SCRIPT', base_path('infrastructure/deploy-nginx-safe.sh')),
        'nginx_safe_deploy_use_sudo' => env('NGINX_SAFE_DEPLOY_USE_SUDO', true),
        'nginx_deploy_backup_root' => env('NGINX_DEPLOY_BACKUP_ROOT', '/var/backups/tastypanel-nginx'),
    ],

    'instances' => [
        'root' => env('TENANT_INSTANCES_ROOT', '/var/www/tastypanel-sites'),
        'repo' => env('TENANT_APP_REPO', ''),
        'branch' => env('TENANT_APP_BRANCH', 'main'),
        'php_version' => env('TENANT_PHP_VERSION', '8.3'),
        'script' => env('INSTANCE_PROVISION_SCRIPT', base_path('infrastructure/provision-instance.sh')),
        'use_sudo' => env('INSTANCE_USE_SUDO', true),
        'deprovision_script' => env('INSTANCE_DEPROVISION_SCRIPT', base_path('infrastructure/deprovision-instance.sh')),
        'deprovision_use_sudo' => env('INSTANCE_DEPROVISION_USE_SUDO', true),
        'system_user_prefix' => env('INSTANCE_SYSTEM_USER_PREFIX', 'tbapp'),
        'fpm_max_children' => env('INSTANCE_FPM_MAX_CHILDREN', 10),
        'fpm_max_requests' => env('INSTANCE_FPM_MAX_REQUESTS', 500),
        'fpm_memory_limit_mb' => env('INSTANCE_FPM_MEMORY_LIMIT_MB', 256),
        'orchestrate_script' => env('INSTANCE_ORCHESTRATE_SCRIPT', base_path('infrastructure/orchestrate-tenant.sh')),
        'orchestrate_use_sudo' => env('INSTANCE_ORCHESTRATE_USE_SUDO', true),
        'clone_script' => env('INSTANCE_CLONE_SCRIPT', base_path('infrastructure/clone-tenant.sh')),
        'clone_use_sudo' => env('INSTANCE_CLONE_USE_SUDO', true),
        'env_sync_script' => env('TENANT_ENV_SYNC_SCRIPT', base_path('infrastructure/sync-tenant-env.sh')),
        'env_sync_use_sudo' => env('TENANT_ENV_SYNC_USE_SUDO', true),
        'frontend_auto' => env('FRONTEND_AUTO', false),
        'frontend_script' => env('FRONTEND_PROVISION_SCRIPT', base_path('infrastructure/provision-frontend.sh')),
        'frontend_deprovision_script' => env('FRONTEND_DEPROVISION_SCRIPT', base_path('infrastructure/deprovision-frontend.sh')),
        'frontend_use_sudo' => env('FRONTEND_PROVISION_USE_SUDO', true),
        'frontend_api_base' => env('FRONTEND_PLATFORM_API_BASE', rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/api'),
        'access_script' => env('TENANT_ACCESS_SCRIPT', base_path('infrastructure/provision-tenant-access.sh')),
        'access_use_sudo' => env('TENANT_ACCESS_USE_SUDO', true),
        'access_auth_mode' => env('TENANT_ACCESS_AUTH_MODE', 'both'),
        'access_sftp_only' => env('TENANT_ACCESS_SFTP_ONLY', false),
    ],

    'tenant_backups' => [
        'root' => env('TENANT_BACKUP_ROOT', storage_path('app/tenant-backups')),
        'script' => env('TENANT_BACKUP_SCRIPT', base_path('infrastructure/backup-tenant.sh')),
        'restore_script' => env('TENANT_RESTORE_SCRIPT', base_path('infrastructure/restore-tenant.sh')),
        'use_sudo' => env('TENANT_BACKUP_USE_SUDO', false),
    ],

    'tenant_queue' => [
        'script' => env('TENANT_QUEUE_SCRIPT', base_path('infrastructure/queue-tenant.sh')),
        'use_sudo' => env('TENANT_QUEUE_USE_SUDO', false),
    ],

    'tenant_artisan' => [
        'script' => env('TENANT_ARTISAN_SCRIPT', base_path('infrastructure/tenant-artisan.sh')),
        'use_sudo' => env('TENANT_ARTISAN_USE_SUDO', true),
    ],

    'tenant_deploy' => [
        'script' => env('TENANT_DEPLOY_SCRIPT', base_path('infrastructure/tenant-deploy.sh')),
        'use_sudo' => env('TENANT_DEPLOY_USE_SUDO', true),
    ],

    'tenant_env_preview' => [
        'script' => env('TENANT_ENV_KEYS_SCRIPT', base_path('infrastructure/tenant-env-keys.sh')),
        'use_sudo' => env('TENANT_ENV_KEYS_USE_SUDO', true),
    ],

    'tenant_mode' => [
        'enabled' => env('TENANT_MODE', false),
        'locked_tenant_id' => env('TENANT_LOCK_ID'),
    ],

    'panel' => [
        'allowed_ips' => env('PANEL_ALLOWED_IPS', ''),
    ],

    'platform' => [
        'rate_limit_per_minute' => env('PLATFORM_RATE_LIMIT', 120),
    ],

    'mail' => [
        'default_daily_limit' => env('TENANT_MAIL_DEFAULT_DAILY_LIMIT', 500),
        'default_per_minute_limit' => env('TENANT_MAIL_DEFAULT_PER_MINUTE_LIMIT', 30),
    ],

    'mailboxes' => [
        'script' => env('TENANT_MAILBOX_SCRIPT', base_path('infrastructure/manage-tenant-mailbox.sh')),
        'use_sudo' => env('TENANT_MAILBOX_USE_SUDO', true),
        'root' => env('TENANT_MAILBOX_ROOT', '/var/mail/tastypanel'),
        'users_file' => env('TENANT_MAILBOX_USERS_FILE', '/etc/dovecot/tastypanel-users'),
        'os_user' => env('TENANT_MAILBOX_OS_USER', 'vmail'),
        'os_group' => env('TENANT_MAILBOX_OS_GROUP', 'vmail'),
    ],

    'platform_service_manager' => [
        'script' => env('PLATFORM_SERVICE_MANAGER_SCRIPT', base_path('infrastructure/manage-platform-service.sh')),
        'use_sudo' => env('PLATFORM_SERVICE_MANAGER_USE_SUDO', true),
        'default_log_lines' => env('PLATFORM_SERVICE_LOG_LINES', 120),
        'services' => [
            'nginx' => [
                'label' => 'Nginx',
                'unit' => env('PLATFORM_SERVICE_NGINX', 'nginx'),
            ],
            'php_fpm' => [
                'label' => 'PHP-FPM',
                'unit' => env('PLATFORM_SERVICE_PHP_FPM', 'php8.3-fpm'),
            ],
            'mysql' => [
                'label' => 'MySQL',
                'unit' => env('PLATFORM_SERVICE_DB', 'mysql'),
            ],
            'redis' => [
                'label' => 'Redis',
                'unit' => env('PLATFORM_SERVICE_REDIS', 'redis-server'),
            ],
            'queue' => [
                'label' => 'Queue Worker',
                'unit' => env('PLATFORM_SERVICE_QUEUE', ''),
            ],
            'scheduler' => [
                'label' => 'Scheduler',
                'unit' => env('PLATFORM_SERVICE_SCHEDULER', ''),
            ],
        ],
    ],

    'provisioning' => [
        'auto_on_create' => env('AUTO_PROVISION_ON_TENANT_CREATE', false),
        'lock_ttl_seconds' => env('PROVISIONING_LOCK_TTL_SECONDS', 1800),
    ],

    'storage' => [
        'tenant_files_root' => env('TENANT_FILES_ROOT', storage_path('app/tenant-files')),
    ],

    'logs' => [
        'nginx_access_template' => env('NGINX_ACCESS_LOG_TEMPLATE', '/var/log/nginx/%s-access.log'),
        'nginx_error_template' => env('NGINX_ERROR_LOG_TEMPLATE', '/var/log/nginx/%s-error.log'),
        'php_fpm' => env('PHP_FPM_LOG', '/var/log/php8.3-fpm.log'),
    ],

    'security' => [
        'scan_script' => env('SECURITY_SCAN_SCRIPT', base_path('infrastructure/security-scan.sh')),
        'audit_script' => env('SECURITY_AUDIT_SCRIPT', base_path('infrastructure/security-audit.sh')),
        'use_sudo' => env('SECURITY_SCAN_USE_SUDO', true),
    ],

    'firewall' => [
        'script' => env('FIREWALL_SCRIPT', base_path('infrastructure/firewall-apply.sh')),
        'use_sudo' => env('FIREWALL_USE_SUDO', true),
    ],

];
