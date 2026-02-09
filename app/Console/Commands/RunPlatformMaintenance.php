<?php

namespace App\Console\Commands;

use App\Services\PlatformAnalyticsService;
use App\Services\PlatformAutomationService;
use Illuminate\Console\Command;

class RunPlatformMaintenance extends Command
{
    protected $signature = 'platform:maintenance {--analytics : Collect analytics only} {--automation : Run automation only}';

    protected $description = 'Run platform maintenance tasks: collect analytics and execute automation rules';

    public function handle(
        PlatformAnalyticsService $analytics,
        PlatformAutomationService $automation
    ): int {
        $analyticsOnly = $this->option('analytics');
        $automationOnly = $this->option('automation');

        $runBoth = !$analyticsOnly && !$automationOnly;

        // Collect Analytics
        if ($runBoth || $analyticsOnly) {
            $this->info('Collecting platform analytics...');
            $metric = $analytics->collectDailyMetrics();
            $this->info("Metrics collected for {$metric->date}:");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Tenants', $metric->total_tenants],
                    ['Active Tenants', $metric->active_tenants],
                    ['New Tenants', $metric->new_tenants],
                    ['Total Recipes', $metric->total_recipes],
                    ['Total Articles', $metric->total_articles],
                    ['Total Requests', number_format($metric->total_requests)],
                ]
            );
        }

        // Run Automation Rules
        if ($runBoth || $automationOnly) {
            $this->info('Running scheduled automation rules...');
            $results = $automation->runScheduledRules();

            if (empty($results)) {
                $this->info('No automation rules due to run.');
            } else {
                foreach ($results as $result) {
                    $status = $result['status'] === 'success' ? '✓' : '✗';
                    $this->line("{$status} {$result['rule']}: {$result['status']}");
                }
                $this->info('Automation complete: ' . count($results) . ' rules executed.');
            }
        }

        return self::SUCCESS;
    }
}
