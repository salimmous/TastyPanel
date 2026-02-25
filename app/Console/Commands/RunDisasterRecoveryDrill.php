<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\DisasterRecoveryDrillService;
use Illuminate\Console\Command;

class RunDisasterRecoveryDrill extends Command
{
    protected $signature = 'drill:run {--platform-only : Run only platform backup drill} {--tenant=* : Specific tenant IDs} {--all-tenants : Run all active tenant drills}';

    protected $description = 'Run disaster recovery drills and produce RPO/RTO snapshots.';

    public function handle(DisasterRecoveryDrillService $service): int
    {
        $hasFailure = false;

        $this->info('Running platform disaster recovery drill...');
        $platform = $service->runPlatformDrill();
        $this->line(sprintf(
            'Platform drill: %s | %s',
            strtoupper($platform->status),
            $platform->message ?: 'n/a'
        ));
        if ($platform->status !== 'passed') {
            $hasFailure = true;
        }

        if ($this->option('platform-only')) {
            return $hasFailure ? self::FAILURE : self::SUCCESS;
        }

        $tenantIds = array_filter(array_map('intval', (array) $this->option('tenant')));
        if ($tenantIds) {
            foreach ($tenantIds as $tenantId) {
                $tenant = Tenant::find($tenantId);
                if (! $tenant) {
                    $this->warn("Tenant {$tenantId} not found.");
                    $hasFailure = true;

                    continue;
                }
                $drill = $service->runTenantDrill($tenant);
                $this->line(sprintf(
                    'Tenant %d (%s): %s | %s',
                    $tenant->id,
                    $tenant->name,
                    strtoupper($drill->status),
                    $drill->message ?: 'n/a'
                ));
                if ($drill->status !== 'passed') {
                    $hasFailure = true;
                }
            }

            return $hasFailure ? self::FAILURE : self::SUCCESS;
        }

        if ($this->option('all-tenants')) {
            $this->info('Running tenant drills for all active tenants...');
            $drills = $service->runAllTenantDrills();
            foreach ($drills as $drill) {
                $this->line(sprintf(
                    'Tenant %d: %s | %s',
                    (int) $drill->tenant_id,
                    strtoupper($drill->status),
                    $drill->message ?: 'n/a'
                ));
                if ($drill->status !== 'passed') {
                    $hasFailure = true;
                }
            }

            return $hasFailure ? self::FAILURE : self::SUCCESS;
        }

        $tenant = Tenant::query()->where('status', 'active')->latest('id')->first();
        if (! $tenant) {
            $this->warn('No active tenant found for sample tenant drill.');

            return $hasFailure ? self::FAILURE : self::SUCCESS;
        }

        $drill = $service->runTenantDrill($tenant);
        $this->line(sprintf(
            'Sample tenant %d (%s): %s | %s',
            $tenant->id,
            $tenant->name,
            strtoupper($drill->status),
            $drill->message ?: 'n/a'
        ));
        if ($drill->status !== 'passed') {
            $hasFailure = true;
        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }
}
