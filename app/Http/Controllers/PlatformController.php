<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BackupRun;
use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Models\Theme;
use App\Models\Plugin;
use App\Services\PhpMyAdminProvisioningService;
use App\Services\TenantAccessService;
use App\Services\TenantMailService;
use App\Services\TenantQuotaService;
use App\Services\CronManagementService;
use App\Services\SslProvisioningService;
use App\Services\NginxProvisioningService;
use App\Support\AdminPermissions;

class PlatformController extends Controller
{
    public function dashboard()
    {
        if (!PlatformInstallController::isInstalled()) {
            return redirect()->route('platform.install');
        }

        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $user = Auth::user();
        if ($user->role !== 'superadmin' && !$user->is_superadmin) {
            Auth::logout();
            return redirect()->route('platform.login')->withErrors(['email' => 'Unauthorized access.']);
        }

        $stats = [
            'tenants' => Tenant::count(),
            'users' => User::count(),
            'domains' => Domain::count(),
        ];

        $recentTenants = Tenant::with('domains')->latest()->take(5)->get();

        return view('platform.dashboard', compact('stats', 'recentTenants'));
    }

    public function overview()
    {
        if (!Auth::check())
            return redirect()->route('platform.login');

        // System metrics
        $load = sys_getloadavg();
        $systemMetrics = [
            'load' => [
                '1m' => round($load[0] ?? 0, 2),
                '5m' => round($load[1] ?? 0, 2),
                '15m' => round($load[2] ?? 0, 2),
            ],
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
        ];

        // Service statuses
        $services = [
            'nginx' => $this->checkServiceStatus('nginx'),
            'mysql' => 'running',
            'redis' => 'running',
            'php' => 'running',
        ];

        try {
            DB::connection()->getPdo();
            $services['mysql'] = 'running';
        } catch (\Throwable $e) {
            $services['mysql'] = 'stopped';
        }

        try {
            Redis::connection()->ping();
            $services['redis'] = 'running';
        } catch (\Throwable $e) {
            $services['redis'] = 'stopped';
        }

        // Quick stats
        $stats = [
            'tenants' => Tenant::count(),
            'users' => User::count(),
            'domains' => Domain::count(),
            'backups' => BackupRun::where('status', 'success')->count(),
        ];

        // Queue stats
        $queueSize = 0;
        $failedJobs = 0;
        try {
            $queueSize = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
        }

        // Recent activity
        $recentTenants = Tenant::with('domains')->latest()->take(5)->get();
        $recentBackups = BackupRun::latest()->take(5)->get();

        return view('platform.overview', compact(
            'systemMetrics',
            'services',
            'stats',
            'queueSize',
            'failedJobs',
            'recentTenants',
            'recentBackups'
        ));
    }

    private function getMemoryUsage(): array
    {
        $meminfo = $this->readMeminfo();
        if ($meminfo) {
            $total = $meminfo['MemTotal'] ?? 0;
            $available = $meminfo['MemAvailable'] ?? 0;
            $used = max($total - $available, 0);
            return [
                'total_mb' => round($total / 1024, 2),
                'used_mb' => round($used / 1024, 2),
                'free_mb' => round($available / 1024, 2),
                'percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
            ];
        }

        $usage = memory_get_usage(true);
        return [
            'total_mb' => null,
            'used_mb' => round($usage / 1024 / 1024, 2),
            'free_mb' => null,
            'percent' => null,
        ];
    }

