<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BackupRun;
use App\Models\Domain;
use App\Models\FirewallRule;
use App\Models\PlatformSetting;
use App\Models\SecurityBaseline;
use App\Models\SecurityScan;
use App\Models\SslCertificate;
use App\Models\Tenant;
use App\Models\UptimeCheck;
use App\Services\AlertService;
use App\Services\BackupService;
use App\Services\CloudflareService;
use App\Services\ContentSnapshotService;
use App\Services\FileIntegrityService;
use App\Services\FirewallService;
use App\Services\Http3HealthService;
use App\Services\InstanceProvisioningService;
use App\Services\LogReaderService;
use App\Services\NginxProvisioningService;
use App\Services\NginxSafeDeployService;
use App\Services\PlatformServiceManagerService;
use App\Services\ProvisioningService;
use App\Services\SearchService;
use App\Services\SecurityScanService;
use App\Services\SslHealthService;
use App\Services\SslProvisioningService;
use App\Services\TenantArtisanService;
use App\Services\TenantBackupService;
use App\Services\TenantCacheService;
use App\Services\TenantDeployService;
use App\Services\TenantEnvPreviewService;
use App\Services\TenantEnvSyncService;
use App\Services\TenantOrchestrationService;
use App\Services\TenantQueueService;
use App\Services\TenantSecretService;
use App\Services\UptimeMonitorService;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PlatformOpsController extends Controller
{
    private const ACTIONS = [
        // Platform
        'platform_backup_run' => [
            'label' => 'Platform backup (run now)',
            'description' => 'Create a platform backup (DB + storage archive). This can take time.',
            'category' => 'Platform',
            'confirm' => 'platform_backup_run',
            'requires' => [],
        ],
        'platform_log_laravel_tail' => [
            'label' => 'Platform: tail laravel.log',
            'description' => 'Tail the platform Laravel log (storage/logs/laravel.log).',
            'category' => 'Logs',
            'confirm' => null,
            'requires' => [],
        ],
        'platform_log_php_fpm_tail' => [
            'label' => 'Platform: tail PHP-FPM log',
            'description' => 'Tail the platform PHP-FPM log (from config/services.php).',
            'category' => 'Logs',
            'confirm' => null,
            'requires' => [],
        ],

        // Nginx
        'nginx_safe_deploy' => [
            'label' => 'Nginx safe deploy',
            'description' => 'Test config (nginx -t) then reload Nginx if valid.',
            'category' => 'Nginx',
            'confirm' => 'nginx_safe_deploy',
            'requires' => [],
        ],

        // Domains
        'domain_nginx_write' => [
            'label' => 'Domain: write Nginx config',
            'description' => 'Render and write the Nginx vhost config to storage (no apply).',
            'category' => 'Domains',
            'confirm' => null,
            'requires' => ['domain'],
        ],
        'domain_nginx_test' => [
            'label' => 'Domain: test Nginx config',
            'description' => 'Write config then run nginx config test (no reload).',
            'category' => 'Domains',
            'confirm' => 'domain_nginx_test',
            'requires' => ['domain'],
        ],
        'domain_nginx_apply' => [
            'label' => 'Domain: apply Nginx config',
            'description' => 'Write config then apply to sites-enabled and reload Nginx (safe rollback on failure).',
            'category' => 'Domains',
            'confirm' => 'domain_nginx_apply',
            'requires' => ['domain'],
        ],
        'domain_nginx_remove' => [
            'label' => 'Domain: remove Nginx config',
            'description' => 'Remove vhost from Nginx and reload. Use with caution.',
            'category' => 'Domains',
            'confirm' => 'domain_nginx_remove',
            'requires' => ['domain'],
        ],
        'domain_ssl_request' => [
            'label' => 'Domain: request SSL',
            'description' => 'Create/refresh SSL certificate request record (queued/pending).',
            'category' => 'Domains',
            'confirm' => null,
            'requires' => ['domain'],
        ],
        'domain_ssl_provision_force' => [
            'label' => 'Domain: provision SSL (force)',
            'description' => 'Run certbot now (Cloudflare DNS token + email required). Can take time.',
            'category' => 'Domains',
            'confirm' => 'domain_ssl_provision_force',
            'requires' => ['domain'],
        ],
        'domain_provision_full' => [
            'label' => 'Domain: full provisioning (DNS + SSL + Nginx + instance)',
            'description' => 'Run the full provisioning workflow for this domain. This can take time.',
            'category' => 'Domains',
            'confirm' => 'domain_provision_full',
            'requires' => ['domain'],
        ],
        'domain_provision_rollback' => [
            'label' => 'Domain: rollback provisioning',
            'description' => 'Rollback provisioning steps (DNS/Nginx/instance) where possible. Dangerous.',
            'category' => 'Domains',
            'confirm' => 'domain_provision_rollback',
            'requires' => ['domain'],
        ],
        'domain_cf_purge_cache_host' => [
            'label' => 'Domain: Cloudflare purge cache (host)',
            'description' => 'Purge Cloudflare cache for this hostname (host-level purge).',
            'category' => 'Cloudflare',
            'confirm' => null,
            'requires' => ['domain'],
        ],
        'domain_cf_purge_cache_zone' => [
            'label' => 'Zone: Cloudflare purge cache (everything)',
            'description' => 'Purge everything in the Cloudflare zone cache. Uses the selected domain zone id.',
            'category' => 'Cloudflare',
            'confirm' => 'domain_cf_purge_cache_zone',
            'requires' => ['domain'],
        ],
        'domain_cf_delete_dns' => [
            'label' => 'Domain: Cloudflare delete DNS record',
            'description' => 'Delete the stored Cloudflare DNS record for this domain (cf_record_id).',
            'category' => 'Cloudflare',
            'confirm' => 'domain_cf_delete_dns',
            'requires' => ['domain'],
        ],
        'domain_http3_check' => [
            'label' => 'Domain: HTTP/3 health check',
            'description' => 'Run HTTP/3 health checks and update status fields for this domain.',
            'category' => 'Domains',
            'confirm' => null,
            'requires' => ['domain'],
        ],
        'domain_http3_enable' => [
            'label' => 'Domain: enable HTTP/3',
            'description' => 'Enable HTTP/3 for this domain (regenerates Nginx config and runs a health check).',
            'category' => 'Domains',
            'confirm' => 'domain_http3_enable',
            'requires' => ['domain'],
        ],
        'domain_http3_disable' => [
            'label' => 'Domain: disable HTTP/3',
            'description' => 'Disable HTTP/3 for this domain (regenerates Nginx config and runs a health check).',
            'category' => 'Domains',
            'confirm' => 'domain_http3_disable',
            'requires' => ['domain'],
        ],
        'domain_log_access_tail' => [
            'label' => 'Domain: tail Nginx access log',
            'description' => 'Tail the Nginx access log for the selected domain.',
            'category' => 'Logs',
            'confirm' => null,
            'requires' => ['domain'],
        ],
        'domain_log_error_tail' => [
            'label' => 'Domain: tail Nginx error log',
            'description' => 'Tail the Nginx error log for the selected domain.',
            'category' => 'Logs',
            'confirm' => null,
            'requires' => ['domain'],
        ],

        // Services (systemd allowlist via PlatformServiceManagerService)
        'restart_nginx' => [
            'label' => 'Restart Nginx',
            'description' => 'systemctl restart nginx (via allowlisted service manager).',
            'category' => 'Services',
            'confirm' => 'restart_nginx',
            'requires' => [],
        ],
        'restart_php_fpm' => [
            'label' => 'Restart PHP-FPM',
            'description' => 'systemctl restart php-fpm (via allowlisted service manager).',
            'category' => 'Services',
            'confirm' => 'restart_php_fpm',
            'requires' => [],
        ],
        'restart_mysql' => [
            'label' => 'Restart MySQL',
            'description' => 'systemctl restart mysql (via allowlisted service manager).',
            'category' => 'Services',
            'confirm' => 'restart_mysql',
            'requires' => [],
        ],
        'restart_redis' => [
            'label' => 'Restart Redis',
            'description' => 'systemctl restart redis (via allowlisted service manager).',
            'category' => 'Services',
            'confirm' => 'restart_redis',
            'requires' => [],
        ],

        // Queue
        'queue_restart' => [
            'label' => 'Restart queue workers',
            'description' => 'php artisan queue:restart',
            'category' => 'Queue',
            'confirm' => 'queue_restart',
            'requires' => [],
        ],
        'queue_flush_failed' => [
            'label' => 'Flush failed jobs',
            'description' => 'php artisan queue:flush',
            'category' => 'Queue',
            'confirm' => 'queue_flush_failed',
            'requires' => [],
        ],

        // Tenants
        'tenant_backup' => [
            'label' => 'Tenant: backup (run now)',
            'description' => 'Run a backup for the selected tenant (DB + files). Can take time.',
            'category' => 'Tenants',
            'confirm' => 'tenant_backup',
            'requires' => ['tenant'],
        ],
        'tenant_cache_clear' => [
            'label' => 'Tenant: clear cache',
            'description' => 'Flush cache tags for selected tenant.',
            'category' => 'Tenants',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_cache_warmup' => [
            'label' => 'Tenant: warm up cache',
            'description' => 'Warm tenant cached data/stats.',
            'category' => 'Tenants',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_queue_restart' => [
            'label' => 'Tenant: restart queue',
            'description' => 'Restart queue worker(s) for selected tenant instance.',
            'category' => 'Tenants',
            'confirm' => 'tenant_queue_restart',
            'requires' => ['tenant'],
        ],
        'tenant_queue_flush_failed' => [
            'label' => 'Tenant: flush failed jobs',
            'description' => 'Flush failed jobs for selected tenant.',
            'category' => 'Tenants',
            'confirm' => 'tenant_queue_flush_failed',
            'requires' => ['tenant'],
        ],
        'tenant_queue_retry_failed' => [
            'label' => 'Tenant: retry failed jobs',
            'description' => 'Retry failed jobs for selected tenant.',
            'category' => 'Tenants',
            'confirm' => 'tenant_queue_retry_failed',
            'requires' => ['tenant'],
        ],
        'tenant_instance_provision' => [
            'label' => 'Tenant: provision instance',
            'description' => 'Provision tenant runtime (instance root, DB/user, FPM pool, optional frontend). Can take time.',
            'category' => 'Tenants',
            'confirm' => 'tenant_instance_provision',
            'requires' => ['tenant'],
        ],
        'tenant_instance_deprovision' => [
            'label' => 'Tenant: deprovision instance',
            'description' => 'Remove tenant runtime (files, DB/user, FPM pool, frontend, access). Dangerous.',
            'category' => 'Tenants',
            'confirm' => 'tenant_instance_deprovision',
            'requires' => ['tenant'],
        ],
        'tenant_staging_enable' => [
            'label' => 'Tenant: enable staging',
            'description' => 'Enable staging environment flags/settings for selected tenant.',
            'category' => 'Staging',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_staging_disable' => [
            'label' => 'Tenant: disable staging',
            'description' => 'Disable staging environment flags for selected tenant.',
            'category' => 'Staging',
            'confirm' => 'tenant_staging_disable',
            'requires' => ['tenant'],
        ],
        'tenant_staging_sync_prod_to_staging' => [
            'label' => 'Tenant: sync prod -> staging',
            'description' => 'Overwrite staging theme/settings from production. This can overwrite staging changes.',
            'category' => 'Staging',
            'confirm' => 'tenant_staging_sync_prod_to_staging',
            'requires' => ['tenant'],
        ],
        'tenant_staging_promote_to_prod' => [
            'label' => 'Tenant: promote staging -> prod',
            'description' => 'Apply staging theme/settings to production. Dangerous.',
            'category' => 'Staging',
            'confirm' => 'tenant_staging_promote_to_prod',
            'requires' => ['tenant'],
        ],
        'tenant_preview_enable' => [
            'label' => 'Tenant: enable preview',
            'description' => 'Enable preview environment flags/settings and sync production content to preview.',
            'category' => 'Preview',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_preview_disable' => [
            'label' => 'Tenant: disable preview',
            'description' => 'Disable preview environment flags for selected tenant.',
            'category' => 'Preview',
            'confirm' => 'tenant_preview_disable',
            'requires' => ['tenant'],
        ],
        'tenant_preview_sync_prod_to_preview' => [
            'label' => 'Tenant: sync prod -> preview',
            'description' => 'Overwrite preview theme/settings/content from production. This can overwrite preview changes.',
            'category' => 'Preview',
            'confirm' => 'tenant_preview_sync_prod_to_preview',
            'requires' => ['tenant'],
        ],
        'tenant_preview_promote_to_prod' => [
            'label' => 'Tenant: promote preview -> prod',
            'description' => 'Apply preview theme/settings/content to production. Dangerous.',
            'category' => 'Preview',
            'confirm' => 'tenant_preview_promote_to_prod',
            'requires' => ['tenant'],
        ],
        'tenant_env_preview_keys' => [
            'label' => 'Tenant: preview .env keys',
            'description' => 'List env keys present in the tenant .env file (keys only, no values).',
            'category' => 'Secrets',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_env_diff_secrets' => [
            'label' => 'Tenant: diff secrets -> .env (dry-run)',
            'description' => 'Show which derived env keys would be upserted for stored secrets (no values).',
            'category' => 'Secrets',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_secrets_sync_all_to_env' => [
            'label' => 'Tenant: sync all secrets to .env',
            'description' => 'Push all stored tenant secrets to the tenant .env file (derived env keys).',
            'category' => 'Secrets',
            'confirm' => 'tenant_secrets_sync_all_to_env',
            'requires' => ['tenant'],
        ],
        'tenant_deploy_full' => [
            'label' => 'Tenant: deploy (full)',
            'description' => 'Run a full deploy sequence: down -> git pull -> composer install -> migrate -> optimize:clear -> up.',
            'category' => 'Deploy',
            'confirm' => 'tenant_deploy_full',
            'requires' => ['tenant'],
        ],
        'tenant_deploy_git_pull' => [
            'label' => 'Tenant: deploy (git pull)',
            'description' => 'Run `git pull --ff-only` in the tenant instance.',
            'category' => 'Deploy',
            'confirm' => 'tenant_deploy_git_pull',
            'requires' => ['tenant'],
        ],
        'tenant_deploy_composer_install' => [
            'label' => 'Tenant: deploy (composer install)',
            'description' => 'Run `composer install --no-dev` in the tenant instance.',
            'category' => 'Deploy',
            'confirm' => 'tenant_deploy_composer_install',
            'requires' => ['tenant'],
        ],
        'tenant_orchestrate_restart' => [
            'label' => 'Tenant: restart services',
            'description' => 'Restart the tenant runtime (maintenance + php-fpm reload + optional frontend restart).',
            'category' => 'Runtime',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_orchestrate_stop' => [
            'label' => 'Tenant: stop (maintenance)',
            'description' => 'Put tenant in maintenance mode and stop optional frontend service.',
            'category' => 'Runtime',
            'confirm' => 'tenant_orchestrate_stop',
            'requires' => ['tenant'],
        ],
        'tenant_orchestrate_start' => [
            'label' => 'Tenant: start (exit maintenance)',
            'description' => 'Bring tenant out of maintenance mode and restart services.',
            'category' => 'Runtime',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_migrate' => [
            'label' => 'Tenant: run migrations',
            'description' => 'Run `php artisan migrate --force` inside the tenant instance.',
            'category' => 'Deploy',
            'confirm' => 'tenant_migrate',
            'requires' => ['tenant'],
        ],
        'tenant_optimize_clear' => [
            'label' => 'Tenant: clear caches',
            'description' => 'Run `php artisan optimize:clear` inside the tenant instance.',
            'category' => 'Deploy',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_config_cache' => [
            'label' => 'Tenant: config cache',
            'description' => 'Run `php artisan config:cache` inside the tenant instance.',
            'category' => 'Deploy',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_route_cache' => [
            'label' => 'Tenant: route cache',
            'description' => 'Run `php artisan route:cache` inside the tenant instance.',
            'category' => 'Deploy',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_view_cache' => [
            'label' => 'Tenant: view cache',
            'description' => 'Run `php artisan view:cache` inside the tenant instance.',
            'category' => 'Deploy',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_log_laravel_tail' => [
            'label' => 'Tenant: tail laravel.log',
            'description' => 'Tail the tenant Laravel log from the instance storage/logs directory.',
            'category' => 'Logs',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
        'tenant_log_php_fpm_tail' => [
            'label' => 'Tenant: tail php-fpm.log',
            'description' => 'Tail the tenant php-fpm log from the instance storage/logs directory.',
            'category' => 'Logs',
            'confirm' => null,
            'requires' => ['tenant'],
        ],

        // Caches
        'platform_migrate' => [
            'label' => 'Platform: run migrations',
            'description' => 'Run `php artisan migrate --force` for the platform DB.',
            'category' => 'Laravel',
            'confirm' => 'platform_migrate',
            'requires' => [],
        ],
        'optimize_clear' => [
            'label' => 'Clear app caches',
            'description' => 'php artisan optimize:clear (cache/config/routes/views).',
            'category' => 'Laravel',
            'confirm' => null,
            'requires' => [],
        ],

        // Security
        'security_scan_malware' => [
            'label' => 'Security scan (malware)',
            'description' => 'Run security scan script against platform path.',
            'category' => 'Security',
            'confirm' => 'security_scan_malware',
            'requires' => [],
        ],
        'security_scan_audit' => [
            'label' => 'Security audit',
            'description' => 'Run security audit script against platform path.',
            'category' => 'Security',
            'confirm' => 'security_scan_audit',
            'requires' => [],
        ],
        'integrity_baseline_create' => [
            'label' => 'Integrity: create baseline (platform)',
            'description' => 'Create a new file integrity baseline for the platform codebase.',
            'category' => 'Integrity',
            'confirm' => 'integrity_baseline_create',
            'requires' => [],
        ],
        'integrity_check_latest' => [
            'label' => 'Integrity: check latest baseline',
            'description' => 'Run integrity check against the latest baseline (alerts if changes are found).',
            'category' => 'Integrity',
            'confirm' => null,
            'requires' => [],
        ],
        'alerts_dispatch' => [
            'label' => 'Dispatch alerts (run now)',
            'description' => 'Send SSL/HTTP3/backup/storage alerts to email/Slack if configured.',
            'category' => 'Security',
            'confirm' => 'alerts_dispatch',
            'requires' => [],
        ],
        'ssl_renew_expiring' => [
            'label' => 'SSL: renew expiring certificates',
            'description' => 'Attempt to renew SSL certificates expiring soon (uses ssl_alert_days). Can take time.',
            'category' => 'Domains',
            'confirm' => 'ssl_renew_expiring',
            'requires' => [],
        ],
        'uptime_run' => [
            'label' => 'Run uptime checks',
            'description' => 'Run all active uptime checks now.',
            'category' => 'Monitoring',
            'confirm' => null,
            'requires' => [],
        ],

        // Firewall
        'firewall_apply' => [
            'label' => 'Apply firewall rules',
            'description' => 'Apply all active UFW rules via firewall script.',
            'category' => 'Firewall',
            'confirm' => 'firewall_apply',
            'requires' => [],
        ],
        'firewall_status' => [
            'label' => 'Firewall status',
            'description' => 'Show `ufw status` output.',
            'category' => 'Firewall',
            'confirm' => null,
            'requires' => [],
        ],
        // Search
        'search_status' => [
            'label' => 'Search: status',
            'description' => 'Show current search driver/enabled config.',
            'category' => 'Search',
            'confirm' => null,
            'requires' => [],
        ],
        'search_reindex_all' => [
            'label' => 'Search: reindex (all)',
            'description' => 'Reindex/search counts across all tenants (database driver only).',
            'category' => 'Search',
            'confirm' => null,
            'requires' => [],
        ],
        'search_reindex_tenant' => [
            'label' => 'Search: reindex (tenant)',
            'description' => 'Reindex/search counts for selected tenant (database driver only).',
            'category' => 'Search',
            'confirm' => null,
            'requires' => ['tenant'],
        ],
    ];

    public function index(PlatformServiceManagerService $serviceManager)
    {
        $guard = $this->guard();
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $services = $serviceManager->list();
        $actions = self::ACTIONS;

        $actionList = [];
        foreach ($actions as $id => $meta) {
            $actionList[] = [
                'id' => (string) $id,
                'label' => (string) ($meta['label'] ?? $id),
                'description' => (string) ($meta['description'] ?? ''),
                'category' => (string) ($meta['category'] ?? 'Other'),
                'confirm' => $meta['confirm'] ?? null,
                'requires' => array_values($meta['requires'] ?? []),
            ];
        }
        usort($actionList, function ($a, $b) {
            return [$a['category'], $a['label']] <=> [$b['category'], $b['label']];
        });

        $bulkActionIds = [
            'tenant_optimize_clear',
            'tenant_migrate',
            'tenant_config_cache',
            'tenant_route_cache',
            'tenant_view_cache',
            'tenant_queue_restart',
            'tenant_queue_flush_failed',
            'tenant_cache_clear',
            'tenant_cache_warmup',
            'tenant_secrets_sync_all_to_env',
            'tenant_staging_sync_prod_to_staging',
            'tenant_preview_sync_prod_to_preview',
        ];
        $bulkActionList = array_values(array_filter($actionList, function ($item) use ($bulkActionIds) {
            return in_array($item['id'] ?? '', $bulkActionIds, true);
        }));

        $tenants = Tenant::query()
            ->select(['id', 'name', 'slug', 'status', 'instance_status', 'staging_enabled', 'preview_enabled'])
            ->orderBy('name')
            ->get();

        $domains = Domain::query()
            ->with('tenant:id,name')
            ->orderBy('hostname')
            ->get(['id', 'tenant_id', 'hostname', 'environment', 'status', 'nginx_status', 'is_primary']);

        $firewallRules = FirewallRule::query()->orderByDesc('id')->limit(50)->get();
        $securityScans = SecurityScan::query()->with('creator:id,name')->orderByDesc('id')->limit(20)->get();
        $recentAudit = AuditLog::query()->with('user:id,name')->orderByDesc('created_at')->limit(10)->get();
        $sslDays = 14;
        $monitoring = [
            'uptime_active' => UptimeCheck::where('is_active', true)->count(),
            'ssl_expiring_soon' => SslCertificate::where('status', 'issued')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays($sslDays))
                ->count(),
            'backup_failures_24h' => BackupRun::where('status', 'failed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'http3_issues' => Domain::where('http3_enabled', true)
                ->whereNotIn('http3_status', ['ok', 'advertised'])
                ->count(),
            'ssl_days' => $sslDays,
        ];

        return view('platform.control', [
            'actions' => $actions,
            'actionList' => $actionList,
            'bulkActionList' => $bulkActionList,
            'tenants' => $tenants,
            'domains' => $domains,
            'firewallRules' => $firewallRules,
            'securityScans' => $securityScans,
            'recentAudit' => $recentAudit,
            'monitoring' => $monitoring,
            'services' => $services,
            'lastAction' => session('runbook_action'),
            'lastActionId' => session('runbook_action_id'),
            'lastOutput' => session('runbook_output'),
            'lastSuccess' => session('runbook_success'),
            'lastTenantId' => session('runbook_tenant_id'),
            'lastDomainId' => session('runbook_domain_id'),
        ]);
    }

    public function run(Request $request)
    {
        $guard = $this->guard();
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $data = $request->validate([
            'action_id' => ['required', 'string'],
            'confirm' => ['nullable', 'string'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'domain_id' => ['nullable', 'integer', 'exists:domains,id'],
        ]);

        $actionId = $data['action_id'];
        if (! array_key_exists($actionId, self::ACTIONS)) {
            abort(404);
        }

        $meta = self::ACTIONS[$actionId];
        $requires = array_values($meta['requires'] ?? []);

        $tenant = null;
        if (! empty($data['tenant_id'])) {
            $tenant = Tenant::query()->find((int) $data['tenant_id']);
        }

        $domain = null;
        if (! empty($data['domain_id'])) {
            $domain = Domain::query()
                ->with(['tenant', 'sslCertificate'])
                ->find((int) $data['domain_id']);
        }

        if ($domain && ! $tenant) {
            $tenant = $domain->tenant;
        }

        if (in_array('tenant', $requires, true) && ! $tenant) {
            return back()->with('error', 'This action requires a tenant.');
        }
        if (in_array('domain', $requires, true) && ! $domain) {
            return back()->with('error', 'This action requires a domain.');
        }
        if ($tenant && $domain && (int) $domain->tenant_id !== (int) $tenant->id) {
            return back()->with('error', 'Selected domain does not belong to selected tenant.');
        }

        $requiredConfirm = $meta['confirm'] ?? null;
        if ($requiredConfirm !== null) {
            $confirm = trim((string) ($data['confirm'] ?? ''));
            if ($confirm !== $requiredConfirm) {
                return back()->with('error', "Confirmation text did not match. Type '{$requiredConfirm}' to run this action.");
            }
        }

        $result = $this->executeAction($actionId, $tenant, $domain);
        if (array_key_exists('output', $result)) {
            $result['output'] = $this->redactOutput((string) ($result['output'] ?? ''));
        }

        $this->logRunbook($meta['label'] ?? $actionId, $actionId, $tenant?->id, $domain?->id, $result);

        $output = $this->tail((string) ($result['output'] ?? ''), 6000);
        $message = $result['message'] ?? ($result['success'] ? 'Action completed.' : 'Action failed.');

        return back()
            ->with($result['success'] ? 'success' : 'error', $message)
            ->with('runbook_action', $meta['label'] ?? $actionId)
            ->with('runbook_action_id', $actionId)
            ->with('runbook_output', $output !== '' ? $output : null)
            ->with('runbook_success', (bool) $result['success'])
            ->with('runbook_tenant_id', $tenant?->id)
            ->with('runbook_domain_id', $domain?->id);
    }

    public function bulkRun(Request $request)
    {
        $guard = $this->guard();
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $data = $request->validate([
            'action_id' => ['required', 'string'],
            'confirm' => ['nullable', 'string'],
            'tenant_ids' => ['required', 'array', 'min:1', 'max:50'],
            'tenant_ids.*' => ['integer', 'exists:tenants,id'],
        ]);

        $actionId = (string) $data['action_id'];
        if (! array_key_exists($actionId, self::ACTIONS)) {
            abort(404);
        }

        $meta = self::ACTIONS[$actionId];
        $requires = array_values($meta['requires'] ?? []);
        if (! in_array('tenant', $requires, true)) {
            return back()->with('error', 'This bulk action must target tenants.');
        }

        $requiredConfirm = $meta['confirm'] ?? null;
        if ($requiredConfirm !== null) {
            $confirm = trim((string) ($data['confirm'] ?? ''));
            if ($confirm !== $requiredConfirm) {
                return back()->with('error', "Confirmation text did not match. Type '{$requiredConfirm}' to run this action.");
            }
        }

        $tenantIds = array_values(array_unique(array_map('intval', $data['tenant_ids'] ?? [])));
        $tenants = Tenant::query()
            ->whereIn('id', $tenantIds)
            ->get(['id', 'name', 'instance_root', 'instance_system_user', 'instance_status'])
            ->keyBy('id');

        $results = [];
        $ok = true;

        foreach ($tenantIds as $tenantId) {
            $tenant = $tenants->get($tenantId);
            if (! $tenant) {
                $ok = false;
                $results[] = [
                    'tenant_id' => $tenantId,
                    'success' => false,
                    'message' => 'Tenant not found.',
                    'output_tail' => null,
                ];

                continue;
            }

            $result = $this->executeAction($actionId, $tenant, null);
            if (array_key_exists('output', $result)) {
                $result['output'] = $this->redactOutput((string) ($result['output'] ?? ''));
            }

            $this->logRunbook(($meta['label'] ?? $actionId).' (bulk)', $actionId, (int) $tenant->id, null, $result);

            $success = (bool) ($result['success'] ?? false);
            $ok = $ok && $success;
            $results[] = [
                'tenant_id' => (int) $tenant->id,
                'tenant_name' => (string) ($tenant->name ?? ''),
                'success' => $success,
                'message' => (string) ($result['message'] ?? ''),
                'output_tail' => $this->tail((string) ($result['output'] ?? ''), 900),
            ];
        }

        $successCount = count(array_filter($results, fn ($r) => (bool) ($r['success'] ?? false)));
        $payload = [
            'action_id' => $actionId,
            'label' => (string) ($meta['label'] ?? $actionId),
            'tenants' => count($results),
            'success' => $successCount,
            'failed' => count($results) - $successCount,
            'results' => $results,
        ];

        return back()
            ->with($ok ? 'success' : 'error', $ok ? 'Bulk action completed.' : 'Bulk action completed with failures.')
            ->with('runbook_action', ($meta['label'] ?? $actionId).' (bulk)')
            ->with('runbook_action_id', $actionId)
            ->with('runbook_output', $this->tail((string) (json_encode($payload, JSON_PRETTY_PRINT) ?: ''), 6000))
            ->with('runbook_success', $ok)
            ->with('runbook_tenant_id', null)
            ->with('runbook_domain_id', null);
    }

    private function executeAction(
        string $actionId,
        ?Tenant $tenant,
        ?Domain $domain
    ): array {
        try {
            return match ($actionId) {
                'platform_backup_run' => $this->actionPlatformBackup(app(BackupService::class)),
                'platform_log_laravel_tail' => $this->actionTailLog(app(LogReaderService::class), storage_path('logs/laravel.log')),
                'platform_log_php_fpm_tail' => $this->actionTailLog(app(LogReaderService::class), (string) config('services.logs.php_fpm')),
                'nginx_safe_deploy' => $this->actionNginxSafeDeploy(app(NginxSafeDeployService::class)),

                'domain_nginx_write' => $this->actionDomainNginxWrite(app(NginxProvisioningService::class), $domain),
                'domain_nginx_test' => $this->actionDomainNginxTest(app(NginxProvisioningService::class), $domain),
                'domain_nginx_apply' => $this->actionDomainNginxApply(app(NginxProvisioningService::class), $domain),
                'domain_nginx_remove' => $this->actionDomainNginxRemove(app(NginxProvisioningService::class), $domain),
                'domain_ssl_request' => $this->actionDomainSslRequest(app(SslProvisioningService::class), $domain),
                'domain_ssl_provision_force' => $this->actionDomainSslProvision(app(SslProvisioningService::class), $domain),
                'domain_provision_full' => $this->actionDomainProvisionFull(app(ProvisioningService::class), $domain),
                'domain_provision_rollback' => $this->actionDomainProvisionRollback(app(ProvisioningService::class), $domain),
                'domain_cf_purge_cache_host' => $this->actionDomainCloudflarePurgeHost(app(CloudflareService::class), $domain),
                'domain_cf_purge_cache_zone' => $this->actionDomainCloudflarePurgeZone(app(CloudflareService::class), $domain),
                'domain_cf_delete_dns' => $this->actionDomainCloudflareDeleteDns(app(CloudflareService::class), $domain),
                'domain_http3_check' => $this->actionDomainHttp3Check(app(Http3HealthService::class), $domain),
                'domain_http3_enable' => $this->actionDomainHttp3Toggle(app(NginxProvisioningService::class), app(Http3HealthService::class), $domain, true),
                'domain_http3_disable' => $this->actionDomainHttp3Toggle(app(NginxProvisioningService::class), app(Http3HealthService::class), $domain, false),
                'domain_log_access_tail' => $this->actionDomainLogTail(app(LogReaderService::class), $domain, 'access'),
                'domain_log_error_tail' => $this->actionDomainLogTail(app(LogReaderService::class), $domain, 'error'),

                'restart_nginx' => app(PlatformServiceManagerService::class)->action('nginx', 'restart'),
                'restart_php_fpm' => app(PlatformServiceManagerService::class)->action('php_fpm', 'restart'),
                'restart_mysql' => app(PlatformServiceManagerService::class)->action('mysql', 'restart'),
                'restart_redis' => app(PlatformServiceManagerService::class)->action('redis', 'restart'),

                'queue_restart' => $this->actionArtisan('queue:restart'),
                'queue_flush_failed' => $this->actionArtisan('queue:flush'),

                'tenant_backup' => $this->actionTenantBackup(app(TenantBackupService::class), $tenant),
                'tenant_cache_clear' => $this->actionTenantCacheClear(app(TenantCacheService::class), $tenant),
                'tenant_cache_warmup' => $this->actionTenantCacheWarmup(app(TenantCacheService::class), $tenant),
                'tenant_queue_restart' => $this->actionTenantQueue(app(TenantQueueService::class), $tenant, 'restart'),
                'tenant_queue_flush_failed' => $this->actionTenantQueue(app(TenantQueueService::class), $tenant, 'flush'),
                'tenant_queue_retry_failed' => $this->actionTenantQueue(app(TenantQueueService::class), $tenant, 'retry'),
                'tenant_instance_provision' => $this->actionTenantProvision(app(InstanceProvisioningService::class), $tenant, $domain),
                'tenant_instance_deprovision' => $this->actionTenantDeprovision(app(InstanceProvisioningService::class), $tenant),
                'tenant_staging_enable' => $this->actionTenantStagingEnable($tenant),
                'tenant_staging_disable' => $this->actionTenantStagingDisable($tenant),
                'tenant_staging_sync_prod_to_staging' => $this->actionTenantStagingSyncProdToStaging($tenant),
                'tenant_staging_promote_to_prod' => $this->actionTenantStagingPromoteToProd($tenant),
                'tenant_preview_enable' => $this->actionTenantPreviewEnable(app(ContentSnapshotService::class), $tenant),
                'tenant_preview_disable' => $this->actionTenantPreviewDisable($tenant),
                'tenant_preview_sync_prod_to_preview' => $this->actionTenantPreviewSyncProdToPreview(app(ContentSnapshotService::class), $tenant),
                'tenant_preview_promote_to_prod' => $this->actionTenantPreviewPromoteToProd(app(ContentSnapshotService::class), $tenant),
                'tenant_env_preview_keys' => $this->actionTenantEnvPreviewKeys(app(TenantEnvPreviewService::class), $tenant),
                'tenant_env_diff_secrets' => $this->actionTenantEnvDiffSecrets(
                    app(TenantSecretService::class),
                    app(TenantEnvSyncService::class),
                    app(TenantEnvPreviewService::class),
                    $tenant
                ),
                'tenant_secrets_sync_all_to_env' => $this->actionTenantSecretsSyncAllToEnv(
                    app(TenantSecretService::class),
                    app(TenantEnvSyncService::class),
                    $tenant
                ),
                'tenant_deploy_full' => $this->actionTenantDeploy(app(TenantDeployService::class), $tenant, 'full'),
                'tenant_deploy_git_pull' => $this->actionTenantDeploy(app(TenantDeployService::class), $tenant, 'git_pull'),
                'tenant_deploy_composer_install' => $this->actionTenantDeploy(app(TenantDeployService::class), $tenant, 'composer_install'),
                'tenant_orchestrate_restart' => $this->actionTenantOrchestrate(app(TenantOrchestrationService::class), $tenant, 'restart'),
                'tenant_orchestrate_stop' => $this->actionTenantOrchestrate(app(TenantOrchestrationService::class), $tenant, 'stop'),
                'tenant_orchestrate_start' => $this->actionTenantOrchestrate(app(TenantOrchestrationService::class), $tenant, 'start'),
                'tenant_migrate' => $this->actionTenantArtisan(app(TenantArtisanService::class), $tenant, 'migrate'),
                'tenant_optimize_clear' => $this->actionTenantArtisan(app(TenantArtisanService::class), $tenant, 'optimize_clear'),
                'tenant_config_cache' => $this->actionTenantArtisan(app(TenantArtisanService::class), $tenant, 'config_cache'),
                'tenant_route_cache' => $this->actionTenantArtisan(app(TenantArtisanService::class), $tenant, 'route_cache'),
                'tenant_view_cache' => $this->actionTenantArtisan(app(TenantArtisanService::class), $tenant, 'view_cache'),
                'tenant_log_laravel_tail' => $this->actionTenantLaravelLogTail(app(LogReaderService::class), $tenant),
                'tenant_log_php_fpm_tail' => $this->actionTailLog(app(LogReaderService::class), $this->resolveTenantPhpFpmLog($tenant)),

                'platform_migrate' => $this->actionArtisan('migrate', ['--force' => true]),
                'optimize_clear' => $this->actionArtisan('optimize:clear'),

                'security_scan_malware' => $this->actionSecurityScan(app(SecurityScanService::class), 'malware'),
                'security_scan_audit' => $this->actionSecurityScan(app(SecurityScanService::class), 'audit'),
                'integrity_baseline_create' => $this->actionIntegrityBaselineCreate(app(FileIntegrityService::class)),
                'integrity_check_latest' => $this->actionIntegrityCheckLatest(app(FileIntegrityService::class)),
                'alerts_dispatch' => $this->actionAlertsDispatch(app(AlertService::class)),
                'ssl_renew_expiring' => $this->actionSslRenewExpiring(app(SslHealthService::class), app(SslProvisioningService::class)),
                'uptime_run' => $this->actionUptimeRun(app(UptimeMonitorService::class)),

                'firewall_apply' => $this->actionFirewallApply(app(FirewallService::class)),
                'firewall_status' => [
                    'success' => true,
                    'output' => app(FirewallService::class)->status(),
                    'message' => 'Firewall status fetched.',
                ],
                'search_status' => [
                    'success' => true,
                    'output' => json_encode(app(SearchService::class)->status(), JSON_PRETTY_PRINT) ?: '',
                    'message' => 'Search status fetched.',
                ],
                'search_reindex_all' => [
                    'success' => true,
                    'output' => json_encode(app(SearchService::class)->reindex(null, 'production'), JSON_PRETTY_PRINT) ?: '',
                    'message' => 'Search reindex completed (all).',
                ],
                'search_reindex_tenant' => [
                    'success' => true,
                    'output' => json_encode(app(SearchService::class)->reindex($tenant?->id, 'production'), JSON_PRETTY_PRINT) ?: '',
                    'message' => 'Search reindex completed (tenant).',
                ],

                default => [
                    'success' => false,
                    'output' => 'Unknown action.',
                    'message' => 'Unknown action.',
                ],
            };
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'output' => $e->getMessage(),
                'message' => 'Action failed: '.$e->getMessage(),
            ];
        }
    }

    private function actionPlatformBackup(BackupService $backupService): array
    {
        $run = $backupService->run(Auth::id());
        $success = $run->status === 'completed';

        $output = trim((string) ($run->output ?? ''));
        if ($run->path) {
            $output .= ($output !== '' ? "\n\n" : '').'PATH='.$run->path;
        }
        if ($run->checksum) {
            $output .= ($output !== '' ? "\n" : '').'SHA256='.$run->checksum;
        }

        return [
            'success' => $success,
            'output' => $output,
            'message' => $success ? 'Platform backup completed.' : 'Platform backup failed.',
        ];
    }

    private function actionNginxSafeDeploy(NginxSafeDeployService $nginxDeployer): array
    {
        $result = $nginxDeployer->deploy();
        $success = (bool) ($result['success'] ?? false);
        $message = (string) ($result['message'] ?? ($success ? 'Nginx deployed.' : 'Nginx deploy failed.'));

        return [
            'success' => $success,
            'output' => $message,
            'message' => $success ? 'Nginx safe deploy succeeded.' : 'Nginx safe deploy failed.',
        ];
    }

    private function actionTailLog(LogReaderService $logs, string $path): array
    {
        $path = trim($path);
        if ($path === '') {
            return ['success' => false, 'output' => 'Log path is not configured.', 'message' => 'Log path is not configured.'];
        }

        $tail = $logs->tail($path, 200);
        $out = "PATH={$path}\n\n".($tail !== '' ? $tail : '[empty or missing]');

        return [
            'success' => true,
            'output' => $out,
            'message' => 'Log tail fetched.',
        ];
    }

    private function actionDomainLogTail(LogReaderService $logs, ?Domain $domain, string $type): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $template = $type === 'error'
            ? (string) config('services.logs.nginx_error_template')
            : (string) config('services.logs.nginx_access_template');

        $path = str_contains($template, '%s') ? sprintf($template, $domain->hostname) : $template;

        return $this->actionTailLog($logs, $path);
    }

    private function actionTenantLaravelLogTail(LogReaderService $logs, ?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $path = $this->resolveTenantLaravelLog($tenant);

        return $this->actionTailLog($logs, $path);
    }

    private function resolveTenantLaravelLog(Tenant $tenant): string
    {
        if (! $tenant->instance_root) {
            return '';
        }
        $logDir = rtrim($tenant->instance_root, '/').'/storage/logs';
        $default = $logDir.'/laravel.log';
        if (file_exists($default)) {
            return $default;
        }

        if (! is_dir($logDir)) {
            return $default;
        }

        $files = glob($logDir.'/laravel*.log') ?: [];
        if ($files === []) {
            return $default;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return (string) ($files[0] ?? $default);
    }

    private function resolveTenantPhpFpmLog(?Tenant $tenant): string
    {
        if (! $tenant || ! $tenant->instance_root) {
            return '';
        }

        return rtrim($tenant->instance_root, '/').'/storage/logs/php-fpm.log';
    }

    private function actionTenantBackup(TenantBackupService $tenantBackups, ?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $run = $tenantBackups->run($tenant, Auth::id(), 'manual');
        $success = $run->status === 'completed';

        $output = trim((string) ($run->output ?? ''));
        if ($run->path) {
            $output .= ($output !== '' ? "\n\n" : '').'PATH='.$run->path;
        }
        if ($run->checksum) {
            $output .= ($output !== '' ? "\n" : '').'SHA256='.$run->checksum;
        }

        return [
            'success' => $success,
            'output' => $output,
            'message' => $success ? 'Tenant backup completed.' : 'Tenant backup failed.',
        ];
    }

    private function actionTenantCacheClear(TenantCacheService $tenantCache, ?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $tenantCache->clearTenantCache($tenant);
        $tenantCache->clearStatsCache($tenant);

        return [
            'success' => true,
            'output' => "Cleared cache for tenant #{$tenant->id} ({$tenant->name}).",
            'message' => 'Tenant cache cleared.',
        ];
    }

    private function actionTenantCacheWarmup(TenantCacheService $tenantCache, ?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $tenantCache->warmupTenantCache($tenant);

        return [
            'success' => true,
            'output' => "Warmed cache for tenant #{$tenant->id} ({$tenant->name}).",
            'message' => 'Tenant cache warmed.',
        ];
    }

    private function actionTenantQueue(TenantQueueService $tenantQueue, ?Tenant $tenant, string $mode): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $result = match ($mode) {
            'restart' => $tenantQueue->restart($tenant),
            'flush' => $tenantQueue->flushFailed($tenant),
            'retry' => $tenantQueue->retryFailed($tenant),
            default => ['success' => false, 'output' => 'Unknown tenant queue action.'],
        };

        return [
            'success' => (bool) ($result['success'] ?? false),
            'output' => (string) ($result['output'] ?? ''),
            'message' => (bool) ($result['success'] ?? false) ? 'Tenant queue action completed.' : 'Tenant queue action failed.',
        ];
    }

    private function actionTenantProvision(InstanceProvisioningService $instances, ?Tenant $tenant, ?Domain $domain): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $result = $instances->provisionTenantWithResult($tenant, $domain);
        $success = (bool) ($result['success'] ?? false);
        $output = (string) ($result['output'] ?? '');

        return [
            'success' => $success,
            'output' => $output,
            'message' => $success ? 'Tenant provisioning completed.' : 'Tenant provisioning failed.',
        ];
    }

    private function actionTenantDeprovision(InstanceProvisioningService $instances, ?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $result = $instances->deprovisionTenant($tenant, true);
        $success = (bool) ($result['success'] ?? false);

        return [
            'success' => $success,
            'output' => json_encode($result, JSON_PRETTY_PRINT) ?: '',
            'message' => $success ? 'Tenant deprovision completed.' : 'Tenant deprovision failed.',
        ];
    }

    private function actionTenantStagingEnable(?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $tenant->load(['settings', 'stagingSettings']);
        if ($tenant->staging_enabled) {
            return [
                'success' => true,
                'output' => "Staging already enabled for tenant #{$tenant->id} ({$tenant->name}).",
                'message' => 'Staging already enabled.',
            ];
        }

        $tenant->staging_enabled = true;
        if (! $tenant->staging_theme_id) {
            $tenant->staging_theme_id = $tenant->theme_id;
        }
        $tenant->save();

        $productionSettings = $tenant->settings?->data ?? [];
        $tenant->stagingSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'staging'],
            ['data' => $productionSettings]
        );

        return [
            'success' => true,
            'output' => "Enabled staging for tenant #{$tenant->id} ({$tenant->name}).",
            'message' => 'Staging enabled.',
        ];
    }

    private function actionTenantStagingDisable(?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        if (! $tenant->staging_enabled) {
            return [
                'success' => true,
                'output' => "Staging already disabled for tenant #{$tenant->id} ({$tenant->name}).",
                'message' => 'Staging already disabled.',
            ];
        }

        $tenant->staging_enabled = false;
        $tenant->save();

        return [
            'success' => true,
            'output' => "Disabled staging for tenant #{$tenant->id} ({$tenant->name}).",
            'message' => 'Staging disabled.',
        ];
    }

    private function actionTenantStagingSyncProdToStaging(?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        if (! $tenant->staging_enabled) {
            return [
                'success' => false,
                'output' => 'Staging is disabled. Enable staging first.',
                'message' => 'Staging is disabled.',
            ];
        }

        $tenant->load(['settings', 'stagingSettings']);
        $tenant->staging_theme_id = $tenant->theme_id;
        $productionSettings = $tenant->settings?->data ?? [];
        $tenant->stagingSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'staging'],
            ['data' => $productionSettings]
        );
        $tenant->save();

        return [
            'success' => true,
            'output' => "Synced production -> staging for tenant #{$tenant->id} ({$tenant->name}).",
            'message' => 'Production synced to staging.',
        ];
    }

    private function actionTenantStagingPromoteToProd(?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        if (! $tenant->staging_enabled) {
            return [
                'success' => false,
                'output' => 'Staging is disabled. Enable staging first.',
                'message' => 'Staging is disabled.',
            ];
        }

        $tenant->load(['settings', 'stagingSettings']);
        if (! $tenant->staging_theme_id) {
            return [
                'success' => false,
                'output' => 'Staging theme is not set. Sync prod -> staging first.',
                'message' => 'Staging theme not set.',
            ];
        }

        $tenant->theme_id = $tenant->staging_theme_id;
        $stagingSettings = $tenant->stagingSettings?->data ?? [];
        $tenant->settings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'production'],
            ['data' => $stagingSettings]
        );
        $tenant->save();

        return [
            'success' => true,
            'output' => "Promoted staging -> production for tenant #{$tenant->id} ({$tenant->name}).",
            'message' => 'Staging promoted to production.',
        ];
    }

    private function actionTenantPreviewEnable(ContentSnapshotService $snapshots, ?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $tenant->load(['settings', 'previewSettings']);
        if ($tenant->preview_enabled) {
            return [
                'success' => true,
                'output' => "Preview already enabled for tenant #{$tenant->id} ({$tenant->name}).",
                'message' => 'Preview already enabled.',
            ];
        }

        $tenant->preview_enabled = true;
        if (! $tenant->preview_theme_id) {
            $tenant->preview_theme_id = $tenant->theme_id;
        }
        $tenant->save();

        $productionSettings = $tenant->settings?->data ?? [];
        $tenant->previewSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'preview'],
            ['data' => $productionSettings]
        );

        $syncOk = true;
        $syncError = null;
        try {
            $snapshots->syncEnvironment($tenant->id, 'production', 'preview');
        } catch (\Throwable $e) {
            $syncOk = false;
            $syncError = $e->getMessage();
        }

        $payload = [
            'tenant_id' => $tenant->id,
            'preview_enabled' => (bool) $tenant->preview_enabled,
            'preview_theme_id' => $tenant->preview_theme_id,
            'content_sync' => [
                'success' => $syncOk,
                'error' => $syncError,
            ],
        ];

        return [
            'success' => $syncOk,
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => $syncOk ? 'Preview enabled.' : 'Preview enabled but content sync failed.',
        ];
    }

    private function actionTenantPreviewDisable(?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        if (! $tenant->preview_enabled) {
            return [
                'success' => true,
                'output' => "Preview already disabled for tenant #{$tenant->id} ({$tenant->name}).",
                'message' => 'Preview already disabled.',
            ];
        }

        $tenant->preview_enabled = false;
        $tenant->save();

        return [
            'success' => true,
            'output' => "Disabled preview for tenant #{$tenant->id} ({$tenant->name}).",
            'message' => 'Preview disabled.',
        ];
    }

    private function actionTenantPreviewSyncProdToPreview(ContentSnapshotService $snapshots, ?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $tenant->load(['settings', 'previewSettings']);
        $tenant->preview_theme_id = $tenant->theme_id;
        $tenant->preview_enabled = true;
        $tenant->save();

        $productionSettings = $tenant->settings?->data ?? [];
        $tenant->previewSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'preview'],
            ['data' => $productionSettings]
        );

        $syncOk = true;
        $syncError = null;
        try {
            $snapshots->syncEnvironment($tenant->id, 'production', 'preview');
        } catch (\Throwable $e) {
            $syncOk = false;
            $syncError = $e->getMessage();
        }

        $payload = [
            'tenant_id' => $tenant->id,
            'preview_enabled' => (bool) $tenant->preview_enabled,
            'preview_theme_id' => $tenant->preview_theme_id,
            'content_sync' => [
                'success' => $syncOk,
                'error' => $syncError,
            ],
        ];

        return [
            'success' => $syncOk,
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => $syncOk ? 'Production synced to preview.' : 'Sync to preview failed.',
        ];
    }

    private function actionTenantPreviewPromoteToProd(ContentSnapshotService $snapshots, ?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $tenant->load(['settings', 'previewSettings']);
        if (! $tenant->preview_theme_id) {
            return [
                'success' => false,
                'output' => 'Preview theme is not set. Sync prod -> preview first.',
                'message' => 'Preview theme not set.',
            ];
        }

        $tenant->theme_id = $tenant->preview_theme_id;

        $previewSettings = $tenant->previewSettings?->data ?? [];
        $tenant->settings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'production'],
            ['data' => $previewSettings]
        );
        $tenant->save();

        $syncOk = true;
        $syncError = null;
        try {
            $snapshots->syncEnvironment($tenant->id, 'preview', 'production');
        } catch (\Throwable $e) {
            $syncOk = false;
            $syncError = $e->getMessage();
        }

        $payload = [
            'tenant_id' => $tenant->id,
            'theme_id' => $tenant->theme_id,
            'content_sync' => [
                'success' => $syncOk,
                'error' => $syncError,
            ],
        ];

        return [
            'success' => $syncOk,
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => $syncOk ? 'Preview promoted to production.' : 'Promotion failed (content sync).',
        ];
    }

    private function actionTenantEnvPreviewKeys(TenantEnvPreviewService $envPreview, ?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $result = $envPreview->listKeys($tenant);
        $success = (bool) ($result['success'] ?? false);

        $payload = [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'env_file' => $result['env_file'] ?? null,
            'status' => $result['status'] ?? null,
            'keys_count' => is_array($result['keys'] ?? null) ? count($result['keys']) : null,
            'keys' => $result['keys'] ?? [],
        ];

        return [
            'success' => $success,
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => $success ? 'Env keys fetched.' : 'Env preview failed.',
        ];
    }

    private function actionTenantEnvDiffSecrets(
        TenantSecretService $secrets,
        TenantEnvSyncService $envSync,
        TenantEnvPreviewService $envPreview,
        ?Tenant $tenant
    ): array {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $env = $envPreview->listKeys($tenant);
        $envKeys = array_values(array_filter($env['keys'] ?? [], fn ($k) => is_string($k) && $k !== ''));
        $envKeysSet = array_fill_keys($envKeys, true);

        $list = $secrets->listMetadata($tenant)->values();
        $derived = [];
        foreach ($list as $secret) {
            $secretKey = (string) ($secret->secret_key ?? '');
            if ($secretKey === '') {
                continue;
            }
            $derived[] = $envSync->deriveEnvKey($secretKey);
        }
        $derived = array_values(array_unique(array_filter($derived)));

        $missing = [];
        $present = [];
        foreach ($derived as $k) {
            if (isset($envKeysSet[$k])) {
                $present[] = $k;
            } else {
                $missing[] = $k;
            }
        }

        $payload = [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'env_file' => $env['env_file'] ?? null,
            'env_status' => $env['status'] ?? null,
            'env_keys_count' => count($envKeys),
            'secrets_count' => $list->count(),
            'derived_env_keys_count' => count($derived),
            'would_upsert' => [
                'present' => count($present),
                'missing' => count($missing),
            ],
            'missing_env_keys' => array_slice($missing, 0, 120),
            'present_env_keys' => array_slice($present, 0, 120),
        ];

        return [
            'success' => (bool) ($env['success'] ?? true),
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => 'Dry-run diff generated.',
        ];
    }

    private function actionTenantSecretsSyncAllToEnv(TenantSecretService $secrets, TenantEnvSyncService $envSync, ?Tenant $tenant): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $list = $secrets->listMetadata($tenant)->values();
        $total = $list->count();
        if ($total === 0) {
            return [
                'success' => true,
                'output' => "No secrets found for tenant #{$tenant->id} ({$tenant->name}).",
                'message' => 'No secrets to sync.',
            ];
        }

        $max = 120;
        $rows = [];
        $ok = true;
        $synced = 0;
        $failed = 0;
        $truncated = false;

        foreach ($list as $idx => $secret) {
            if ($idx >= $max) {
                $truncated = true;
                break;
            }

            $secretKey = (string) ($secret->secret_key ?? '');
            if ($secretKey === '') {
                continue;
            }

            $secretValue = $secrets->getSecretValue($tenant, $secretKey);
            if ($secretValue === null) {
                $ok = false;
                $failed++;
                $rows[] = [
                    'secret_key' => $secretKey,
                    'env_key' => null,
                    'success' => false,
                    'exit_code' => null,
                    'error' => 'Secret value could not be decrypted.',
                ];

                continue;
            }

            $envKey = $envSync->deriveEnvKey($secretKey);
            $result = $envSync->upsert($tenant, $envKey, $secretValue);

            $success = (bool) ($result['success'] ?? false);
            $ok = $ok && $success;
            if ($success) {
                $synced++;
            } else {
                $failed++;
            }

            $rows[] = [
                'secret_key' => $secretKey,
                'env_key' => $envKey,
                'success' => $success,
                'exit_code' => $result['exit_code'] ?? null,
                'error' => $success ? null : $this->tail((string) ($result['output'] ?? ''), 600),
            ];
        }

        $payload = [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'total' => $total,
            'processed' => count($rows),
            'synced' => $synced,
            'failed' => $failed,
            'truncated' => $truncated,
            'results' => $rows,
        ];

        return [
            'success' => $ok,
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => $ok ? 'Secrets synced to tenant .env.' : 'Some secrets failed to sync.',
        ];
    }

    private function actionTenantDeploy(TenantDeployService $deploy, ?Tenant $tenant, string $mode): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $result = $deploy->run($tenant, $mode);
        $success = (bool) ($result['success'] ?? false);
        $output = (string) ($result['output'] ?? '');

        return [
            'success' => $success,
            'output' => $output,
            'message' => $success ? 'Deploy completed.' : 'Deploy failed.',
        ];
    }

    private function actionTenantOrchestrate(TenantOrchestrationService $orchestration, ?Tenant $tenant, string $action): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $result = $orchestration->runAction($tenant, $action);
        $success = (bool) ($result['success'] ?? false);

        return [
            'success' => $success,
            'output' => (string) ($result['output'] ?? ($result['message'] ?? '')),
            'message' => $success ? 'Tenant runtime action completed.' : 'Tenant runtime action failed.',
        ];
    }

    private function actionTenantArtisan(TenantArtisanService $artisan, ?Tenant $tenant, string $action): array
    {
        if (! $tenant) {
            return ['success' => false, 'output' => 'Tenant missing.', 'message' => 'Tenant missing.'];
        }

        $result = $artisan->run($tenant, $action);
        $success = (bool) ($result['success'] ?? false);
        $output = (string) ($result['output'] ?? '');

        return [
            'success' => $success,
            'output' => $output,
            'message' => $success ? 'Tenant command completed.' : 'Tenant command failed.',
        ];
    }

    private function actionDomainNginxWrite(NginxProvisioningService $nginxProvisioning, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $path = $nginxProvisioning->writeConfig($domain);

        return [
            'success' => true,
            'output' => "Wrote config: {$path}",
            'message' => 'Nginx config written.',
        ];
    }

    private function actionDomainNginxTest(NginxProvisioningService $nginxProvisioning, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $config = $nginxProvisioning->renderConfig($domain);
        $result = $nginxProvisioning->testConfig($domain, $config);

        return [
            'success' => (bool) ($result['success'] ?? false),
            'output' => (string) ($result['output'] ?? ''),
            'message' => (bool) ($result['success'] ?? false) ? 'Nginx config test passed.' : 'Nginx config test failed.',
        ];
    }

    private function actionDomainNginxApply(NginxProvisioningService $nginxProvisioning, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $path = $nginxProvisioning->writeConfig($domain);
        $result = $nginxProvisioning->applyConfig($domain, $path);

        $out = trim((string) ($result['output'] ?? ''));
        $out = "CONFIG={$path}\n".$out;

        return [
            'success' => (bool) ($result['success'] ?? false),
            'output' => $out,
            'message' => (bool) ($result['success'] ?? false) ? 'Nginx applied.' : 'Nginx apply failed.',
        ];
    }

    private function actionDomainNginxRemove(NginxProvisioningService $nginxProvisioning, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $result = $nginxProvisioning->deprovisionDomain($domain);

        return [
            'success' => (bool) ($result['success'] ?? false),
            'output' => (string) ($result['output'] ?? ''),
            'message' => (bool) ($result['success'] ?? false) ? 'Nginx config removed.' : 'Nginx remove failed.',
        ];
    }

    private function actionDomainSslRequest(SslProvisioningService $sslProvisioning, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $cert = $sslProvisioning->requestCertificate($domain);

        return [
            'success' => true,
            'output' => "SSL request status: {$cert->status}",
            'message' => 'SSL request recorded.',
        ];
    }

    private function actionDomainSslProvision(SslProvisioningService $sslProvisioning, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $cert = $sslProvisioning->provisionCertificate($domain, true);
        $success = $cert->status === 'issued';
        $output = "STATUS={$cert->status}\n";
        if ($cert->last_error) {
            $output .= "ERROR={$cert->last_error}\n";
        }
        if (is_array($cert->meta) && ($cert->meta['cert_path'] ?? null)) {
            $output .= "CERT={$cert->meta['cert_path']}\n";
        }

        return [
            'success' => $success,
            'output' => trim($output),
            'message' => $success ? 'SSL issued.' : 'SSL provisioning failed.',
        ];
    }

    private function actionDomainProvisionFull(ProvisioningService $provisioning, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $lines = [];
        $progress = function (string $message, array $meta = []) use (&$lines) {
            $stamp = now()->format('H:i:s');
            $suffix = $meta !== [] ? (' '.(json_encode($meta, JSON_UNESCAPED_SLASHES) ?: '')) : '';
            $lines[] = "[{$stamp}] {$message}{$suffix}";

            // Keep progress logs bounded.
            if (count($lines) > 160) {
                $lines = array_slice($lines, -160);
            }
        };

        $state = $provisioning->provisionDomainWithState($domain, null, $progress);

        $fresh = Domain::query()
            ->with(['sslCertificate', 'tenant'])
            ->find($domain->id);

        $summary = [
            'workflow' => [
                'success' => (bool) ($state['success'] ?? false),
                'blocked' => (bool) ($state['blocked'] ?? false),
                'idempotent' => (bool) ($state['idempotent'] ?? false),
                'lock_contended' => (bool) ($state['lock_contended'] ?? false),
                'completed_steps' => $state['completed_steps'] ?? [],
                'failed_step' => $state['failed_step'] ?? null,
                'errors' => $state['errors'] ?? [],
            ],
            'steps' => $state['steps'] ?? [],
            'rollback' => $state['rollback'] ?? null,
            'domain' => [
                'id' => $fresh?->id,
                'hostname' => $fresh?->hostname,
                'status' => $fresh?->status,
                'nginx_status' => $fresh?->nginx_status,
                'ssl_status' => $fresh?->sslCertificate?->status,
                'cf_record_id' => $fresh?->cf_record_id ? 'set' : null,
                'last_error' => $fresh?->last_error,
            ],
            'tenant' => [
                'id' => $fresh?->tenant?->id,
                'name' => $fresh?->tenant?->name,
                'instance_status' => $fresh?->tenant?->instance_status,
            ],
        ];

        $output = ($lines !== [] ? implode("\n", $lines)."\n\n" : '')
            .(json_encode($summary, JSON_PRETTY_PRINT) ?: '');

        $success = (bool) ($state['success'] ?? false);
        $message = $success ? 'Domain provisioning completed.' : 'Domain provisioning failed.';
        if ($success && (bool) ($state['idempotent'] ?? false)) {
            $message = 'Provisioning already satisfied (skipped).';
        } elseif ($success && (bool) ($state['lock_contended'] ?? false)) {
            $message = 'Provisioning already running (skipped).';
        } elseif ($success && (bool) ($state['blocked'] ?? false)) {
            $message = 'Provisioning partially blocked (check output).';
        }

        return [
            'success' => $success,
            'output' => $output,
            'message' => $message,
        ];
    }

    private function actionDomainProvisionRollback(ProvisioningService $provisioning, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $domain = $domain->fresh(['tenant', 'sslCertificate']) ?? $domain;
        $result = $provisioning->rollbackDomain($domain);

        return [
            'success' => (bool) ($result['success'] ?? false),
            'output' => json_encode($result, JSON_PRETTY_PRINT) ?: '',
            'message' => (bool) ($result['success'] ?? false) ? 'Rollback completed.' : 'Rollback had failures.',
        ];
    }

    private function actionDomainCloudflarePurgeHost(CloudflareService $cloudflare, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $zoneId = $domain->cf_zone_id ?: (string) config('services.cloudflare.zone_id');
        if (! $zoneId) {
            return ['success' => false, 'output' => 'Cloudflare zone id is missing.', 'message' => 'Cloudflare zone id is missing.'];
        }

        $result = $cloudflare->purgeCache($zoneId, [$domain->hostname]);

        return [
            'success' => true,
            'output' => json_encode(['zone_id' => $zoneId, 'host' => $domain->hostname, 'result' => $result], JSON_PRETTY_PRINT) ?: '',
            'message' => 'Cloudflare cache purged (host).',
        ];
    }

    private function actionDomainCloudflarePurgeZone(CloudflareService $cloudflare, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $zoneId = $domain->cf_zone_id ?: (string) config('services.cloudflare.zone_id');
        if (! $zoneId) {
            return ['success' => false, 'output' => 'Cloudflare zone id is missing.', 'message' => 'Cloudflare zone id is missing.'];
        }

        $result = $cloudflare->purgeCache($zoneId);

        return [
            'success' => true,
            'output' => json_encode(['zone_id' => $zoneId, 'result' => $result], JSON_PRETTY_PRINT) ?: '',
            'message' => 'Cloudflare cache purged (zone).',
        ];
    }

    private function actionDomainCloudflareDeleteDns(CloudflareService $cloudflare, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $zoneId = $domain->cf_zone_id ?: (string) config('services.cloudflare.zone_id');
        if (! $zoneId) {
            return ['success' => false, 'output' => 'Cloudflare zone id is missing.', 'message' => 'Cloudflare zone id is missing.'];
        }

        if (! $domain->cf_record_id) {
            return [
                'success' => true,
                'output' => "No cf_record_id stored for {$domain->hostname}. Nothing to delete.",
                'message' => 'DNS record already absent.',
            ];
        }

        $cloudflare->deleteDnsRecord($zoneId, (string) $domain->cf_record_id);
        $domain->cf_record_id = null;
        $domain->save();

        return [
            'success' => true,
            'output' => "Deleted DNS record for {$domain->hostname} (zone={$zoneId}).",
            'message' => 'DNS record deleted.',
        ];
    }

    private function actionDomainHttp3Check(Http3HealthService $http3, ?Domain $domain): array
    {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $domain = $domain->fresh(['sslCertificate']) ?? $domain;
        $checked = $http3->check($domain);

        $status = (string) ($checked->http3_status ?? 'unknown');
        $success = $status !== 'error';

        $payload = [
            'hostname' => $checked->hostname,
            'http3_enabled' => (bool) $checked->http3_enabled,
            'http3_status' => $checked->http3_status,
            'http3_error' => $checked->http3_error,
            'checked_at' => optional($checked->http3_checked_at)->toIso8601String(),
            'udp_status' => $checked->http3_udp_status,
            'udp_error' => $checked->http3_udp_error,
            'udp_checked_at' => optional($checked->http3_udp_checked_at)->toIso8601String(),
        ];

        return [
            'success' => $success,
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => 'HTTP/3 check completed.',
        ];
    }

    private function actionDomainHttp3Toggle(
        NginxProvisioningService $nginx,
        Http3HealthService $http3,
        ?Domain $domain,
        bool $enabled
    ): array {
        if (! $domain) {
            return ['success' => false, 'output' => 'Domain missing.', 'message' => 'Domain missing.'];
        }

        $domain = $domain->fresh(['sslCertificate']) ?? $domain;
        if (! empty($domain->nginx_custom_config)) {
            return [
                'success' => false,
                'output' => 'HTTP/3 toggle is disabled when using a custom Nginx config.',
                'message' => 'HTTP/3 toggle blocked (custom Nginx).',
            ];
        }

        $domain->http3_enabled = $enabled;
        $domain->save();

        // Re-render Nginx config so QUIC listeners are applied/removed.
        $domain = $nginx->provisionDomain($domain, true);
        $domain = $http3->check($domain);

        $payload = [
            'hostname' => $domain->hostname,
            'http3_enabled' => (bool) $domain->http3_enabled,
            'http3_status' => $domain->http3_status,
            'http3_error' => $domain->http3_error,
            'checked_at' => optional($domain->http3_checked_at)->toIso8601String(),
            'udp_status' => $domain->http3_udp_status,
            'udp_error' => $domain->http3_udp_error,
            'udp_checked_at' => optional($domain->http3_udp_checked_at)->toIso8601String(),
            'nginx_status' => $domain->nginx_status,
            'nginx_error' => $domain->nginx_error,
        ];

        $success = (string) ($domain->http3_status ?? '') !== 'error';
        $message = $enabled ? 'HTTP/3 enabled.' : 'HTTP/3 disabled.';

        return [
            'success' => $success,
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => $message,
        ];
    }

    private function actionSslRenewExpiring(SslHealthService $sslHealth, SslProvisioningService $ssl): array
    {
        $settings = PlatformSetting::getData();
        $sslDays = (int) ($settings['ssl_alert_days'] ?? 14);
        $limit = 10;

        $certs = $sslHealth->expiringSoon($sslDays);
        $targets = $certs->take($limit);

        $results = [];
        foreach ($targets as $cert) {
            $domain = $cert->domain;
            if (! $domain) {
                $results[] = [
                    'domain' => null,
                    'success' => false,
                    'status' => null,
                    'error' => 'Domain missing for certificate.',
                ];

                continue;
            }

            $renewed = $ssl->provisionCertificate($domain, true);
            // Update expiry if cert was issued and meta paths exist.
            try {
                $sslHealth->updateExpiry($renewed);
            } catch (\Throwable $e) {
            }

            $results[] = [
                'domain' => $domain->hostname,
                'success' => ($renewed->status ?? null) === 'issued',
                'status' => $renewed->status ?? null,
                'expires_at' => optional($renewed->expires_at)->toDateTimeString(),
                'error' => $renewed->last_error,
            ];
        }

        $payload = [
            'ssl_alert_days' => $sslDays,
            'found' => $certs->count(),
            'processed' => count($results),
            'limit' => $limit,
            'results' => $results,
        ];

        $ok = count(array_filter($results, fn ($r) => (bool) ($r['success'] ?? false))) === count($results);

        return [
            'success' => $ok,
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => $certs->count() ? 'SSL renew attempted for expiring certificates.' : 'No expiring certificates found.',
        ];
    }

    private function actionArtisan(string $command, array $parameters = []): array
    {
        $exitCode = Artisan::call($command, $parameters);
        $output = Artisan::output();
        $success = $exitCode === 0;

        $display = $command;
        if ($parameters !== []) {
            $display .= ' '.(json_encode($parameters, JSON_UNESCAPED_SLASHES) ?: '');
        }

        return [
            'success' => $success,
            'output' => $output,
            'message' => $success ? "Ran: php artisan {$display}" : "Failed: php artisan {$display}",
        ];
    }

    private function actionIntegrityBaselineCreate(FileIntegrityService $integrity): array
    {
        $name = 'platform-'.now()->format('Ymd-His');
        $baseline = $integrity->createBaseline($name, base_path(), [], Auth::id());

        $payload = [
            'baseline' => [
                'id' => $baseline->id,
                'name' => $baseline->name,
                'root_path' => $baseline->root_path,
                'paths' => $baseline->paths,
                'file_count' => is_array($baseline->hashes) ? count($baseline->hashes) : 0,
                'created_at' => optional($baseline->created_at)->toIso8601String(),
            ],
        ];

        return [
            'success' => true,
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => 'Integrity baseline created.',
        ];
    }

    private function actionIntegrityCheckLatest(FileIntegrityService $integrity): array
    {
        $baseline = SecurityBaseline::query()->orderByDesc('id')->first();
        $createdBaseline = false;

        if (! $baseline) {
            $createdBaseline = true;
            $name = 'platform-'.now()->format('Ymd-His');
            $baseline = $integrity->createBaseline($name, base_path(), [], Auth::id());
        }

        $check = $integrity->check($baseline, Auth::id());
        $ok = $check->status === 'completed';
        $message = $ok ? 'Integrity check completed.' : 'Integrity check failed.';

        $counts = null;
        $parsed = json_decode((string) ($check->output ?? ''), true);
        if (is_array($parsed) && is_array($parsed['counts'] ?? null)) {
            $counts = $parsed['counts'];
            $changed = (int) ($counts['changed'] ?? 0);
            $missing = (int) ($counts['missing'] ?? 0);
            $new = (int) ($counts['new'] ?? 0);

            if ($ok && ($changed + $missing + $new) > 0) {
                $ok = false;
                $message = "Integrity changes detected: changed={$changed} missing={$missing} new={$new}";
            }
        }

        $payload = [
            'baseline' => [
                'id' => $baseline->id,
                'name' => $baseline->name,
                'created_at' => optional($baseline->created_at)->toIso8601String(),
            ],
            'check' => [
                'id' => $check->id,
                'status' => $check->status,
                'created_at' => optional($check->created_at)->toIso8601String(),
                'started_at' => optional($check->started_at)->toIso8601String(),
                'finished_at' => optional($check->finished_at)->toIso8601String(),
                'counts' => $counts,
                'output' => $check->output,
            ],
            'created_baseline' => $createdBaseline,
        ];

        return [
            'success' => $ok,
            'output' => json_encode($payload, JSON_PRETTY_PRINT) ?: '',
            'message' => $message,
        ];
    }

    private function actionSecurityScan(SecurityScanService $securityScanService, string $type): array
    {
        $scan = $securityScanService->run(base_path(), Auth::id(), $type);

        return [
            'success' => $scan->status === 'completed',
            'output' => (string) ($scan->output ?? ''),
            'message' => $scan->status === 'completed'
                ? "Security {$type} completed."
                : "Security {$type} failed.",
        ];
    }

    private function actionAlertsDispatch(AlertService $alertService): array
    {
        $result = $alertService->dispatch();
        $sent = (bool) ($result['sent'] ?? false);
        $skipped = (bool) ($result['skipped'] ?? false);

        $message = $sent ? 'Alerts dispatched.' : ($skipped ? 'Alerts skipped.' : 'Alerts not sent.');

        return [
            'success' => $sent || $skipped,
            'output' => json_encode($result, JSON_PRETTY_PRINT) ?: '',
            'message' => $message,
        ];
    }

    private function actionUptimeRun(UptimeMonitorService $uptime): array
    {
        $result = $uptime->run();

        return [
            'success' => true,
            'output' => json_encode($result, JSON_PRETTY_PRINT) ?: '',
            'message' => 'Uptime run completed.',
        ];
    }

    private function actionFirewallApply(FirewallService $firewallService): array
    {
        $results = $firewallService->applyAll();
        $lines = [];
        $ok = true;

        foreach ($results as $idx => $item) {
            $success = (bool) ($item['success'] ?? false);
            $ok = $ok && $success;
            $prefix = $success ? 'OK' : 'FAIL';
            $output = trim((string) ($item['output'] ?? ''));
            $lines[] = "#{$idx} {$prefix}".($output !== '' ? "\n{$output}\n" : '');
        }

        return [
            'success' => $ok,
            'output' => implode("\n", $lines),
            'message' => $ok ? 'Firewall rules applied.' : 'Firewall apply had failures.',
        ];
    }

    private function logRunbook(string $label, string $actionId, ?int $tenantId, ?int $domainId, array $result): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'tenant_id' => $tenantId,
                'action' => 'runbook',
                'resource_type' => 'runbook_action',
                'resource_id' => null,
                'description' => "{$label} ({$actionId})",
                'old_values' => null,
                'new_values' => [
                    'tenant_id' => $tenantId,
                    'domain_id' => $domainId,
                    'success' => (bool) ($result['success'] ?? false),
                    'message' => (string) ($result['message'] ?? ''),
                    'output_tail' => $this->tail((string) ($result['output'] ?? ''), 2000),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'status' => (bool) ($result['success'] ?? false) ? 'success' : 'failed',
                'error_message' => (bool) ($result['success'] ?? false) ? null : (string) ($result['message'] ?? 'failed'),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never break the control panel on audit failures.
        }
    }

    private function guard(): RedirectResponse|bool
    {
        if (! PlatformInstallController::isInstalled()) {
            return redirect()->route('platform.install');
        }

        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        return true;
    }

    private function redactOutput(string $text): string
    {
        $text = (string) $text;
        if ($text === '') {
            return $text;
        }

        // PEM blocks / private keys.
        $text = preg_replace(
            '/-----BEGIN [A-Z0-9 ]+-----.*?-----END [A-Z0-9 ]+-----/s',
            '[REDACTED_KEY]',
            $text
        ) ?? $text;

        // JSON payloads: "token": "..."
        $text = preg_replace(
            '/(?i)\"(token|access_token|refresh_token|api_key|apikey|client_secret|password|secret)\"\\s*:\\s*\"[^\"]*\"/',
            '"$1":"[REDACTED]"',
            $text
        ) ?? $text;

        // Common header/env patterns on their own line.
        $text = preg_replace(
            '/(?im)^(\\s*(?:authorization|x-api-key|x-auth-key|api[_-]?key|access[_-]?token|refresh[_-]?token|client[_-]?secret|secret|password)\\s*[:=]\\s*)(.+)$/',
            '$1[REDACTED]',
            $text
        ) ?? $text;

        // Inline patterns: token=..., secret: ...
        $text = preg_replace(
            '/(?i)(token|access[_-]?token|refresh[_-]?token|client[_-]?secret|api[_-]?key|password|secret)\\s*[:=]\\s*([^\\s]+)/',
            '$1=[REDACTED]',
            $text
        ) ?? $text;

        // Authorization: Bearer <token>
        $text = preg_replace(
            '/(?i)(authorization\\s*:\\s*bearer\\s+)[^\\s]+/',
            '$1[REDACTED]',
            $text
        ) ?? $text;

        return $text;
    }

    private function tail(string $text, int $maxBytes): string
    {
        if ($maxBytes <= 0) {
            return '';
        }
        if (strlen($text) <= $maxBytes) {
            return $text;
        }

        return substr($text, -$maxBytes);
    }

    // --- Firewall rule management (web UI) ---

    public function firewallStore(Request $request)
    {
        $guard = $this->guard();
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(['allow', 'deny'])],
            'protocol' => ['required', 'string', Rule::in(['tcp', 'udp'])],
            'port' => ['required', 'string', 'max:32'],
            'source' => ['nullable', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule = FirewallRule::create([
            'action' => strtolower($data['action']),
            'protocol' => strtolower($data['protocol']),
            'port' => $data['port'],
            'source' => $data['source'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'created_by' => Auth::id(),
        ]);

        $this->logControlEvent('firewall_rule_create', $rule, null, $rule->getAttributes(), "Created firewall rule #{$rule->id}");

        return back()->with('success', 'Firewall rule created.');
    }

    public function firewallToggle(Request $request, FirewallRule $rule)
    {
        $guard = $this->guard();
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $old = $rule->getAttributes();
        $rule->is_active = (bool) $data['is_active'];
        $rule->save();

        $this->logControlEvent('firewall_rule_update', $rule, $old, $rule->getAttributes(), "Updated firewall rule #{$rule->id}");

        return back()->with('success', 'Firewall rule updated.');
    }

    public function firewallDestroy(Request $request, FirewallRule $rule)
    {
        $guard = $this->guard();
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $old = $rule->getAttributes();
        $ruleId = (int) $rule->id;
        $rule->delete();

        $this->logControlEvent('firewall_rule_delete', null, $old, null, "Deleted firewall rule #{$ruleId}");

        return back()->with('success', 'Firewall rule deleted.');
    }

    private function logControlEvent(string $action, $resource, ?array $oldValues, ?array $newValues, string $description): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'tenant_id' => null,
                'action' => $action,
                'resource_type' => $resource ? get_class($resource) : null,
                'resource_id' => $resource?->id,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'status' => 'success',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Ignore audit failures.
        }
    }
}
