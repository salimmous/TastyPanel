<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetricsCollector
{
    /**
     * Collect platform-wide metrics
     */
    public function collectPlatformMetrics(): array
    {
        return Cache::remember('metrics:platform', 300, function () {
            return [
                'tenants' => [
                    'total' => Tenant::count(),
                    'active' => Tenant::where('status', 'active')->count(),
                    'inactive' => Tenant::where('status', 'inactive')->count(),
                ],
                'system' => [
                    'disk_free' => disk_free_space('/'),
                    'disk_total' => disk_total_space('/'),
                    'memory_usage' => memory_get_usage(true),
                    'cpu_load' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0,
                ],
                'performance' => [
                    'avg_response_time' => $this->getAverageResponseTime(),
                    'cache_hit_rate' => $this->getCacheHitRate(),
                ],
                'database' => [
                    'size' => $this->getDatabaseSize(),
                    'connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0,
                ],
            ];
        });
    }

    /**
     * Collect metrics for specific tenant
     */
    public function collectTenantMetrics(Tenant $tenant): array
    {
        return Cache::remember("metrics:tenant:{$tenant->id}", 300, function () use ($tenant) {
            $db = app(TenantDatabaseService::class)->connection($tenant);

            return [
                'content' => [
                    'recipes' => $db->table('recipes')->count(),
                    'articles' => $db->table('articles')->count(),
                    'categories' => $db->table('categories')->count(),
                ],
                'activity' => [
                    'published_recipes' => $db->table('recipes')->whereNotNull('published_at')->count(),
                    'published_articles' => $db->table('articles')->whereNotNull('published_at')->count(),
                ],
                'performance' => [
                    'database_size' => $this->getTenantDatabaseSize($tenant),
                    'files_size' => $this->getTenantFilesSize($tenant),
                ],
                'health' => [
                    'status' => $tenant->status,
                    'last_activity' => $tenant->updated_at->diffForHumans(),
                ],
            ];
        });
    }

    /**
     * Get health check status
     */
    public function healthCheck(): array
    {
        $checks = [];

        // Database check
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'healthy';
        } catch (\Exception $e) {
            $checks['database'] = 'unhealthy';
        }

        // Redis check
        try {
            Cache::store('redis')->put('health_check', 1, 10);
            $checks['redis'] = 'healthy';
        } catch (\Exception $e) {
            $checks['redis'] = 'unhealthy';
        }

        // Disk space check
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsagePercent = (1 - ($diskFree / $diskTotal)) * 100;
        $checks['disk'] = $diskUsagePercent < 90 ? 'healthy' : 'warning';

        $checks['overall'] = in_array('unhealthy', $checks) ? 'unhealthy' : 'healthy';

        return $checks;
    }

    private function getAverageResponseTime(): float
    {
        // Placeholder - implement actual tracking
        return 0.0;
    }

    private function getCacheHitRate(): float
    {
        // Placeholder - implement actual tracking
        return 0.0;
    }

    private function getDatabaseSize(): int
    {
        try {
            $db = config('database.connections.mysql.database');
            $result = DB::select("
                SELECT SUM(data_length + index_length) as size
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ", [$db]);

            return $result[0]->size ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTenantDatabaseSize(Tenant $tenant): int
    {
        try {
            $result = DB::select("
                SELECT SUM(data_length + index_length) as size
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ", [$tenant->instance_db_name]);

            return $result[0]->size ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTenantFilesSize(Tenant $tenant): int
    {
        $path = storage_path("app/tenant-files/{$tenant->id}");

        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
