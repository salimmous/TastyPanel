<?php

namespace App\Services;

class PlatformStatusService
{
    public function summary(): array
    {
        return [
            'load' => $this->loadAverage(),
            'memory' => $this->memoryUsage(),
            'disk' => $this->diskUsage(),
            'services' => $this->serviceStatus(),
        ];
    }

    private function loadAverage(): array
    {
        $load = sys_getloadavg();

        return [
            '1m' => $load[0] ?? null,
            '5m' => $load[1] ?? null,
            '15m' => $load[2] ?? null,
        ];
    }

    private function memoryUsage(): array
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
            ];
        }

        $usage = memory_get_usage(true);

        return [
            'total_mb' => null,
            'used_mb' => round($usage / 1024 / 1024, 2),
            'free_mb' => null,
        ];
    }

    private function readMeminfo(): ?array
    {
        $path = '/proc/meminfo';
        if (! is_readable($path)) {
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

    private function diskUsage(): array
    {
        $root = '/';
        $total = @disk_total_space($root);
        $free = @disk_free_space($root);
        if ($total === false || $free === false) {
            return [
                'total_gb' => null,
                'used_gb' => null,
                'free_gb' => null,
            ];
        }

        $used = max($total - $free, 0);

        return [
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_gb' => round($used / 1024 / 1024 / 1024, 2),
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
        ];
    }

    private function serviceStatus(): array
    {
        return [
            'nginx' => $this->checkService('nginx'),
            'mysql' => $this->checkService('mysql'),
            'php_fpm' => $this->checkPhpFpm(),
        ];
    }

    private function checkService(string $name): string
    {
        $output = [];
        $exit = 0;
        @exec(sprintf('systemctl is-active %s 2>&1', escapeshellarg($name)), $output, $exit);
        if ($exit === 0) {
            return trim($output[0] ?? 'active');
        }

        return 'unknown';
    }

    private function checkPhpFpm(): string
    {
        $socket = config('services.infrastructure.php_fpm_socket');
        if ($socket && file_exists($socket)) {
            return 'active';
        }

        return $this->checkService('php-fpm');
    }
}
