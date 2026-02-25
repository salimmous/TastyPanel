<?php

namespace App\Services;

use App\Models\Tenant;

class TenantQueueService
{
    public function __construct(private TenantDatabaseService $database) {}

    public function stats(Tenant $tenant): array
    {
        if (! $tenant->instance_db_name || ! $tenant->instance_db_user) {
            return [
                'failed' => null,
                'pending' => null,
                'error' => 'Instance database is not configured.',
            ];
        }

        $failed = null;
        $pending = null;
        $error = null;

        try {
            $connection = $this->database->connection($tenant);
            try {
                $failed = $connection->table('failed_jobs')->count();
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
            try {
                $pending = $connection->table('jobs')->count();
            } catch (\Throwable $e) {
                $error = $error ?: $e->getMessage();
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        } finally {
            $this->database->purge($tenant);
        }

        return [
            'failed' => $failed,
            'pending' => $pending,
            'error' => $error,
        ];
    }

    public function restart(Tenant $tenant): array
    {
        return $this->run($tenant, 'restart');
    }

    public function flushFailed(Tenant $tenant): array
    {
        return $this->run($tenant, 'flush');
    }

    public function retryFailed(Tenant $tenant): array
    {
        return $this->run($tenant, 'retry');
    }

    private function run(Tenant $tenant, string $action): array
    {
        if (! $tenant->instance_root || ! is_dir($tenant->instance_root)) {
            return [
                'success' => false,
                'output' => 'Instance root not found.',
            ];
        }

        $script = config('services.tenant_queue.script');
        if (! $script || ! file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Tenant queue script not found.',
            ];
        }

        $commandParts = [];
        if (config('services.tenant_queue.use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = $action;
        $commandParts[] = $tenant->instance_root;

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
}
