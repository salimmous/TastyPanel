<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\DisasterRecoveryDrillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunDisasterRecoveryDrillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $scope = 'platform',
        public ?int $tenantId = null,
        public ?int $userId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DisasterRecoveryDrillService $drillService): void
    {
        if ($this->scope === 'platform') {
            $drillService->runPlatformDrill($this->userId);
        } elseif ($this->scope === 'tenant' && $this->tenantId) {
            $tenant = Tenant::find($this->tenantId);
            if ($tenant) {
                $drillService->runTenantDrill($tenant, $this->userId);
            }
        }
    }
}
