<?php

namespace App\Services;

use App\Models\Tenant;

class TenantEnvSyncService
{
    public function deriveEnvKey(string $secretKey): string
    {
        $normalized = strtoupper(trim($secretKey));
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized ?? '') ?: 'SECRET';
        $normalized = preg_replace('/_+/', '_', $normalized) ?: 'SECRET';
        $normalized = trim($normalized, '_');
        if ($normalized === '') {
            $normalized = 'SECRET';
        }
        if (!preg_match('/^[A-Z]/', $normalized)) {
            $normalized = 'SECRET_' . $normalized;
        }

        return $normalized;
    }

    public function upsert(Tenant $tenant, string $envKey, string $value): array
    {
        return $this->run('upsert', $tenant, $envKey, $value);
    }

    public function remove(Tenant $tenant, string $envKey): array
    {
        return $this->run('remove', $tenant, $envKey, null);
    }

    private function run(string $action, Tenant $tenant, string $envKey, ?string $envValue): array
    {
        if (!$tenant->instance_root || !is_dir($tenant->instance_root)) {
            return [
                'success' => false,
                'output' => 'Tenant instance root is missing.',
                'exit_code' => 1,
            ];
        }

        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $envKey)) {
            return [
                'success' => false,
                'output' => 'Invalid env key format.',
                'exit_code' => 1,
            ];
        }

        $script = config('services.instances.env_sync_script', base_path('infrastructure/sync-tenant-env.sh'));
        if (!$script || !file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Tenant env sync script not found.',
                'exit_code' => 1,
            ];
        }

        $commandParts = [];
        if (config('services.instances.env_sync_use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = $action;
        $commandParts[] = $tenant->instance_root;
        $commandParts[] = $envKey;
        if ($action === 'upsert') {
            $commandParts[] = $envValue ?? '';
        }
        $commandParts[] = $tenant->instance_system_user ?: '';

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));
        $output = [];
        $exitCode = 0;
        exec($escaped . ' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
            'exit_code' => $exitCode,
            'env_key' => $envKey,
        ];
    }
}
