<?php

namespace App\Services;

use App\Models\Tenant;

class AnalyticsService
{
    /**
     * Get tracking script for tenant
     */
    public function getTrackingScript(Tenant $tenant): ?string
    {
        if (!$tenant->analytics_enabled) {
            return null;
        }

        return match ($tenant->analytics_provider) {
            'google' => $this->getGoogleAnalyticsScript($tenant->analytics_id),
            'plausible' => $this->getPlausibleScript($tenant->analytics_id),
            'custom' => $tenant->analytics_config['custom_script'] ?? null,
            default => null,
        };
    }

    /**
     * Get Google Analytics script
     */
    private function getGoogleAnalyticsScript(string $measurementId): string
    {
        return <<<HTML
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={$measurementId}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{$measurementId}', {
                'anonymize_ip': true,
                'cookie_flags': 'SameSite=None;Secure'
            });
        </script>
        HTML;
    }

    /**
     * Get Plausible Analytics script
     */
    private function getPlausibleScript(string $domain): string
    {
        return <<<HTML
        <script defer data-domain="{$domain}" src="https://plausible.io/js/script.js"></script>
        HTML;
    }

    /**
     * Track custom event
     */
    public function trackEvent(string $eventName, array $properties = []): void
    {
        // This would integrate with your analytics provider
        // For now, just log it
        \Log::info("Analytics event: {$eventName}", $properties);
    }

    public function getPlatformOverview(): array
    {
        // In a real app, this would query a dedicated analytics table or time-series DB (e.g. InfluxDB/Prometheus)
        // For this demo/first version, we will aggregate from existing data + some cache estimates.
        
        $totalTenants = Tenant::count();
        $totalUsers = \App\Models\User::count();
        $failedJobs = \DB::table('failed_jobs')->count();
        $totalJobs = \DB::table('jobs')->count();
        
        $errorRate = $totalJobs > 0 ? ($failedJobs / ($totalJobs + $failedJobs)) * 100 : 0;
        
        return [
            'total_requests' => 'N/A', // valid requests tracking requires middleware
            'avg_response_time' => 'N/A',
            'error_rate' => round($errorRate, 2),
            'unique_visitors' => $totalUsers, // approximation
            'top_tenants' => Tenant::withCount('users')->orderByDesc('users_count')->take(5)->get(),
        ];
    }
}
