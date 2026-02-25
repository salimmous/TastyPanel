<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Support\Str;

class InstanceProvisioningService
{
    public function __construct(private TenantAccessService $tenantAccess) {}

    public function provisionTenant(Tenant $tenant, ?Domain $domain = null): Tenant
    {
        return $this->provisionTenantWithResult($tenant, $domain)['tenant'];
    }

    public function provisionTenantWithResult(Tenant $tenant, ?Domain $domain = null): array
    {
        $wasReady = $tenant->instance_status === 'ready' && ! empty($tenant->instance_root);

        if ($tenant->instance_status === 'ready' && $tenant->instance_root) {
            if (! $this->isSafeInstanceRoot((string) $tenant->instance_root)) {
                $tenant->instance_status = 'error';
                $tenant->instance_last_error = 'Unsafe tenant root detected. Provisioning aborted.';
                $tenant->save();

                return [
                    'tenant' => $tenant,
                    'was_ready' => $wasReady,
                    'fresh_provisioned' => false,
                    'success' => false,
                    'output' => $tenant->instance_last_error,
                ];
            }

            $frontendResult = $this->provisionFrontendIfEnabled($tenant, $domain);
            if (! $frontendResult['success']) {
                $tenant->instance_status = 'error';
                $tenant->instance_last_error = $frontendResult['output'];
                $tenant->save();
            }
            $accessResult = $this->ensureTenantAccessIfEnabled($tenant);
            if (! $accessResult['success']) {
                $tenant->instance_status = 'error';
                $tenant->instance_last_error = $accessResult['output'];
                $tenant->save();
            }

            return [
                'tenant' => $tenant,
                'was_ready' => $wasReady,
                'fresh_provisioned' => false,
                'success' => $tenant->instance_status === 'ready',
                'output' => $tenant->instance_last_error,
            ];
        }

        $settings = config('services.instances', []);
        $repo = $settings['repo'] ?? '';
        if (! $repo) {
            $repo = 'default';
        }

        $tenant->instance_status = 'provisioning';
        $tenant->instance_last_error = null;
        $tenant->save();

        $instanceKey = $tenant->instance_key ?: $this->buildInstanceKey($tenant);
        $root = rtrim((string) ($settings['root'] ?? '/var/www/tastypanel-sites'), '/')
            .'/'.$instanceKey;
        $publicRoot = $root.'/public';
        $phpVersion = (string) ($settings['php_version'] ?? '8.3');
        $phpSocket = "/run/php/php{$phpVersion}-fpm-{$instanceKey}.sock";
        $dbName = $tenant->instance_db_name ?: 'tb_'.$tenant->id;
        $dbUser = $tenant->instance_db_user ?: 'tb_'.$tenant->id;
        $dbPass = $tenant->instance_db_password ?: Str::random(24);
        $systemUser = $tenant->instance_system_user ?: $this->resolveSystemUser($tenant);

        if (! $this->isSafeInstanceRoot($root)) {
            $tenant->instance_status = 'error';
            $tenant->instance_last_error = 'Unsafe tenant root path. Provisioning aborted.';
            $tenant->save();

            return [
                'tenant' => $tenant,
                'was_ready' => $wasReady,
                'fresh_provisioned' => false,
                'success' => false,
                'output' => $tenant->instance_last_error,
            ];
        }

        $appUrl = $this->resolveAppUrl($tenant, $domain);

        $tenant->instance_key = $instanceKey;
        $tenant->instance_root = $root;
        $tenant->instance_public_root = $publicRoot;
        $tenant->instance_php_socket = $phpSocket;
        $tenant->instance_db_name = $dbName;
        $tenant->instance_db_user = $dbUser;
        $tenant->instance_db_password = $dbPass;
        $tenant->instance_system_user = $systemUser;
        $tenant->save();

        $maxWorkers = (int) ($tenant->queueProfile?->max_workers ?? 0);
        $fpmMaxChildren = $maxWorkers > 0
            ? max(4, $maxWorkers * 4)
            : (int) config('services.instances.fpm_max_children', 10);
        $fpmMemoryLimit = (int) config('services.instances.fpm_memory_limit_mb', 256);
        $fpmMaxRequests = (int) config('services.instances.fpm_max_requests', 500);

        $result = $this->runScript([
            $instanceKey,
            $root,
            $repo,
            (string) ($settings['branch'] ?? 'main'),
            $dbName,
            $dbUser,
            $dbPass,
            $phpVersion,
            $appUrl,
            $systemUser,
        ], [
            'FPM_PM_MAX_CHILDREN' => (string) $fpmMaxChildren,
            'FPM_PM_MAX_REQUESTS' => (string) $fpmMaxRequests,
            'FPM_MEMORY_LIMIT_MB' => (string) $fpmMemoryLimit,
        ]);

        if (! $result['success']) {
            $tenant->instance_status = 'error';
            $tenant->instance_last_error = $result['output'];
            $tenant->save();

            return [
                'tenant' => $tenant,
                'was_ready' => $wasReady,
                'fresh_provisioned' => false,
                'success' => false,
                'output' => $result['output'],
            ];
        }

        $tenant->instance_status = 'ready';
        $tenant->instance_last_error = null;
        $tenant->instance_installed_at = now();
        $tenant->save();

        $frontendResult = $this->provisionFrontendIfEnabled($tenant, $domain);
        if (! $frontendResult['success']) {
            $tenant->instance_status = 'error';
            $tenant->instance_last_error = $frontendResult['output'];
            $tenant->save();

            return [
                'tenant' => $tenant,
                'was_ready' => $wasReady,
                'fresh_provisioned' => true,
                'success' => false,
                'output' => $frontendResult['output'],
            ];
        }

        $accessResult = $this->ensureTenantAccessIfEnabled($tenant);
        if (! $accessResult['success']) {
            $tenant->instance_status = 'error';
            $tenant->instance_last_error = $accessResult['output'];
            $tenant->save();

            return [
                'tenant' => $tenant,
                'was_ready' => $wasReady,
                'fresh_provisioned' => true,
                'success' => false,
                'output' => $accessResult['output'],
            ];
        }

        return [
            'tenant' => $tenant->fresh(),
            'was_ready' => $wasReady,
            'fresh_provisioned' => ! $wasReady,
            'success' => true,
            'output' => '',
        ];
    }

