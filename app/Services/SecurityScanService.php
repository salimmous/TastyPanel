<?php

namespace App\Services;

use App\Models\SecurityScan;

class SecurityScanService
{
    public function run(string $targetPath, ?int $userId = null, string $type = 'malware'): SecurityScan
    {
        $scan = SecurityScan::create([
            'type' => $type,
            'status' => 'running',
            'target_path' => $targetPath,
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        $output = [];
        $exitCode = 0;

        try {
            $script = $type === 'audit'
                ? config('services.security.audit_script', base_path('infrastructure/security-audit.sh'))
                : config('services.security.scan_script', base_path('infrastructure/security-scan.sh'));
            if (!$script || !file_exists($script)) {
                throw new \RuntimeException('Security scan script not found.');
            }

            $commandParts = [];
            if (config('services.security.use_sudo', true)) {
                $commandParts[] = 'sudo';
                $commandParts[] = '-n';
            }
            $commandParts[] = $script;
            $commandParts[] = $targetPath;

            $escaped = implode(' ', array_map('escapeshellarg', $commandParts));
            exec($escaped . ' 2>&1', $output, $exitCode);
        } catch (\Throwable $e) {
            $output[] = $e->getMessage();
            $exitCode = 1;
        }

        $scan->status = $exitCode === 0 ? 'completed' : 'failed';
        $scan->output = implode("\n", $output);
        $scan->finished_at = now();
        $scan->save();

        return $scan;
    }
}
