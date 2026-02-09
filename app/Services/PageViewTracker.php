<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageViewTracker
{
    /**
     * Track page view
     */
    public function track(Tenant $tenant, Request $request): void
    {
        try {
            DB::table('page_views')->insert([
                'tenant_id' => $tenant->id,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail - don't break the request
            \Log::error('Failed to track page view', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get statistics for tenant
     */
    public function getStats(Tenant $tenant, int $days = 30): array
    {
        $since = now()->subDays($days);

        $totalViews = DB::table('page_views')
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>', $since)
            ->count();

        $uniqueVisitors = DB::table('page_views')
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>', $since)
            ->distinct('ip')
            ->count('ip');

        $topPages = DB::table('page_views')
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>', $since)
            ->select('url', DB::raw('count(*) as views'))
            ->groupBy('url')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $dailyViews = DB::table('page_views')
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as views'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_views' => $totalViews,
            'unique_visitors' => $uniqueVisitors,
            'top_pages' => $topPages,
            'daily_views' => $dailyViews,
            'period_days' => $days,
        ];
    }

    /**
     * Clean old page views
     */
    public function cleanOldViews(int $keepDays = 90): int
    {
        return DB::table('page_views')
            ->where('created_at', '<', now()->subDays($keepDays))
            ->delete();
    }
}
