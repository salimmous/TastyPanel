<?php

namespace App\Services;

use App\Models\Tenant;

class TenantDeployService
{
    private const ALLOWED_MODES = [
        'full',
        'git_pull',
        'composer_install',
    ];

    public function run(Tenant $tenant, string $mode): array
    {
        $mode = trim($mode);
        if (! in_array($mode, self::ALLOWED_MODES, true)) {
            return [
                'success' => false,
                'output' => 'Unsupported deploy mode.',
            ];
        }

        if (! $tenant->instance_root || ! is_dir($tenant->instance_root)) {
            return [
                'success' => false,
                'output' => 'Instance root not found.',
            ];
        }

        if (! $this->isSafeInstanceRoot((string) $tenant->instance_root)) {
            return [
                'success' => false,
                'output' => 'Unsafe tenant root path. Aborting.',
            ];
        }

        $script = (string) config('services.tenant_deploy.script', base_path('infrastructure/tenant-deploy.sh'));
        if ($script === '' || ! file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Tenant deploy script not found.',
            ];
        }

        $commandParts = [];
        if (config('services.tenant_deploy.use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = $mode;
        $commandParts[] = $tenant->instance_root;
        $commandParts[] = (string) ($tenant->instance_system_user ?: '');

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
}
