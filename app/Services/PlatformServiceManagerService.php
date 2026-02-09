<?php

namespace App\Services;

class PlatformServiceManagerService
{
    public function list(): array
    {
        $services = config('services.platform_service_manager.services', []);
        $result = [];

        foreach ($services as $key => $meta) {
            $status = $this->status((string) $key);
            $result[] = [
                'key' => (string) $key,
                'label' => $meta['label'] ?? (string) $key,
                'unit' => $status['unit'] ?? ($meta['unit'] ?? ''),
                'state' => $status['state'] ?? 'unknown',
                'managed' => (bool) ($status['managed'] ?? false),
                'detail' => $status['detail'] ?? null,
            ];
        }

        return $result;
    }

    public function status(string $serviceKey): array
    {
        $scriptResult = $this->runScript('status', $serviceKey);
        if (!$scriptResult['success']) {
            return [
                'service_key' => $serviceKey,
                'state' => 'unknown',
                'managed' => false,
                'detail' => $scriptResult['output'] ?: 'Status check failed.',
                'unit' => '',
            ];
        }

        $parsed = $this->parseKeyValueOutput($scriptResult['output']);
        return [
            'service_key' => $serviceKey,
            'state' => $parsed['STATE'] ?? 'unknown',
            'managed' => ($parsed['MANAGED'] ?? 'false') === 'true',
            'detail' => $parsed['DETAIL'] ?? null,
            'unit' => $parsed['UNIT'] ?? '',
            'raw_output' => $scriptResult['output'],
        ];
    }

    public function action(string $serviceKey, string $action): array
    {
        if (!in_array($action, ['start', 'stop', 'restart'], true)) {
            return [
                'success' => false,
                'output' => 'Unsupported action.',
                'exit_code' => 1,
                'status' => $this->status($serviceKey),
            ];
        }

        $result = $this->runScript('action', $serviceKey, $action);

        return [
            'success' => $result['success'],
            'output' => $result['output'],
            'exit_code' => $result['exit_code'],
            'status' => $this->status($serviceKey),
        ];
    }

    public function logs(string $serviceKey, int $lines = 120): array
    {
        $lines = max(10, min(500, $lines));
        $result = $this->runScript('logs', $serviceKey, (string) $lines);

        return [
            'success' => $result['success'],
            'output' => $result['output'],
            'exit_code' => $result['exit_code'],
            'lines' => $lines,
        ];
    }

    private function runScript(string $mode, string $serviceKey, ?string $arg = null): array
    {
        $services = config('services.platform_service_manager.services', []);
        if (!array_key_exists($serviceKey, $services)) {
            return [
                'success' => false,
                'output' => 'Unknown service key.',
                'exit_code' => 1,
            ];
        }

        $script = config('services.platform_service_manager.script', base_path('infrastructure/manage-platform-service.sh'));
        if (!$script || !file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Service manager script not found.',
                'exit_code' => 1,
            ];
        }

        $commandParts = [];
        if (config('services.platform_service_manager.use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = $mode;
        $commandParts[] = $serviceKey;
        if ($arg !== null && $arg !== '') {
            $commandParts[] = $arg;
        }

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

    private function parseKeyValueOutput(string $output): array
    {
        $parsed = [];
        foreach (preg_split('/\r?\n/', $output) as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $parsed[trim($key)] = trim($value);
        }

        return $parsed;
    }
}
