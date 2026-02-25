<?php

namespace App\Services;

use App\Models\Tenant;

class TenantEnvPreviewService
{
    public function listKeys(Tenant $tenant): array
    {
        if (! $tenant->instance_root || ! is_dir($tenant->instance_root)) {
            return [
                'success' => false,
                'output' => 'Instance root not found.',
                'exit_code' => 1,
                'env_file' => null,
                'status' => 'missing',
                'keys' => [],
            ];
        }

        if (! $this->isSafeInstanceRoot((string) $tenant->instance_root)) {
            return [
                'success' => false,
                'output' => 'Unsafe tenant root path. Aborting.',
                'exit_code' => 1,
                'env_file' => null,
                'status' => 'error',
                'keys' => [],
            ];
        }

        $script = (string) config('services.tenant_env_preview.script', base_path('infrastructure/tenant-env-keys.sh'));
        if ($script === '' || ! file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Tenant env preview script not found.',
                'exit_code' => 1,
                'env_file' => null,
                'status' => 'error',
                'keys' => [],
            ];
        }

        $commandParts = [];
        if (config('services.tenant_env_preview.use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = $tenant->instance_root;

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));
        $output = [];
        $exitCode = 0;
        exec($escaped.' 2>&1', $output, $exitCode);

        $raw = implode("\n", $output);

        $envFile = null;
        $status = null;
        $keys = [];
        foreach (preg_split('/\r?\n/', $raw) as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, 'ENV_FILE=')) {
                $envFile = substr($line, strlen('ENV_FILE='));

                continue;
            }

            if (str_starts_with($line, 'STATUS=')) {
                $status = substr($line, strlen('STATUS='));

                continue;
            }

            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $line)) {
                $keys[] = $line;
            }
        }

        $keys = array_values(array_unique($keys));
        $status = $status ?: ($exitCode === 0 ? 'ok' : 'error');

        return [
            'success' => $exitCode === 0,
            'output' => $raw,
            'exit_code' => $exitCode,
            'env_file' => $envFile,
            'status' => $status,
            'keys' => $keys,
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
