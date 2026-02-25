<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantQueueProfileService;
use Illuminate\Console\Command;

class TenantQueueAutoscale extends Command
{
    protected $signature = 'tenant:queue:autoscale {--tenant=}';

    protected $description = 'Compute desired worker counts per tenant based on queue depth.';

    public function handle(TenantQueueProfileService $service): int
    {
        $tenantId = $this->option('tenant');
        $tenants = $tenantId ? Tenant::where('id', $tenantId)->get() : Tenant::all();

        foreach ($tenants as $tenant) {
            $decision = $service->decide($tenant);
            $this->line(sprintf(
                '%s => desired %d workers (depth=%d, min=%d, max=%d)',
                $tenant->name,
                $decision['desired_workers'],
                $decision['queue_depth'],
                $decision['min'],
                $decision['max']
            ));
        }

        return Command::SUCCESS;
    }
}