    private function buildInstanceKey(Tenant $tenant): string
    {
        $base = Str::slug($tenant->slug ?: $tenant->name ?: 'tenant');
        $base = substr($base, 0, 16);
        if (! $base) {
            $base = 'tenant';
        }

        return $base.'-'.$tenant->id;
    }

    private function resolveAppUrl(Tenant $tenant, ?Domain $domain): string
    {
        $hostname = $domain?->hostname;
        if (! $hostname) {
            $primary = $tenant->primaryDomain()->first();
            $hostname = $primary?->hostname;
        }
        if ($hostname) {
            return 'https://'.$hostname;
        }

        return 'http://localhost';
    }

    private function resolveSystemUser(Tenant $tenant): string
    {
        $prefix = (string) config('services.instances.system_user_prefix', 'tbapp');
        $prefix = preg_replace('/[^a-z0-9]/', '', strtolower($prefix)) ?: 'tbapp';

        return $prefix.$tenant->id;
    }

    private function isSafeInstanceRoot(string $root): bool
    {
        $base = (string) config('services.instances.root', '/var/www/tastypanel-sites');
        $base = $this->normalizePath($base);
        $root = $this->normalizePath($root);

        if ($base === '' || $root === '') {
            return false;
        }

        return str_starts_with($root.'/', $base.'/');
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#/+#', '/', $path) ?: '';

        return rtrim($path, '/');
    }

    private function runScript(array $args, array $env = []): array
    {
        $script = config('services.instances.script');
        if (! $script || ! file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Instance provision script not found.',
                'exit_code' => 1,
            ];
        }

