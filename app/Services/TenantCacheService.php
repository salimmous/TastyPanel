<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TenantCacheService
{
    /**
     * Cache TTL in seconds
     */
    private const TENANT_DATA_TTL = 1800; // 30 minutes

    private const ANALYTICS_TTL = 3600; // 1 hour

    private const STATS_TTL = 900; // 15 minutes

    /**
     * Get cached tenant data with relationships
     */
    public function getTenantData(Tenant $tenant): Tenant
    {
        return Cache::tags(['tenant', "tenant:{$tenant->id}"])
            ->remember(
                "tenant:{$tenant->id}:data",
                self::TENANT_DATA_TTL,
                fn () => $tenant->load(['domains', 'plan'])
            );
    }

    /**
     * Get cached tenant statistics
     */
    public function getTenantStats(Tenant $tenant): array
    {
        return Cache::tags(["tenant:{$tenant->id}", 'stats'])
            ->remember(
                "tenant:{$tenant->id}:stats",
                self::STATS_TTL,
                function () use ($tenant) {
                    $db = app(TenantDatabaseService::class)->connection($tenant);

                    return [
                        'recipes_count' => $db->table('recipes')->count(),
                        'articles_count' => $db->table('articles')->count(),
                        'categories_count' => $db->table('categories')->count(),
                        'published_recipes' => $db->table('recipes')->whereNotNull('published_at')->count(),
                    ];
                }
            );
    }

    /**
     * Get platform-wide analytics (cached)
     */
    public function getPlatformAnalytics(): array
    {
        return Cache::tags(['platform', 'analytics'])
            ->remember('platform:analytics', self::ANALYTICS_TTL, function () {
                return [
                    'total_tenants' => Tenant::count(),
                    'active_tenants' => Tenant::where('status', 'active')->count(),
                    'inactive_tenants' => Tenant::where('status', 'inactive')->count(),
                    'total_domains' => DB::table('domains')->count(),
                    'verified_domains' => DB::table('domains')->whereNotNull('ssl_verified_at')->count(),
                ];
            });
    }

    /**
     * Clear all caches for a specific tenant
     */
    public function clearTenantCache(Tenant $tenant): void
    {
        Cache::tags(["tenant:{$tenant->id}"])->flush();
    }

    /**
     * Clear tenant statistics cache
     */
    public function clearStatsCache(Tenant $tenant): void
    {
        Cache::tags(["tenant:{$tenant->id}", 'stats'])->flush();
    }

    /**
     * Clear platform analytics cache
     */
    public function clearPlatformAnalytics(): void
    {
        Cache::tags(['platform', 'analytics'])->flush();
    }

    /**
     * Warm up cache for tenant
     */
    public function warmupTenantCache(Tenant $tenant): void
    {
        $this->getTenantData($tenant);
        $this->getTenantStats($tenant);
    }

    /**
     * Clear all platform caches
     */
    public function clearAll(): void
    {
        Cache::tags(['platform'])->flush();
        Cache::tags(['tenant'])->flush();
        Cache::tags(['analytics'])->flush();
        Cache::tags(['stats'])->flush();
    }
}
