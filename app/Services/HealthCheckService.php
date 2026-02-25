<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheckService
{
    /**
     * Perform overall health check
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $status = $this->getOverallStatus($checks);

        return [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /**
     * Check database connectivity
     */
    public function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'up',
                'response_time' => "{$responseTime}ms",
                'message' => 'Database is accessible',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    public function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'up',
                'response_time' => "{$responseTime}ms",
                'message' => 'Redis is accessible',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'message' => 'Redis connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check storage availability
     */
    public function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $totalSpace = disk_total_space(storage_path());
            $freeSpace = disk_free_space(storage_path());
            $usedSpace = $totalSpace - $freeSpace;
            $usedPercent = round(($usedSpace / $totalSpace) * 100, 2);

            $status = $usedPercent < 90 ? 'up' : 'degraded';

            return [
                'status' => $status,
                'free_space' => $this->formatBytes($freeSpace),
                'used_percent' => "{$usedPercent}%",
                'message' => $status === 'up' ? 'Storage is healthy' : 'Storage is running low',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'message' => 'Storage check failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check queue status
     */
    public function checkQueue(): array
    {
        try {
            // Check pending jobs count
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $status = $pendingJobs < 1000 ? 'up' : 'degraded';

            return [
                'status' => $status,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'message' => $status === 'up' ? 'Queue is healthy' : 'Queue backlog detected',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'message' => 'Queue check failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get overall status from individual checks
     */
    protected function getOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('down', $statuses)) {
            return 'down';
        }

        if (in_array('degraded', $statuses)) {
            return 'degraded';
        }

        return 'healthy';
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
