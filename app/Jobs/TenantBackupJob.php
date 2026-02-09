<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantBackupRun;
use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TenantBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public function __construct(
        public Tenant $tenant,
        public ?int $userId = null
    ) {}

    public function handle(BackupService $backupService): void
    {
        $run = TenantBackupRun::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'manual',
            'status' => 'running',
            'started_at' => now(),
            'created_by' => $this->userId,
        ]);

        try {
            $zipPath = $backupService->backupTenant($this->tenant);
            
            $run->update([
                'status' => 'success',
                'path' => $zipPath,
                'size_bytes' => file_exists($zipPath) ? filesize($zipPath) : 0,
                'finished_at' => now(),
            ]);
            
            Log::info("Tenant backup job success: {$this->tenant->id}");
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now(),
            ]);
            Log::error("Tenant backup job failed: " . $e->getMessage());
        }
    }
}
