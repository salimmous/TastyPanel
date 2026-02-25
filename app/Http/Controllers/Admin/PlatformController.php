<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditExport;
use App\Models\AuditLog;
use App\Models\BackupRun;
use App\Models\DisasterRecoveryDrill;
use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Services\AuditExportService;
use App\Services\BackupRestoreService;
use App\Services\BackupService;
use App\Services\DisasterRecoveryDrillService;
use App\Services\NginxSafeDeployService;
use App\Services\PlatformServiceManagerService;
use App\Services\PlatformStatusService;
use App\Services\SslHealthService;
use App\Services\TenantLimitService;
use App\Services\TenantStorageService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(AdminPermissions::isSuperadmin($request->user()), 403);

            return $next($request);
        });
    }

    public function overview(PlatformStatusService $statusService)
    {
        $queue = $this->queueStats();
        $backups = BackupRun::orderByDesc('id')->limit(5)->get();

        return response()->json([
            'status' => $statusService->summary(),
            'queue' => $queue,
            'backups' => $backups,
            'settings' => $this->settingsPayload(),
        ]);
    }

    public function settings()
    {
        return response()->json([
            'data' => $this->settingsPayload(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'panel_allowed_ips' => ['nullable', 'string'],
            'force_2fa' => ['nullable', 'boolean'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:0'],
            'backup_retention_days' => ['nullable', 'integer', 'min:0'],
            'backup_s3_enabled' => ['nullable', 'boolean'],
            'backup_keep_local' => ['nullable', 'boolean'],
            'backup_s3_prefix' => ['nullable', 'string'],
            'ssl_alert_days' => ['nullable', 'integer', 'min:1'],
            'http3_check_interval_minutes' => ['nullable', 'integer', 'min:1'],
            'ssl_check_interval_hours' => ['nullable', 'integer', 'min:1'],
            'backup_interval_hours' => ['nullable', 'integer', 'min:1'],
            'analytics_interval_hours' => ['nullable', 'integer', 'min:1'],
            'uptime_check_interval_minutes' => ['nullable', 'integer', 'min:1'],
            'integrity_check_interval_hours' => ['nullable', 'integer', 'min:1'],
            'cron_enabled' => ['nullable', 'boolean'],
            'cron_timezone' => ['nullable', 'timezone', 'max:120'],
            'audit_export_retention_days' => ['nullable', 'integer', 'min:0'],
            'search_enabled' => ['nullable', 'boolean'],
            'search_driver' => ['nullable', 'string'],
            'search_endpoint' => ['nullable', 'string'],
            'search_api_key' => ['nullable', 'string'],
            'search_index_prefix' => ['nullable', 'string'],
            'brand_name' => ['nullable', 'string'],
            'brand_logo_url' => ['nullable', 'string'],
            'brand_favicon_url' => ['nullable', 'string'],
            'brand_primary_color' => ['nullable', 'string'],
            'brand_secondary_color' => ['nullable', 'string'],
            'brand_accent_color' => ['nullable', 'string'],
            'brand_login_message' => ['nullable', 'string'],
            'alerts_emails' => ['nullable', 'string'],
            'alerts_slack_webhook' => ['nullable', 'string'],
            'alerts_interval_hours' => ['nullable', 'integer', 'min:1'],
            'alerts_send_empty' => ['nullable', 'boolean'],
            'sso_enabled' => ['nullable', 'boolean'],
            'sso_provider_label' => ['nullable', 'string'],
            'sso_client_id' => ['nullable', 'string'],
            'sso_client_secret' => ['nullable', 'string'],
            'sso_auth_url' => ['nullable', 'string'],
            'sso_token_url' => ['nullable', 'string'],
            'sso_userinfo_url' => ['nullable', 'string'],
            'sso_redirect_url' => ['nullable', 'string'],
            'sso_scopes' => ['nullable', 'string'],
            'sso_allowed_domains' => ['nullable', 'string'],
            'sso_auto_create' => ['nullable', 'boolean'],
            'sso_enforce' => ['nullable', 'boolean'],
            'sso_default_role' => ['nullable', 'string'],
            'sso_default_tenant_id' => ['nullable', 'integer'],
            'saml_enabled' => ['nullable', 'boolean'],
            'saml_provider_label' => ['nullable', 'string'],
            'saml_idp_metadata_url' => ['nullable', 'string'],
            'saml_idp_metadata_xml' => ['nullable', 'string'],
            'saml_idp_entity_id' => ['nullable', 'string'],
            'saml_idp_sso_url' => ['nullable', 'string'],
            'saml_idp_slo_url' => ['nullable', 'string'],
            'saml_idp_x509' => ['nullable', 'string'],
            'saml_sp_entity_id' => ['nullable', 'string'],
            'saml_acs_url' => ['nullable', 'string'],
            'saml_slo_url' => ['nullable', 'string'],
            'saml_nameid_format' => ['nullable', 'string'],
            'saml_attribute_email' => ['nullable', 'string'],
            'saml_attribute_name' => ['nullable', 'string'],
            'saml_attribute_groups' => ['nullable', 'string'],
            'saml_allowed_domains' => ['nullable', 'string'],
            'saml_auto_create' => ['nullable', 'boolean'],
            'saml_enforce' => ['nullable', 'boolean'],
            'saml_default_role' => ['nullable', 'string'],
            'saml_default_tenant_id' => ['nullable', 'integer'],
        ]);

        $current = PlatformSetting::getData();
        $next = array_merge($current, $data);
        PlatformSetting::updateData($next);

        return response()->json([
            'data' => $this->settingsPayload(),
        ]);
    }

    public function queue()
    {
        return response()->json([
            'data' => $this->queueStats(),
        ]);
    }

    public function queueRestart()
    {
        $output = [];
        $exit = 0;
        exec('php artisan queue:restart 2>&1', $output, $exit);

        return response()->json([
            'success' => $exit === 0,
            'output' => implode("\n", $output),
        ]);
    }

    public function queueFlushFailed()
    {
        $output = [];
        $exit = 0;
        exec('php artisan queue:flush 2>&1', $output, $exit);

        return response()->json([
            'success' => $exit === 0,
            'output' => implode("\n", $output),
        ]);
    }

    public function services(PlatformServiceManagerService $manager)
    {
        return response()->json([
            'data' => $manager->list(),
        ]);
    }

    public function serviceAction(Request $request, string $service, PlatformServiceManagerService $manager)
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:start,stop,restart'],
        ]);

        $result = $manager->action($service, $data['action']);

        return response()->json([
            'success' => $result['success'],
            'output' => $result['output'],
            'status' => $result['status'] ?? null,
        ], $result['success'] ? 200 : 422);
    }

    public function serviceLogs(Request $request, string $service, PlatformServiceManagerService $manager)
    {
        $data = $request->validate([
            'lines' => ['nullable', 'integer', 'min:10', 'max:500'],
        ]);

        $result = $manager->logs($service, (int) ($data['lines'] ?? config('services.platform_service_manager.default_log_lines', 120)));

        return response()->json([
            'success' => $result['success'],
            'output' => $result['output'],
            'lines' => $result['lines'],
        ], $result['success'] ? 200 : 422);
    }

    public function deployNginxSafe(Request $request, NginxSafeDeployService $deployService)
    {
        $data = $request->validate([
            'mode' => ['nullable', 'string', 'in:deploy,rollback'],
            'backup_path' => ['nullable', 'string'],
        ]);

        $mode = $data['mode'] ?? 'deploy';
        $result = $mode === 'rollback'
            ? $deployService->rollback($data['backup_path'] ?? null)
            : $deployService->deploy();

        return response()->json([
            'success' => $result['success'],
            'output' => $result['output'],
            'mode' => $mode,
        ], $result['success'] ? 200 : 422);
    }

    public function backups()
    {
        $runs = BackupRun::orderByDesc('id')->paginate(20);

        return response()->json($runs);
    }

    public function createBackup(Request $request, BackupService $backupService)
    {
        $run = $backupService->run($request->user()?->id);

        return response()->json([
            'data' => $run,
        ]);
    }

    public function downloadBackup(BackupRun $backup)
    {
        if (! $backup->path) {
            abort(404);
        }

        $zipPath = $backup->path.'/backup.zip';
        if (! file_exists($zipPath)) {
            abort(404);
        }

        return new StreamedResponse(function () use ($zipPath) {
            readfile($zipPath);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Length' => filesize($zipPath),
            'Content-Disposition' => 'attachment; filename="backup.zip"',
        ]);
    }

    public function restoreBackup(Request $request, BackupRun $backup, BackupRestoreService $restoreService)
    {
        $data = $request->validate([
            'confirm' => ['required', 'string'],
        ]);

        if (strtoupper(trim($data['confirm'])) !== 'RESTORE') {
            return response()->json(['message' => 'Confirmation required. Type RESTORE to proceed.'], 422);
        }

        $restore = $restoreService->restore($backup, $request->user()?->id);

        return response()->json([
            'data' => $restore,
        ]);
    }

    public function auditLogs(Request $request)
    {
        $query = AuditLog::with(['user:id,name,email', 'tenant:id,name']);
        if ($request->has('search')) {
            $term = $request->search;
            $query->where('route', 'like', "%{$term}%")
                ->orWhere('action', 'like', "%{$term}%");
        }

        $logs = $query->orderByDesc('id')->paginate(25);

        return response()->json($logs);
    }

    public function auditExports()
    {
        $exports = AuditExport::orderByDesc('id')->paginate(20);

        return response()->json($exports);
    }

    public function createAuditExport(Request $request, AuditExportService $exports)
    {
        $data = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $export = $exports->export($data['days'] ?? null, $request->user()?->id);

        return response()->json([
            'data' => $export,
        ], 201);
    }

    public function downloadAuditExport(AuditExport $export)
    {
        $path = storage_path('app/'.$export->file_path);
        if (! $export->file_path || ! file_exists($path)) {
            abort(404);
        }

        return new StreamedResponse(function () use ($path) {
            readfile($path);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Length' => filesize($path),
            'Content-Disposition' => 'attachment; filename="audit_export.csv"',
        ]);
    }

    public function alerts(SslHealthService $sslHealth, TenantStorageService $storage, TenantLimitService $limits)
    {
        $settings = $this->settingsPayload();
        $sslDays = (int) ($settings['ssl_alert_days'] ?? 14);

        $sslExpiring = $sslHealth->expiringSoon($sslDays)->map(function ($cert) {
            return [
                'domain' => $cert->domain?->hostname,
                'expires_at' => $cert->expires_at,
            ];
        })->values();

        $http3Issues = Domain::where('http3_enabled', true)
            ->whereNotIn('http3_status', ['ok', 'advertised'])
            ->get(['hostname', 'http3_status', 'http3_error', 'http3_checked_at']);

        $storageOver = [];
        $tenants = \App\Models\Tenant::all(['id', 'name']);
        foreach ($tenants as $tenant) {
            $limitBytes = $limits->storageLimitBytes($tenant);
            if (! $limitBytes) {
                continue;
            }
            $usage = $storage->usage($tenant);
            if ($usage['bytes'] > $limitBytes) {
                $storageOver[] = [
                    'tenant' => $tenant->name,
                    'usage_bytes' => $usage['bytes'],
                    'limit_bytes' => $limitBytes,
                ];
            }
        }

        return response()->json([
            'ssl_expiring' => $sslExpiring,
            'http3_issues' => $http3Issues,
            'storage_overages' => $storageOver,
        ]);
    }

    public function drills()
    {
        $drills = DisasterRecoveryDrill::query()
            ->with(['tenant:id,name', 'platformBackupRun:id,status,created_at', 'tenantBackupRun:id,status,created_at'])
            ->latest('id')
            ->paginate(25);

        return response()->json($drills);
    }

    public function runDrill(Request $request, DisasterRecoveryDrillService $drills)
    {
        $data = $request->validate([
            'platform_only' => ['nullable', 'boolean'],
            'all_tenants' => ['nullable', 'boolean'],
            'tenant_ids' => ['nullable', 'array'],
            'tenant_ids.*' => ['integer', 'min:1'],
        ]);

        $result = [
            'platform' => $drills->runPlatformDrill($request->user()?->id),
            'tenants' => [],
        ];

        if (! ($data['platform_only'] ?? false)) {
            if (! empty($data['tenant_ids'])) {
                foreach ($data['tenant_ids'] as $tenantId) {
                    $tenant = \App\Models\Tenant::find((int) $tenantId);
                    if ($tenant) {
                        $result['tenants'][] = $drills->runTenantDrill($tenant, $request->user()?->id);
                    }
                }
            } elseif (($data['all_tenants'] ?? false) === true) {
                $result['tenants'] = $drills->runAllTenantDrills($request->user()?->id);
            }
        }

        return response()->json([
            'data' => $result,
        ]);
    }

    private function settingsPayload(): array
    {
        $defaults = [
            'panel_allowed_ips' => config('services.panel.allowed_ips', ''),
            'force_2fa' => false,
            'rate_limit_per_minute' => config('services.platform.rate_limit_per_minute', 120),
            'backup_retention_days' => 7,
            'backup_s3_enabled' => false,
            'backup_keep_local' => true,
            'backup_s3_prefix' => 'tastypanel/backups',
            'ssl_alert_days' => 14,
            'http3_check_interval_minutes' => 30,
            'ssl_check_interval_hours' => 6,
            'backup_interval_hours' => 24,
            'analytics_interval_hours' => 6,
            'uptime_check_interval_minutes' => 5,
            'integrity_check_interval_hours' => 24,
            'cron_enabled' => true,
            'cron_timezone' => config('app.timezone', 'UTC'),
            'audit_export_retention_days' => 30,
            'search_enabled' => true,
            'search_driver' => 'database',
            'search_endpoint' => '',
            'search_api_key' => '',
            'search_index_prefix' => 'tastypanel',
            'brand_name' => 'TastyPanel',
            'brand_logo_url' => '',
            'brand_favicon_url' => '',
            'brand_primary_color' => '#2563eb',
            'brand_secondary_color' => '#111827',
            'brand_accent_color' => '#f97316',
            'brand_login_message' => 'Admin Dashboard',
            'alerts_emails' => '',
            'alerts_slack_webhook' => '',
            'alerts_interval_hours' => 24,
            'alerts_send_empty' => false,
            'alerts_last_sent_at' => null,
            'sso_enabled' => false,
            'sso_provider_label' => 'SSO',
            'sso_client_id' => '',
            'sso_client_secret' => '',
            'sso_auth_url' => '',
            'sso_token_url' => '',
            'sso_userinfo_url' => '',
            'sso_redirect_url' => config('app.url').'/admin/sso/callback',
            'sso_scopes' => 'openid email profile',
            'sso_allowed_domains' => '',
            'sso_auto_create' => false,
            'sso_enforce' => false,
            'sso_default_role' => 'tenant-admin',
            'sso_default_tenant_id' => null,
            'saml_enabled' => false,
            'saml_provider_label' => 'SAML SSO',
            'saml_idp_metadata_url' => '',
            'saml_idp_metadata_xml' => '',
            'saml_idp_entity_id' => '',
            'saml_idp_sso_url' => '',
            'saml_idp_slo_url' => '',
            'saml_idp_x509' => '',
            'saml_sp_entity_id' => config('app.url').'/admin/saml/metadata',
            'saml_acs_url' => config('app.url').'/admin/saml/acs',
            'saml_slo_url' => config('app.url').'/admin/saml/logout',
            'saml_nameid_format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            'saml_attribute_email' => 'email',
            'saml_attribute_name' => 'name',
            'saml_attribute_groups' => 'groups',
            'saml_allowed_domains' => '',
            'saml_auto_create' => false,
            'saml_enforce' => false,
            'saml_default_role' => 'tenant-admin',
            'saml_default_tenant_id' => null,
        ];

        return array_merge($defaults, PlatformSetting::getData());
    }

    private function queueStats(): array
    {
        $pending = 0;
        $failed = 0;
        try {
            $pending = DB::table('jobs')->count();
        } catch (\Throwable $e) {
            $pending = 0;
        }
        try {
            $failed = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            $failed = 0;
        }

        return [
            'pending' => $pending,
            'failed' => $failed,
        ];
    }
}