        $commandParts = [];
        if (config('services.instances.use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        if ($env !== []) {
            $commandParts[] = 'env';
            foreach ($env as $key => $value) {
                $commandParts[] = $key.'='.$value;
            }
        }
        $commandParts[] = $script;
        foreach ($args as $arg) {
            $commandParts[] = $arg;
        }

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));

        $output = [];
        $exitCode = 0;
        exec($escaped.' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
            'exit_code' => $exitCode,
        ];
    }

    private function provisionFrontendIfEnabled(Tenant $tenant, ?Domain $domain): array
    {
        if (! config('services.instances.frontend_auto', false)) {
            return ['success' => true, 'output' => '', 'exit_code' => 0];
        }

        if (! $tenant->instance_root || ! is_dir($tenant->instance_root)) {
            return ['success' => false, 'output' => 'Instance root missing for frontend provisioning.', 'exit_code' => 1];
        }

        $frontendDir = rtrim($tenant->instance_root, '/').'/frontend';
        if (is_dir($frontendDir) && file_exists($frontendDir.'/.next/BUILD_ID')) {
            return ['success' => true, 'output' => 'Frontend already provisioned.', 'exit_code' => 0];
        }

        $hostname = $domain?->hostname ?: $tenant->domains()->where('is_primary', true)->value('hostname');
        if (! $hostname) {
            return ['success' => false, 'output' => 'Primary domain is missing for frontend provisioning.', 'exit_code' => 1];
        }

        $script = config('services.instances.frontend_script');
        if (! $script || ! file_exists($script)) {
            return ['success' => false, 'output' => 'Frontend provision script not found.', 'exit_code' => 1];
        }

        $apiBase = (string) config('services.instances.frontend_api_base', '');
        if ($apiBase === '') {
            return ['success' => false, 'output' => 'FRONTEND_PLATFORM_API_BASE is not configured.', 'exit_code' => 1];
        }

        $baseRoot = (string) config('services.instances.root', '/var/www/tastypanel-sites');
        $tenantEnv = (string) ($domain?->environment ?: 'production');

        $commandParts = [];
        if (config('services.instances.frontend_use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = 'env';
        $commandParts[] = 'TENANT_KEY='.($tenant->instance_key ?: ('tenant-'.$tenant->id));
        $commandParts[] = 'TENANT_ID='.$tenant->id;
        $commandParts[] = 'TENANT_HOST='.$hostname;
        $commandParts[] = 'PLATFORM_API_BASE='.$apiBase;
        $commandParts[] = 'TENANT_ENV='.$tenantEnv;
        $commandParts[] = 'BASE_DIR='.$baseRoot;
        $commandParts[] = $script;

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));

        $output = [];
        $exitCode = 0;
        exec($escaped.' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
            'exit_code' => $exitCode,
        ];
    }

    public function deprovisionFrontend(Tenant $tenant): array
    {
        $script = config('services.instances.frontend_deprovision_script');
        if (! $script || ! file_exists($script)) {
            return ['success' => false, 'output' => 'Frontend deprovision script not found.', 'exit_code' => 1];
        }

        $baseRoot = (string) config('services.instances.root', '/var/www/tastypanel-sites');
        $commandParts = [];
        if (config('services.instances.frontend_use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = 'env';
        $commandParts[] = 'TENANT_KEY='.($tenant->instance_key ?: ('tenant-'.$tenant->id));
        $commandParts[] = 'BASE_DIR='.$baseRoot;
        $commandParts[] = $script;

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));

        $output = [];
        $exitCode = 0;
        exec($escaped.' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
            'exit_code' => $exitCode,
        ];
    }

    public function deprovisionTenant(Tenant $tenant, bool $removeAccess = true): array
    {
        $result = [
            'success' => true,
            'frontend' => ['success' => true, 'output' => 'Skipped.'],
            'instance' => ['success' => true, 'output' => 'Skipped.'],
            'access' => ['success' => true, 'output' => 'Skipped.'],
        ];

        $frontend = $this->deprovisionFrontend($tenant);
        $result['frontend'] = $frontend;
        if (! ($frontend['success'] ?? false)) {
            $result['success'] = false;
        }

        $instance = $this->runDeprovisionScript($tenant);
        $result['instance'] = $instance;
        if (! ($instance['success'] ?? false)) {
            $result['success'] = false;
        }

        if ($removeAccess) {
            $access = $this->tenantAccess->removeAccess($tenant);
            $result['access'] = $access;
            if (! ($access['success'] ?? false)) {
                $result['success'] = false;
            }
        }

        if ($result['success']) {
            $tenant->instance_status = 'pending';
            $tenant->instance_last_error = null;
            $tenant->instance_installed_at = null;
            $tenant->save();
        }

        return $result;
    }

    private function runDeprovisionScript(Tenant $tenant): array
    {
        $script = (string) config('services.instances.deprovision_script');
        if ($script === '' || ! file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Instance deprovision script not found.',
                'exit_code' => 1,
            ];
        }

        $commandParts = [];
        if (config('services.instances.deprovision_use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = (string) ($tenant->instance_key ?: ('tenant-'.$tenant->id));
        $commandParts[] = (string) ($tenant->instance_root ?: '');
        $commandParts[] = (string) ($tenant->instance_db_name ?: '');
        $commandParts[] = (string) ($tenant->instance_db_user ?: '');
        $commandParts[] = (string) config('services.instances.php_version', '8.3');
        $commandParts[] = (string) ($tenant->instance_system_user ?: $this->resolveSystemUser($tenant));

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));
        $output = [];
        $exitCode = 0;
        exec($escaped.' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
            'exit_code' => $exitCode,
        ];
    }

    private function ensureTenantAccessIfEnabled(Tenant $tenant): array
    {
        $script = (string) config('services.instances.access_script');
        if ($script === '' || ! file_exists($script)) {
            return ['success' => true, 'output' => 'Tenant access provisioning disabled.'];
        }

        return $this->tenantAccess->ensureAccess($tenant);
    }
}
