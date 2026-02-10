<?php

namespace App\Services;

use App\Models\Tenant;

class TenantArtisanService
{
    private const ALLOWED_ACTIONS = [
        'migrate',
        'migrate_status',
        'optimize_clear',
        'config_cache',
        'route_cache',
        'view_cache',
    ];

    public function run(Tenant $tenant, string $action): array
    {
        $action = trim($action);
        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            return [
                'success' => false,
                'output' => 'Unsupported action.',
            ];
        }

        if (!$tenant->instance_root || !is_dir($tenant->instance_root)) {
            return [
                'success' => false,
                'output' => 'Instance root not found.',
            ];
        }

        if (!$this->isSafeInstanceRoot((string) $tenant->instance_root)) {
            return [
                'success' => false,
                'output' => 'Unsafe tenant root path. Aborting.',
            ];
        }

        $script = (string) config('services.tenant_artisan.script');
        if ($script === '' || !file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Tenant artisan script not found.',
            ];
        }

        $commandParts = [];
        if (config('services.tenant_artisan.use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = $action;
        $commandParts[] = $tenant->instance_root;
        $commandParts[] = (string) ($tenant->instance_system_user ?: '');

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));

        $output = [];
        $exitCode = 0;
        exec($escaped . ' 2>&1', $output, $exitCode);

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

        return str_starts_with($root . '/', $base . '/');
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#/+#', '/', $path) ?: '';
        return rtrim($path, '/');
    }
}