    private function readMeminfo(): ?array
    {
        $path = '/proc/meminfo';
        if (!is_readable($path)) {
            return null;
        }

        $data = [];
        foreach (file($path) as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches)) {
                $data[$matches[1]] = (int) $matches[2];
            }
        }

        return $data ?: null;
    }

    private function getDiskUsage(): array
    {
        $root = '/';
        $total = @disk_total_space($root);
        $free = @disk_free_space($root);
        if ($total === false || $free === false) {
            return [
                'total_gb' => null,
                'used_gb' => null,
                'free_gb' => null,
                'percent' => null,
            ];
        }

        $used = max($total - $free, 0);
        return [
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_gb' => round($used / 1024 / 1024 / 1024, 2),
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
        ];
    }

    private function checkServiceStatus($service): string
    {
        $output = [];
        $exit = 0;
        @exec(sprintf('systemctl is-active %s 2>&1', escapeshellarg($service)), $output, $exit);
        if ($exit === 0) {
            return 'running';
        }
        return 'stopped';
    }

    public function tenants()
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $tenants = Tenant::with(['domains', 'users'])->paginate(20);
        return view('platform.tenants', compact('tenants'));
    }

    public function showTenant($id, TenantAccessService $accessService, TenantMailService $mailService, TenantQuotaService $quotaService, CronManagementService $cronService)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $tenant = Tenant::with(['domains', 'users', 'securityProfile', 'secrets', 'backupRuns'])->findOrFail($id);
        
        // ... (access, mail, security, quota) ...
        $access = $accessService->connectionInfo($tenant);
        $mail = $mailService->settingsPayload($tenant);
        $security = $tenant->securityProfile()->firstOrCreate(['tenant_id' => $tenant->id]);
        $quota = [
            'limits' => $quotaService->limitsFor($tenant),
            'usage' => $quotaService->usageSnapshot($tenant),
        ];

        // Fetch PHP FPM Settings
        // ... (php settings logic) ...
        $phpSettings = [];
        $fpmPoolPath = "/etc/php/8.3/fpm/pool.d/{$tenant->instance_key}.conf";
        if (File::exists($fpmPoolPath)) {
            $poolContent = File::get($fpmPoolPath);
            preg_match('/pm.max_children = (.*)/', $poolContent, $matches);
            $phpSettings['max_children'] = $matches[1] ?? 10;
            preg_match('/php_admin_value\[memory_limit\] = (.*)M/', $poolContent, $matches);
            $phpSettings['memory_limit'] = $matches[1] ?? 256;
            preg_match('/pm.max_requests = (.*)/', $poolContent, $matches);
            $phpSettings['max_requests'] = $matches[1] ?? 500;
        }

        // Fetch Cron Jobs
        $cronJobs = $cronService->getJobs($tenant);

        // Fetch Logs & Vhost ...
        // ... rest of the method ...
        $logs = [];
        $tenantKey = $tenant->instance_key ?: $tenant->slug; 
        $logPath = "/var/www/tastypanel-sites/{$tenantKey}/storage/logs/php-fpm.log";
        if (File::exists($logPath)) {
            $logs = array_slice(explode("\n", File::get($logPath)), -100);
            $logs = array_reverse(array_filter($logs));
        }

        $nginxLogs = [];
        $primaryDomain = $tenant->domains->where('is_primary', true)->first();
        if ($primaryDomain) {
            $nginxLogPath = "/var/log/nginx/{$primaryDomain->hostname}-error.log";
            if (File::exists($nginxLogPath)) {
                $nginxLogs = array_slice(explode("\n", File::get($nginxLogPath)), -50);
                $nginxLogs = array_reverse(array_filter($nginxLogs));
            }
        }

        $vhostContent = '';
        if ($primaryDomain) {
            $vhostPath = "/etc/nginx/sites-available/{$primaryDomain->hostname}.conf";
            if (File::exists($vhostPath)) {
                $vhostContent = File::get($vhostPath);
            }
        }

        $pmaUrl = $this->resolvePhpMyAdminUrl($tenant);

        $installLogPath = storage_path("logs/tenant-install-{$tenant->id}.log");
        $installLog = File::exists($installLogPath) ? array_slice(explode("\n", File::get($installLogPath)), -200) : [];

        return view('platform.tenants.show', compact('tenant', 'access', 'mail', 'security', 'quota', 'logs', 'nginxLogs', 'vhostContent', 'phpSettings', 'cronJobs', 'pmaUrl', 'installLog'));
    }

    public function updateVhost(Request $request, $id, NginxProvisioningService $nginxService)
    {
        $tenant = Tenant::findOrFail($id);
        $primaryDomain = $tenant->domains->where('is_primary', true)->first();

        if (!$primaryDomain) {
            return redirect()->back()->with('error', 'Primary domain not found.');
        }

        $validated = $request->validate([
            'vhost_content' => 'required|string',
        ]);

        $vhostPath = "/etc/nginx/sites-available/{$primaryDomain->hostname}.conf";
        
        // Use sudo to write the file (via shell since PHP might not have write access to /etc/nginx)
        $tempFile = tempnam(sys_get_temp_dir(), 'nginx_vhost');
        File::put($tempFile, $validated['vhost_content']);
        
        $result = Process::run("sudo cp \"$tempFile\" \"$vhostPath\" && sudo nginx -t && sudo systemctl reload nginx");
        unlink($tempFile);

        if ($result->failed()) {
            return redirect()->back()->with('error', 'Nginx Config Test Failed: ' . $result->errorOutput());
        }

        return redirect()->back()->with('success', 'Nginx configuration updated and reloaded.');
    }

    public function provisionSsl(Request $request, $id, SslProvisioningService $sslService)
    {
        $domain = Domain::findOrFail($id);
        
        $result = $sslService->provisionCertificate($domain, true);

        if ($result->status === 'error') {
            return redirect()->back()->with('error', 'SSL Provisioning Failed: ' . $result->last_error);
        }

        return redirect()->back()->with('success', 'SSL certificate issued successfully.');
    }

    public function updatePhpSettings(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        
        $validated = $request->validate([
            'memory_limit' => 'required|integer|min:64|max:4096',
            'max_children' => 'required|integer|min:1|max:100',
            'max_requests' => 'required|integer|min:1|max:10000',
        ]);

        $fpmPoolPath = "/etc/php/8.3/fpm/pool.d/{$tenant->instance_key}.conf";
        if (!File::exists($fpmPoolPath)) {
            return redirect()->back()->with('error', 'PHP-FPM pool configuration not found.');
        }

        $content = File::get($fpmPoolPath);
        $content = preg_replace('/pm.max_children = .*/', "pm.max_children = {$validated['max_children']}", $content);
        $content = preg_replace('/pm.max_requests = .*/', "pm.max_requests = {$validated['max_requests']}", $content);
        $content = preg_replace('/php_admin_value\[memory_limit\] = .*/', "php_admin_value[memory_limit] = {$validated['memory_limit']}M", $content);

        // Write back using sudo
        $tempFile = tempnam(sys_get_temp_dir(), 'fpm_pool');
        File::put($tempFile, $content);
        
        $result = Process::run("sudo cp \"$tempFile\" \"$fpmPoolPath\" && sudo systemctl reload php8.3-fpm");
        unlink($tempFile);

        if ($result->failed()) {
            return redirect()->back()->with('error', 'Failed to update PHP-FPM config: ' . $result->errorOutput());
        }

        return redirect()->back()->with('success', 'PHP settings updated and FPM reloaded.');
    }

    public function storeCronJob(Request $request, $id, CronManagementService $cronService)
    {
        $tenant = Tenant::findOrFail($id);
        $validated = $request->validate([
            'command' => 'required|string|max:500',
        ]);

        $cronService->addJob($tenant, $validated['command']);

        return redirect()->back()->with('success', 'Cron job added.');
    }

    public function destroyCronJob(Request $request, $id, $index, CronManagementService $cronService)
    {
        $tenant = Tenant::findOrFail($id);
        $cronService->removeJob($tenant, (int) $index);

        return redirect()->back()->with('success', 'Cron job removed.');
    }

    public function storeSecret(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        
        $validated = $request->validate([
            'secret_key' => 'required|string|max:255',
            'secret_value' => 'required|string',
        ]);

        $tenant->secrets()->updateOrCreate(
            ['secret_key' => $validated['secret_key']],
            [
                'encrypted_value' => encrypt($validated['secret_value']),
                'updated_by' => Auth::id(),
            ]
        );

        return redirect()->back()->with('success', 'Secret saved successfully.');
    }

    public function destroySecret($id, $secretId)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->secrets()->where('id', $secretId)->delete();

        return redirect()->back()->with('success', 'Secret deleted successfully.');
    }

    public function createTenantBackup($id)
    {
        $tenant = Tenant::findOrFail($id);
        \App\Jobs\TenantBackupJob::dispatch($tenant, Auth::id());

        return redirect()->back()->with('success', 'Backup started in background.');
    }

    public function downloadTenantBackup($id, $backupId)
    {
        $tenant = Tenant::findOrFail($id);
        $run = $tenant->backupRuns()->findOrFail($backupId);

        if ($run->status !== 'success' || !file_exists($run->path)) {
            return redirect()->back()->with('error', 'Backup file not found.');
        }

        return response()->download($run->path);
    }

    public function deleteTenantBackup($id, $backupId)
    {
        $tenant = Tenant::findOrFail($id);
        $run = $tenant->backupRuns()->findOrFail($backupId);

        if ($run->path && file_exists($run->path)) {
            @unlink($run->path);
        }

        $run->delete();

        return redirect()->back()->with('success', 'Backup deleted successfully.');
    }

    public function phpmyadminFrame($id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $tenant = Tenant::with('domains')->findOrFail($id);
        $pmaUrl = $this->resolvePhpMyAdminUrl($tenant);

        if (!$pmaUrl) {
            return redirect()->route('platform.tenants.show', $id)
                ->withErrors(['phpmyadmin' => 'phpMyAdmin URL not configured. Set PMA_URL or PMA_PATH in .env (e.g. PMA_URL=http://84.247.160.84:8443/phpmyadmin/).']);
        }

        return view('platform.phpmyadmin-frame', [
            'tenant' => $tenant,
            'pmaUrl' => $pmaUrl,
        ]);
    }

    /**
     * Resolve phpMyAdmin URL: single URL (PMA_URL or app.url + PMA_PATH) or per-tenant pma.<domain>.
     */
    private function resolvePhpMyAdminUrl(?Tenant $tenant = null): ?string
    {
        $single = config('services.phpmyadmin.url');
        if ($single !== null && $single !== '') {
            return rtrim($single, '/') . '/';
        }
        $path = config('services.phpmyadmin.path', '/phpmyadmin');
        if ($path !== null && $path !== '') {
            $base = rtrim(config('app.url'), '/');
            return $base . '/' . ltrim($path, '/');
        }
        if ($tenant) {
            $primaryDomain = $tenant->domains->firstWhere('is_primary', true) ?? $tenant->domains->first();
            $template = config('services.phpmyadmin.url_template');
            if ($template && $primaryDomain) {
                return str_replace(':domain', $primaryDomain->hostname, $template);
            }
            if ($primaryDomain) {
                return (request()->secure() ? 'https://' : 'http://') . 'pma.' . $primaryDomain->hostname;
            }
        }
        return null;
    }

    public function provisionPhpMyAdmin($id, PhpMyAdminProvisioningService $pma): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $tenant = Tenant::with('domains')->findOrFail($id);
        $result = $pma->provision($tenant);

        if ($result['success']) {
            return redirect()->route('platform.tenants.show', $id)
                ->with('success', 'phpMyAdmin set up for this site. Use "Open phpMyAdmin" to access it. Web login (admin) and DB login (pma_&lt;slug&gt;) passwords were generated—check server logs or re-run to see them.');
        }

        return redirect()->route('platform.tenants.show', $id)
            ->withErrors(['phpmyadmin' => $result['output'] ?? 'Provisioning failed.']);
    }

    public function themes()
    {
        if (!Auth::check()) return redirect()->route('platform.login');
        
        $themes = Theme::orderByDesc('is_active')->orderBy('name')->get();
        return view('platform.themes.index', compact('themes'));
    }

    public function marketplace()
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        // logic: fetch marketplace themes. For now using local themes flagged as is_marketplace
        $themes = Theme::where('is_marketplace', true)->get();
        return view('platform.themes.marketplace', compact('themes'));
    }

    public function uploadTheme(Request $request, \App\Services\ThemePackageService $packages)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $request->validate([
            'zip' => ['required', 'file', 'mimes:zip'],
        ]);

        try {
            // We use a temporary key based on filename, the service extracts real key from json
            $key = pathinfo($request->file('zip')->getClientOriginalName(), PATHINFO_FILENAME);
            $packages->importThemeZip($request->file('zip'), $key);
            
            return redirect()->route('platform.themes')->with('success', 'Theme uploaded successfully.');
        } catch (\Throwable $e) {
            return redirect()->route('platform.themes')->withErrors(['zip' => $e->getMessage()]);
        }
    }

    public function plugins()
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $plugins = Plugin::orderBy('name')->get();
        return view('platform.plugins.index', compact('plugins'));
    }

    public function createTenant()
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        return view('platform.tenant-create');
    }

    public function storeTenant(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:domains,hostname',
            'admin_email' => 'nullable|email|max:255',
            'admin_user' => 'nullable|string|max:255',
            'admin_password' => 'nullable|string|min:8|max:255',
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'status' => 'active',
        ]);

        $domain = Domain::create([
            'tenant_id' => $tenant->id,
            'hostname' => $validated['domain'],
            'is_primary' => true,
            'status' => 'pending',
        ]);

        $jobMeta = ['domain_id' => $domain->id];
        if (!empty($validated['admin_email'])) {
            $jobMeta['admin_email'] = $validated['admin_email'];
        }
        if (!empty($validated['admin_user'])) {
            $jobMeta['admin_user'] = $validated['admin_user'];
        }
        if (!empty($validated['admin_password'])) {
            $jobMeta['admin_password'] = $validated['admin_password'];
        }

        // Create Provisioning Job
        $job = \App\Models\ProvisioningJob::create([
            'tenant_id' => $tenant->id,
            'status' => 'pending',
            'message' => 'Provisioning started for ' . $domain->hostname,
            'meta' => $jobMeta,
        ]);

        // Dispatch Job
        \App\Jobs\ProcessTenantProvisioningJob::dispatch($tenant->id, $domain->id, $job->id);

        return redirect()->route('platform.tenants.show', $tenant->id)->with('success', 'Site created. Install (provisioning) started — status below.');
    }

    public function installApp(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'app_type' => 'required|in:wordpress,laravel,git',
            'repo_url' => 'nullable|required_if:app_type,git|url',
            'admin_user' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8',
        ]);

        $domain = $tenant->primaryDomain?->hostname ?: 'localhost';

        \App\Jobs\InstallTenantAppJob::dispatch(
            $tenant, 
            $validated['app_type'], 
            $validated['repo_url'] ?? null,
            [
                'admin_user' => $validated['admin_user'],
                'admin_email' => $validated['admin_email'],
                'admin_password' => $validated['admin_password'],
                'url' => "http://{$domain}"
            ]
        );

        return redirect()->back()->with('success', 'Application installation started in background.');
    }

    public function uninstallApp($id)
    {
        $tenant = Tenant::findOrFail($id);
        \App\Jobs\UninstallTenantAppJob::dispatch($tenant);

        return redirect()->back()->with('success', 'Application uninstallation started in background.');
    }

    public function destroyTenant($id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $tenant = Tenant::with('domains')->findOrFail($id);
        
        // Prepare data for cleanup job
        $tenantKey = $tenant->instance_key ?: $tenant->slug;
        $tenantRoot = $tenant->instance_root;
        $dbName = $tenant->instance_db_name;
        $dbUser = $tenant->instance_db_user;
        $systemUser = $tenant->instance_system_user;
        $domains = $tenant->domains->pluck('hostname')->toArray();

        // Dispatch cleanup job
        \App\Jobs\CleanupTenantInfrastructureJob::dispatch(
            $tenantKey,
            $tenantRoot,
            $dbName,
            $dbUser,
            $systemUser,
            $domains
        );

        $tenant->domains()->delete();
        $tenant->delete();

        return redirect()->route('platform.tenants')->with('success', 'Tenant deleted and infrastructure cleanup started in background.');
    }

    public function toggleTenantStatus($id)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $tenant = Tenant::findOrFail($id);
        $tenant->status = $tenant->status === 'active' ? 'inactive' : 'active';
        $tenant->save();

        $message = $tenant->status === 'active' ? 'Tenant activated successfully.' : 'Tenant deactivated successfully.';
        return redirect()->route('platform.tenants')->with('success', $message);
    }

    public function bulkActivate(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $tenantIds = $request->input('tenant_ids', []);
        Tenant::whereIn('id', $tenantIds)->update(['status' => 'active']);

        return redirect()->route('platform.tenants')->with('success', count($tenantIds) . ' tenant(s) activated successfully.');
    }

    public function bulkDeactivate(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $tenantIds = $request->input('tenant_ids', []);
        Tenant::whereIn('id', $tenantIds)->update(['status' => 'inactive']);

        return redirect()->route('platform.tenants')->with('success', count($tenantIds) . ' tenant(s) deactivated successfully.');
    }

    public function bulkDelete(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $tenantIds = $request->input('tenant_ids', []);

        // Delete associated domains first
        Domain::whereIn('tenant_id', $tenantIds)->delete();

        // Delete tenants
        Tenant::whereIn('id', $tenantIds)->delete();

        return redirect()->route('platform.tenants')->with('success', count($tenantIds) . ' tenant(s) deleted successfully.');
    }

    public function users()
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $users = User::with('tenant')->paginate(20);
        return view('platform.users', compact('users'));
    }

    public function settings()
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        // Load all platform settings with defaults
        $settings = $this->getSettingsPayload();

        return view('platform.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        // Validate settings
        $validated = $request->validate([
            // Security
            'panel_allowed_ips' => 'nullable|string',
            'force_2fa' => 'nullable|boolean',
            'rate_limit_per_minute' => 'nullable|integer|min:1',

            // Backups
            'backup_retention_days' => 'nullable|integer|min:1',
            'backup_s3_enabled' => 'nullable|boolean',
            'backup_keep_local' => 'nullable|boolean',
            'backup_s3_prefix' => 'nullable|string',

            // Alerts
            'ssl_alert_days' => 'nullable|integer|min:1',
            'alerts_emails' => 'nullable|string',
            'alerts_slack_webhook' => 'nullable|url',
            'alerts_interval_hours' => 'nullable|integer|min:1',

            // Scheduler
            'http3_check_interval_minutes' => 'nullable|integer|min:1',
            'ssl_check_interval_hours' => 'nullable|integer|min:1',
            'backup_interval_hours' => 'nullable|integer|min:1',
            'analytics_interval_hours' => 'nullable|integer|min:1',
            'uptime_check_interval_minutes' => 'nullable|integer|min:1',
            'integrity_check_interval_hours' => 'nullable|integer|min:1',
            'cron_enabled' => 'nullable|boolean',
            'cron_timezone' => 'nullable|string',

            // Branding
            'brand_name' => 'nullable|string',
            'brand_logo_url' => 'nullable|url',
            'brand_favicon_url' => 'nullable|url',
            'brand_primary_color' => 'nullable|string',
            'brand_secondary_color' => 'nullable|string',
            'brand_accent_color' => 'nullable|string',
            'brand_login_message' => 'nullable|string',

            // Search
            'search_enabled' => 'nullable|boolean',
            'search_driver' => 'nullable|string',
            'search_endpoint' => 'nullable|url',
            'search_api_key' => 'nullable|string',

            // SSO
            'sso_enabled' => 'nullable|boolean',
            'sso_provider_label' => 'nullable|string',
            'sso_client_id' => 'nullable|string',
            'sso_client_secret' => 'nullable|string',
            'sso_auth_url' => 'nullable|url',
            'sso_token_url' => 'nullable|url',
            'sso_userinfo_url' => 'nullable|url',

            // SAML
            'saml_enabled' => 'nullable|boolean',
            'saml_provider_label' => 'nullable|string',
            'saml_idp_metadata_url' => 'nullable|url',
            'saml_idp_entity_id' => 'nullable|string',
        ]);

        // Merge with current settings and update
        $current = PlatformSetting::getData();
        $updated = array_merge($current, $validated);
        PlatformSetting::updateData($updated);

        return redirect()->route('platform.settings')->with('success', 'Settings updated successfully.');
    }

    public function sendTestEmail(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $email = $request->input('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'Invalid email address for testing.');
        }

        try {
            \Illuminate\Support\Facades\Mail::raw('This is a test email from TastyPanel to verify your SMTP configuration.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('TastyPanel SMTP Test');
            });

            return back()->with('success', 'Test email sent successfully to ' . $email);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    private function getSettingsPayload(): array
    {
        $defaults = [
            // Security
            'panel_allowed_ips' => config('services.panel.allowed_ips', ''),
            'force_2fa' => false,
            'rate_limit_per_minute' => config('services.platform.rate_limit_per_minute', 120),

            // Backups
            'backup_retention_days' => 7,
            'backup_s3_enabled' => false,
            'backup_keep_local' => true,
            'backup_s3_prefix' => 'tastypanel/backups',

            // Alerts
            'ssl_alert_days' => 14,
            'alerts_emails' => '',
            'alerts_slack_webhook' => '',
            'alerts_interval_hours' => 24,
            'alerts_send_empty' => false,

            // Scheduler
            'http3_check_interval_minutes' => 30,
            'ssl_check_interval_hours' => 6,
            'backup_interval_hours' => 24,
            'analytics_interval_hours' => 6,
            'uptime_check_interval_minutes' => 5,
            'integrity_check_interval_hours' => 24,
            'cron_enabled' => true,
            'cron_timezone' => config('app.timezone', 'UTC'),

            // Branding
            'brand_name' => 'TastyPanel',
            'brand_logo_url' => '',
            'brand_favicon_url' => '',
            'brand_primary_color' => '#2563eb',
            'brand_secondary_color' => '#111827',
            'brand_accent_color' => '#f97316',
            'brand_login_message' => 'Admin Dashboard',

            // Search
            'search_enabled' => true,
            'search_driver' => 'database',
            'search_endpoint' => '',
            'search_api_key' => '',
            'search_index_prefix' => 'tastypanel',

            // SSO/OIDC
            'sso_enabled' => false,
            'sso_provider_label' => 'SSO',
            'sso_client_id' => '',
            'sso_client_secret' => '',
            'sso_auth_url' => '',
            'sso_token_url' => '',
            'sso_userinfo_url' => '',
            'sso_redirect_url' => config('app.url') . '/admin/sso/callback',
            'sso_scopes' => 'openid email profile',
            'sso_allowed_domains' => '',
            'sso_auto_create' => false,
            'sso_enforce' => false,

            // SAML
            'saml_enabled' => false,
            'saml_provider_label' => 'SAML SSO',
            'saml_idp_metadata_url' => '',
            'saml_idp_metadata_xml' => '',
            'saml_idp_entity_id' => '',
            'saml_idp_sso_url' => '',
            'saml_idp_x509' => '',
            'saml_attribute_email' => 'email',
            'saml_attribute_name' => 'name',
        ];

        return array_merge($defaults, PlatformSetting::getData());
    }

    // --- Advanced Features ---

    public function analytics(\App\Services\AnalyticsService $analytics)
    {
        if (!Auth::check())
            return redirect()->route('platform.login');

        $stats = $analytics->getPlatformOverview();
        return view('platform.analytics', compact('stats'));
    }

    public function backups()
    {
        if (!Auth::check())
            return redirect()->route('platform.login');

        $backups = BackupRun::latest()->paginate(20);
        return view('platform.backups', compact('backups'));
    }

    public function system()
    {
        if (!Auth::check())
            return redirect()->route('platform.login');

        $services = [
            'nginx' => 'running', // Assumed running if we are here
            'mysql' => 'stopped',
            'redis' => 'stopped',
            'php' => 'running',
        ];

        try {
            DB::connection()->getPdo();
            $services['mysql'] = 'running';
        } catch (\Throwable $e) {
        }

        try {
            Redis::connection()->ping();
            $services['redis'] = 'running';
        } catch (\Throwable $e) {
        }

        $queueSize = 0;
        $failedJobs = 0;
        try {
            $queueSize = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
        }

        return view('platform.system', compact('services', 'queueSize', 'failedJobs'));
    }

    public function auditLogs()
    {
        if (!Auth::check())
            return redirect()->route('platform.login');

        $logs = AuditLog::with('user')->latest()->paginate(25);
        return view('platform.audit_logs', compact('logs'));
    }

    // --- Auth ---

    public function showLogin(): View
    {
        return view('platform.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if (!AdminPermissions::isSuperadmin($user)) {
                Auth::logout();
                return back()->withErrors(['email' => 'Unauthorized access.']);
            }

            $settings = PlatformSetting::getData();
            if (($settings['force_2fa'] ?? false) && !$user->two_factor_enabled) {
                Auth::logout();
                return back()->withErrors(['email' => 'Two-factor authentication is required. Enable 2FA for your account first.']);
            }

            $request->session()->regenerate();

            if ($user->two_factor_enabled) {
                $this->sendTwoFactorCode($user);
                $request->session()->put('two_factor_verified', false);
                $this->auditAuthEvent('login_2fa_challenge', $user, $request);
                return redirect()->route('platform.2fa');
            }

            $request->session()->put('two_factor_verified', true);
            $this->auditAuthEvent('login', $user, $request);
            return redirect()->intended(route('platform.dashboard'));

        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        Auth::logout();
        $request->session()->forget(['two_factor_verified']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($user) {
            $this->auditAuthEvent('logout', $user, $request);
        }
        return redirect()->route('platform.login');
    }

    private function sendTwoFactorCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $user->two_factor_code = Hash::make($code);
        $user->two_factor_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            Mail::raw("Your TastyPanel verification code is: {$code}", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Your TastyPanel verification code');
            });
        } catch (\Throwable $e) {
            // Ignore email failures in environments without mail.
        }
    }

    private function auditAuthEvent(string $action, User $user, Request $request): void
    {
        try {
            AuditLog::create([
                'user_id' => $user->id,
                'tenant_id' => null,
                'action' => $action,
                'resource_type' => 'user',
                'resource_id' => $user->id,
                'description' => $action,
                'old_values' => null,
                'new_values' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => 'success',
                'error_message' => null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    // --- Queue Management ---

    public function queue()
    {
        if (!Auth::check())
            return redirect()->route('platform.login');

        // Get queue statistics
        $stats = $this->getQueueStats();

        // Get pending jobs
        $pendingJobs = [];
        try {
            $pendingJobs = DB::table('jobs')
                ->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')
                ->orderBy('id', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'name' => $payload['displayName'] ?? 'Unknown',
                        'attempts' => $job->attempts,
                        'created_at' => $job->created_at,
                    ];
                });
        } catch (\Throwable $e) {
            $pendingJobs = [];
        }

        // Get failed jobs
        $failedJobs = [];
        try {
            $failedJobs = DB::table('failed_jobs')
                ->select('id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at')
                ->orderBy('id', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'id' => $job->id,
                        'uuid' => $job->uuid,
                        'queue' => $job->queue,
                        'name' => $payload['displayName'] ?? 'Unknown',
                        'exception' => Str::limit($job->exception, 200),
                        'failed_at' => $job->failed_at,
                    ];
                });
        } catch (\Throwable $e) {
            $failedJobs = [];
        }

        return view('platform.queue', compact('stats', 'pendingJobs', 'failedJobs'));
    }

    public function queueRestart()
    {
        if (!Auth::check())
            return redirect()->route('platform.login');

        $output = [];
        $exit = 0;
        @exec('php artisan queue:restart 2>&1', $output, $exit);

        if ($exit === 0) {
            return redirect()->route('platform.queue')->with('success', 'Queue workers restarted successfully.');
        }

        return redirect()->route('platform.queue')->with('error', 'Failed to restart queue workers.');
    }

    public function queueFlushFailed()
    {
        if (!Auth::check())
            return redirect()->route('platform.login');

        $output = [];
        $exit = 0;
        @exec('php artisan queue:flush 2>&1', $output, $exit);

        if ($exit === 0) {
            return redirect()->route('platform.queue')->with('success', 'Failed jobs flushed successfully.');
        }

        return redirect()->route('platform.queue')->with('error', 'Failed to flush failed jobs.');
    }

    private function getQueueStats(): array
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

    public function services()
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $services = [
            'nginx' => [
                'name' => 'Nginx Web Server',
                'service' => 'nginx',
                'status' => $this->checkServiceStatus('nginx'),
                'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>',
            ],
            'mysql' => [
                'name' => 'MySQL Database',
                'service' => 'mysql',
                'status' => $this->checkServiceStatus('mysql'),
                'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>',
            ],
            'redis' => [
                'name' => 'Redis Cache',
                'service' => 'redis',
                'status' => $this->checkServiceStatus('redis'),
                'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>',
            ],
            'php' => [
                'name' => 'PHP-FPM',
                'service' => 'php8.1-fpm',
                'status' => $this->checkServiceStatus('php8.1-fpm'),
                'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>',
            ],
            'supervisor' => [
                'name' => 'Supervisor',
                'service' => 'supervisor',
                'status' => $this->checkServiceStatus('supervisor'),
                'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>',
            ]
        ];

        return view('platform.services', compact('services'));
    }

    public function serviceAction(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $service = $request->input('service');
        $action = $request->input('action');

        if (!in_array($action, ['start', 'stop', 'restart'])) {
            return back()->with('error', 'Invalid action');
        }

        $cmd = sprintf('sudo systemctl %s %s 2>&1', escapeshellarg($action), escapeshellarg($service));
        $output = [];
        $exit = 0;
        @exec($cmd, $output, $exit);

        if ($exit === 0) {
            return back()->with('success', "Service {$service} {$action}ed successfully.");
        }

        return back()->with('error', "Failed to {$action} {$service}. Output: " . implode("\n", $output));
    }

    public function serviceLogs(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $service = $request->query('service');
        $logFile = '';

        switch ($service) {
            case 'nginx':
                $logFile = '/var/log/nginx/error.log';
                break;
            case 'mysql':
                $logFile = '/var/log/mysql/error.log';
                break;
            case 'redis':
                $logFile = '/var/log/redis/redis-server.log';
                break;
            case 'php8.1-fpm':
                $logFile = '/var/log/php8.1-fpm.log';
                break;
            default:
                return response()->json(['error' => 'Unknown service'], 400);
        }

        if (!file_exists($logFile) || !is_readable($logFile)) {
            // Try journalctl if file not accessible
            $cmd = sprintf('journalctl -u %s -n 50 --no-pager', escapeshellarg($service));
            $output = [];
            @exec($cmd, $output);
            return response()->json(['logs' => implode("\n", $output)]);
        }

        $content = shell_exec("tail -n 50 " . escapeshellarg($logFile));
        return response()->json(['logs' => $content]);
    }

    public function deployNginxSafe(\App\Services\NginxSafeDeployService $deployer)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $result = $deployer->deploy();

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function drills()
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $drills = \App\Models\DisasterRecoveryDrill::with('creator')
            ->where('scope', 'platform')
            ->latest()
            ->paginate(20);

        return view('platform.drills', compact('drills'));
    }

    public function runDrill(\App\Services\DisasterRecoveryDrillService $drillService)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        try {
            // Ideally queue this, but service seems fast enough for small backups or simple verification
            $drill = $drillService->runPlatformDrill(Auth::id());

            if ($drill->status === 'passed') {
                return back()->with('success', 'Disaster recovery drill passed successfully.');
            }

            return back()->with('error', 'Disaster recovery drill failed: ' . $drill->message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Drill execution failed: ' . $e->getMessage());
        }
    }

    public function createBackup(\App\Services\BackupService $backupService)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        try {
            $backupService->run(Auth::id());
            return back()->with('success', 'Backup created successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function downloadBackup($id)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $backup = \App\Models\BackupRun::findOrFail($id);

        if ($backup->disk === 's3') {
            if ($backup->remote_path) {
                return \Illuminate\Support\Facades\Storage::disk('s3')->download($backup->remote_path);
            }
        } elseif ($backup->path && file_exists($backup->path . '/backup.zip')) {
            return response()->download($backup->path . '/backup.zip');
        }

        return back()->with('error', 'Backup file not found.');
    }

    public function deleteBackup($id)
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $backup = \App\Models\BackupRun::findOrFail($id);

        try {
            if ($backup->disk === 's3' && $backup->remote_path) {
                \Illuminate\Support\Facades\Storage::disk('s3')->delete($backup->remote_path);
            } elseif ($backup->path) {
                if (is_dir($backup->path)) {
                    $this->deleteDirectory($backup->path);
                }
            }

            $backup->delete();
            return back()->with('success', 'Backup deleted successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete backup: ' . $e->getMessage());
        }
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir))
            return true;
        if (!is_dir($dir))
            return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..')
                continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item))
                return false;
        }
        return rmdir($dir);
    }
}
