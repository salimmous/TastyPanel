<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TenantQuotaService
{
    public function evaluateAndTrack(Tenant $tenant): array
    {
        $limits = $this->limitsFor($tenant);

        $requests = $this->checkAndTrackMonthlyRequests($tenant, $limits['max_monthly_requests']);
        if (! ($requests['allowed'] ?? true)) {
            return [
                'allowed' => false,
                'status' => 429,
                'reason' => 'monthly_requests_exceeded',
                'limits' => $limits,
                'usage' => $this->usageSnapshot($tenant, $limits, $requests),
            ];
        }

        $storage = $this->checkStorageLimit($tenant, $limits['max_storage_mb']);
        if (! ($storage['allowed'] ?? true)) {
            return [
                'allowed' => false,
                'status' => 507,
                'reason' => 'storage_limit_exceeded',
                'limits' => $limits,
                'usage' => $this->usageSnapshot($tenant, $limits, $requests, $storage),
            ];
        }

        $database = $this->checkDatabaseLimit($tenant, $limits['max_db_size_mb']);
        if (! ($database['allowed'] ?? true)) {
            return [
                'allowed' => false,
                'status' => 507,
                'reason' => 'database_limit_exceeded',
                'limits' => $limits,
                'usage' => $this->usageSnapshot($tenant, $limits, $requests, $storage, $database),
            ];
        }

        $runtime = $this->checkRuntimeResourceLimits(
            $tenant,
            $limits['max_cpu_percent'],
            $limits['max_memory_mb'],
            $limits['max_worker_processes']
        );
        if (! ($runtime['allowed'] ?? true)) {
            return [
                'allowed' => false,
                'status' => (int) ($runtime['status'] ?? 429),
                'reason' => (string) ($runtime['reason'] ?? 'runtime_limit_exceeded'),
                'limits' => $limits,
                'usage' => $this->usageSnapshot($tenant, $limits, $requests, $storage, $database, $runtime),
            ];
        }

        return [
            'allowed' => true,
            'status' => 200,
            'reason' => null,
            'limits' => $limits,
            'usage' => $this->usageSnapshot($tenant, $limits, $requests, $storage, $database, $runtime),
        ];
    }

    public function limitsFor(Tenant $tenant): array
    {
        $profile = $tenant->securityProfile;

        $planMonthly = $this->planLimit($tenant, ['max_monthly_requests', 'monthly_requests']);
        $planStorageMb = $this->planStorageLimitMb($tenant);
        $planDbMb = $this->planLimit($tenant, ['max_db_size_mb', 'db_size_mb']);
        $defaultWorkers = (int) config('services.instances.fpm_max_children', 0);
        $defaultMemoryMb = (int) config('services.instances.fpm_memory_limit_mb', 0);
        $threshold = (int) ($profile?->quota_alert_threshold_percent ?? 80);
        $threshold = max(50, min(99, $threshold));

        return [
            'max_monthly_requests' => $this->resolveNullablePositiveInt($profile?->max_monthly_requests, $planMonthly),
            'max_storage_mb' => $this->resolveNullablePositiveInt($profile?->max_storage_mb, $planStorageMb),
            'max_db_size_mb' => $this->resolveNullablePositiveInt($profile?->max_db_size_mb, $planDbMb),
            'max_cpu_percent' => $this->resolveNullablePositiveInt($profile?->max_cpu_percent, null),
            'max_memory_mb' => $this->resolveNullablePositiveInt($profile?->max_memory_mb, $defaultMemoryMb),
            'max_worker_processes' => $this->resolveNullablePositiveInt($profile?->max_worker_processes, $defaultWorkers),
            'alert_threshold_percent' => $threshold,
        ];
    }

    public function usageSnapshot(
        Tenant $tenant,
        ?array $limits = null,
        ?array $requests = null,
        ?array $storage = null,
        ?array $database = null,
        ?array $runtime = null
    ): array {
        $limits = $limits ?: $this->limitsFor($tenant);
        $requests = $requests ?: $this->checkAndTrackMonthlyRequests($tenant, null, false);
        $storage = $storage ?: $this->checkStorageLimit($tenant, null);
        $database = $database ?: $this->checkDatabaseLimit($tenant, null);
        $runtime = $runtime ?: $this->checkRuntimeResourceLimits($tenant, null, null, null);

        $payload = [
            'requests' => [
                'used' => (int) ($requests['used'] ?? 0),
                'limit' => $limits['max_monthly_requests'],
                'window' => (string) ($requests['window'] ?? now()->format('Y-m')),
            ],
            'storage_mb' => [
                'used' => (int) ($storage['used_mb'] ?? 0),
                'limit' => $limits['max_storage_mb'],
            ],
            'database_mb' => [
                'used' => (int) ($database['used_mb'] ?? 0),
                'limit' => $limits['max_db_size_mb'],
            ],
            'cpu_percent' => [
                'used' => $runtime['usage']['cpu_percent'] ?? null,
                'limit' => $limits['max_cpu_percent'],
            ],
            'memory_mb' => [
                'used' => $runtime['usage']['memory_mb'] ?? null,
                'limit' => $limits['max_memory_mb'],
            ],
            'workers' => [
                'used' => $runtime['usage']['workers'] ?? null,
                'limit' => $limits['max_worker_processes'],
            ],
            'runtime_source' => $runtime['usage']['source'] ?? 'unavailable',
            'alert_threshold_percent' => (int) ($limits['alert_threshold_percent'] ?? 80),
        ];

        $alerts = $this->evaluateAlerts($payload, (int) ($limits['alert_threshold_percent'] ?? 80));
        $payload['alerts'] = $alerts;
        $payload['status'] = $this->alertsStatus($alerts);

        return $payload;
    }

    private function evaluateAlerts(array $usage, int $thresholdPercent): array
    {
        $thresholdPercent = max(50, min(99, $thresholdPercent));
        $checks = [
            'requests' => $usage['requests'] ?? [],
            'storage_mb' => $usage['storage_mb'] ?? [],
            'database_mb' => $usage['database_mb'] ?? [],
            'cpu_percent' => $usage['cpu_percent'] ?? [],
            'memory_mb' => $usage['memory_mb'] ?? [],
            'workers' => $usage['workers'] ?? [],
        ];

        $alerts = [];
        foreach ($checks as $key => $check) {
            $limit = $check['limit'] ?? null;
            $used = $check['used'] ?? null;
            if (! is_numeric($limit) || (int) $limit <= 0 || ! is_numeric($used)) {
                continue;
            }

            $limitVal = (float) $limit;
            $usedVal = (float) $used;
            $percent = round(($usedVal / $limitVal) * 100, 2);
            if ($percent < $thresholdPercent) {
                continue;
            }

            $alerts[] = [
                'key' => $key,
                'used' => $usedVal,
                'limit' => $limitVal,
                'percent' => $percent,
                'severity' => $percent >= 100 ? 'critical' : 'warning',
            ];
        }

        return $alerts;
    }

    private function alertsStatus(array $alerts): string
    {
        foreach ($alerts as $alert) {
            if (($alert['severity'] ?? '') === 'critical') {
                return 'critical';
            }
        }

        if ($alerts !== []) {
            return 'warning';
        }

        return 'ok';
    }

    private function checkRuntimeResourceLimits(
        Tenant $tenant,
        ?int $cpuLimit,
        ?int $memoryLimitMb,
        ?int $workersLimit
    ): array {
        $usage = $this->runtimeUsageSnapshot($tenant);

        $checks = [
            'cpu_limit_exceeded' => [
                'limit' => $cpuLimit,
                'used' => $usage['cpu_percent'],
            ],
            'memory_limit_exceeded' => [
                'limit' => $memoryLimitMb,
                'used' => $usage['memory_mb'],
            ],
            'worker_limit_exceeded' => [
                'limit' => $workersLimit,
                'used' => $usage['workers'],
            ],
        ];

        foreach ($checks as $reason => $check) {
            $limit = $check['limit'];
            $used = $check['used'];
            if (! is_numeric($limit) || (int) $limit <= 0 || ! is_numeric($used)) {
                continue;
            }
            if ((float) $used > (float) $limit) {
                return [
                    'allowed' => false,
                    'status' => 429,
                    'reason' => $reason,
                    'usage' => $usage,
                ];
            }
        }

        return [
            'allowed' => true,
            'status' => 200,
            'reason' => null,
            'usage' => $usage,
        ];
    }

    private function runtimeUsageSnapshot(Tenant $tenant): array
    {
        $systemUser = (string) ($tenant->instance_system_user ?: '');
        if ($systemUser === '') {
            return [
                'cpu_percent' => null,
                'memory_mb' => null,
                'workers' => null,
                'source' => 'unavailable',
            ];
        }

        $cacheKey = sprintf('tenant_quota:runtime:%d', $tenant->id);

        return Cache::remember($cacheKey, 60, function () use ($systemUser): array {
            $output = [];
            $exitCode = 0;
            $command = 'ps -u '.escapeshellarg($systemUser).' -o pcpu=,rss=';
            exec($command.' 2>/dev/null', $output, $exitCode);
            if ($exitCode !== 0 || $output === []) {
                return [
                    'cpu_percent' => 0.0,
                    'memory_mb' => 0,
                    'workers' => 0,
                    'source' => 'ps',
                ];
            }

            $workers = 0;
            $cpu = 0.0;
            $rssKb = 0;
            foreach ($output as $line) {
                $line = trim((string) $line);
                if ($line === '') {
                    continue;
                }

                $parts = preg_split('/\s+/', $line);
                if (! is_array($parts) || count($parts) < 2) {
                    continue;
                }

                $workers++;
                $cpu += is_numeric($parts[0]) ? (float) $parts[0] : 0.0;
                $rssKb += is_numeric($parts[1]) ? (int) $parts[1] : 0;
            }

            return [
                'cpu_percent' => round($cpu, 2),
                'memory_mb' => (int) ceil($rssKb / 1024),
                'workers' => $workers,
                'source' => 'ps',
            ];
        });
    }

    private function checkAndTrackMonthlyRequests(Tenant $tenant, ?int $limit, bool $track = true): array
    {
        $bucket = now()->format('Ym');
        $key = sprintf('tenant_quota:requests:%d:%s', $tenant->id, $bucket);
        $expiresAt = now()->endOfMonth()->addDays(2);

        if (! Cache::has($key)) {
            Cache::put($key, 0, $expiresAt);
        }

        if ($track) {
            Cache::increment($key);
        }

        $used = (int) Cache::get($key, 0);
        if (! $limit || $limit <= 0) {
            return [
                'allowed' => true,
                'used' => $used,
                'limit' => null,
                'window' => now()->format('Y-m'),
            ];
        }

        return [
            'allowed' => $used <= $limit,
            'used' => $used,
            'limit' => $limit,
            'window' => now()->format('Y-m'),
        ];
    }

    private function checkStorageLimit(Tenant $tenant, ?int $limitMb): array
    {
        $cacheKey = sprintf('tenant_quota:storage_mb:%d', $tenant->id);
        $usedMb = (int) Cache::remember($cacheKey, 300, function () use ($tenant): int {
            $usage = app(TenantStorageService::class)->usage($tenant);
            $bytes = (int) ($usage['bytes'] ?? 0);

            return (int) ceil($bytes / 1024 / 1024);
        });

        if (! $limitMb || $limitMb <= 0) {
            return [
                'allowed' => true,
                'used_mb' => $usedMb,
                'limit_mb' => null,
            ];
        }

        return [
            'allowed' => $usedMb <= $limitMb,
            'used_mb' => $usedMb,
            'limit_mb' => $limitMb,
        ];
    }

    private function checkDatabaseLimit(Tenant $tenant, ?int $limitMb): array
    {
        $usedMb = 0;
        if (! empty($tenant->instance_db_name)) {
            try {
                $result = DB::select(
                    'SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ?',
                    [$tenant->instance_db_name]
                );
                $bytes = (int) ($result[0]->size ?? 0);
                $usedMb = (int) ceil($bytes / 1024 / 1024);
            } catch (\Throwable $e) {
                $usedMb = 0;
            }
        }

        if (! $limitMb || $limitMb <= 0) {
            return [
                'allowed' => true,
                'used_mb' => $usedMb,
                'limit_mb' => null,
            ];
        }

        return [
            'allowed' => $usedMb <= $limitMb,
            'used_mb' => $usedMb,
            'limit_mb' => $limitMb,
        ];
    }

    private function planStorageLimitMb(Tenant $tenant): ?int
    {
        $bytes = app(TenantLimitService::class)->storageLimitBytes($tenant);
        if ($bytes === null || $bytes <= 0) {
            return null;
        }

        return (int) floor($bytes / 1024 / 1024);
    }

    private function planLimit(Tenant $tenant, array $keys): ?int
    {
        $service = app(TenantLimitService::class);
        foreach ($keys as $key) {
            $value = $service->limitValue($tenant, $key, 0);
            if ($value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function resolveNullablePositiveInt(mixed $primary, mixed $fallback): ?int
    {
        if (is_numeric($primary) && (int) $primary > 0) {
            return (int) $primary;
        }
        if (is_numeric($fallback) && (int) $fallback > 0) {
            return (int) $fallback;
        }

        return null;
    }
}
