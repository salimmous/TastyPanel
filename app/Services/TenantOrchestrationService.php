<?php

namespace App\Services;

use App\Models\Tenant;

class TenantOrchestrationService
{
    public function runAction(Tenant $tenant, string $action): array
    {
        if (! in_array($action, ['start', 'stop', 'restart'], true)) {
            return [
                'success' => false,
                'message' => 'Unsupported action.',
            ];
        }

        if (empty($tenant->instance_root) || ! is_dir($tenant->instance_root)) {
            return [
                'success' => false,
                'message' => 'Instance root is missing.',
            ];
        }

        $script = config('services.instances.orchestrate_script');
        if (! $script || ! file_exists($script)) {
            return [
                'success' => false,
                'message' => 'Orchestration script not found.',
            ];
        }

        $commandParts = [];
        if (config('services.instances.orchestrate_use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = $action;
        $commandParts[] = $tenant->instance_key ?: (string) $tenant->id;
        $commandParts[] = $tenant->instance_root;
        $commandParts[] = config('services.instances.php_version', '8.3');
        $commandParts[] = $tenant->instance_php_socket ?: '';

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

    public function status(Tenant $tenant): array
    {
        $status = [
            'state' => 'unknown',
            'maintenance' => null,
            'socket' => null,
            'frontend_service' => null,
            'message' => null,
        ];

        if (! $tenant->instance_root || ! is_dir($tenant->instance_root)) {
            $status['state'] = 'missing';
            $status['message'] = 'Instance root not found.';

            return $status;
        }

        if ($tenant->instance_status && $tenant->instance_status !== 'ready') {
            $status['state'] = $tenant->instance_status;
            $status['message'] = $tenant->instance_last_error;
        }

        $maintenanceFile = rtrim($tenant->instance_root, '/').'/storage/framework/down';
        $maintenance = file_exists($maintenanceFile);
        $status['maintenance'] = $maintenance;

        if ($tenant->instance_php_socket) {
            $status['socket'] = file_exists($tenant->instance_php_socket);
        }

        $serviceName = 'tastypanel-'.($tenant->instance_key ?: $tenant->id).'-frontend.service';
        $serviceActive = $this->isServiceActive($serviceName);
        if ($serviceActive !== null) {
            $status['frontend_service'] = $serviceActive;
        }

        if ($status['state'] === 'unknown') {
            $status['state'] = $maintenance ? 'stopped' : 'running';
        }

        return $status;
    }

    private function isServiceActive(string $serviceName): ?bool
    {
        $output = [];
        $exitCode = 0;
        exec('systemctl is-active '.escapeshellarg($serviceName).' 2>/dev/null', $output, $exitCode);
        if ($exitCode === 0) {
            return true;
        }

        $output = [];
        $exitCode = 0;
        exec('systemctl status '.escapeshellarg($serviceName).' >/dev/null 2>&1', $output, $exitCode);
        if ($exitCode === 0) {
            return true;
        }

        // service unknown or inactive; report false if unit exists, null otherwise
        $output = [];
        $exitCode = 0;
        exec('systemctl list-unit-files '.escapeshellarg($serviceName).' 2>/dev/null | grep -q '.escapeshellarg($serviceName), $output, $exitCode);
        if ($exitCode === 0) {
            return false;
        }

        return null;
    }
}
