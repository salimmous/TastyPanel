<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Models\ProvisioningJob;
use App\Services\ProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessTenantProvisioningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1800;

    public function __construct(
        public int $tenantId,
        public int $domainId,
        public int $provisioningJobId
    ) {
    }

    public function handle(ProvisioningService $provisioning): void
    {
        $job = ProvisioningJob::query()->find($this->provisioningJobId);
        if (!$job) {
            return;
        }

        $domain = Domain::query()->with('tenant')->find($this->domainId);
        if (!$domain || $domain->tenant_id !== $this->tenantId) {
            $this->markFailed($job, 'Provisioning target domain not found.');
            return;
        }

        $this->markRunning($job, 'Provisioning started.', ['step' => 'queued']);

        try {
            $result = $provisioning->provisionDomainWithState(
                $domain,
                null,
                function (string $message, array $meta = []) use ($job): void {
                    $this->markRunning($job, $message, $meta);
                }
            );
            $domain = $result['domain'];

            if (($result['lock_contended'] ?? false) === true) {
                $this->markDone($job, 'Provisioning skipped (another job is running).', [
                    'step' => 'skipped_lock',
                    'domain_status' => $domain->status,
                    'lock_contended' => true,
                    'completed_steps' => $result['completed_steps'] ?? [],
                ]);
                return;
            }

            if (($result['idempotent'] ?? false) === true) {
                $this->markDone($job, 'Provisioning skipped (already satisfied).', [
                    'step' => 'idempotent',
                    'domain_status' => $domain->status,
                    'idempotent' => true,
                    'completed_steps' => $result['completed_steps'] ?? [],
                ]);
                return;
            }

            if (!($result['success'] ?? false)) {
                $message = $domain->last_error ?: 'Provisioning failed.';
                $rollback = $result['rollback'] ?? ['performed' => false, 'success' => null];
                if (($rollback['performed'] ?? false) && ($rollback['success'] ?? false)) {
                    $this->markRolledBack($job, $message, [
                        'step' => 'rolled_back',
                        'domain_status' => $domain->status,
                        'failed_step' => $result['failed_step'] ?? null,
                        'completed_steps' => $result['completed_steps'] ?? [],
                        'rollback' => $rollback,
                    ]);
                    return;
                }

                $this->markFailed($job, $message, [
                    'step' => 'failed',
                    'domain_status' => $domain->status,
                    'failed_step' => $result['failed_step'] ?? null,
                    'completed_steps' => $result['completed_steps'] ?? [],
                    'rollback' => $rollback,
                ]);
                return;
            }

            $this->markDone($job, 'Provisioning completed.', [
                'step' => 'done',
                'domain_status' => $domain->status,
                'blocked' => $result['blocked'] ?? false,
                'completed_steps' => $result['completed_steps'] ?? [],
            ]);
        } catch (Throwable $e) {
            $this->markFailed($job, $e->getMessage(), ['step' => 'exception']);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $job = ProvisioningJob::query()->find($this->provisioningJobId);
        if (!$job) {
            return;
        }

        $this->markFailed($job, $e->getMessage(), ['step' => 'failed']);
    }

    private function markRunning(ProvisioningJob $job, string $message, array $meta = []): void
    {
        $mergedMeta = $this->mergeMeta($job, 'running', $message, $meta);
        $job->status = 'running';
        $job->message = $message;
        $job->meta = $mergedMeta;
        $job->started_at = $job->started_at ?: now();
        $job->save();
    }

    private function markDone(ProvisioningJob $job, string $message, array $meta = []): void
    {
        $mergedMeta = $this->mergeMeta($job, 'done', $message, $meta);
        $job->status = 'done';
        $job->message = $message;
        $job->meta = $mergedMeta;
        $job->finished_at = now();
        $job->save();
    }

    private function markRolledBack(ProvisioningJob $job, string $message, array $meta = []): void
    {
        $mergedMeta = $this->mergeMeta($job, 'rolled_back', $message, $meta);
        $job->status = 'rolled_back';
        $job->message = $message;
        $job->meta = $mergedMeta;
        $job->finished_at = now();
        $job->save();
    }

    private function markFailed(ProvisioningJob $job, string $message, array $meta = []): void
    {
        $mergedMeta = $this->mergeMeta($job, 'failed', $message, $meta);
        $job->status = 'failed';
        $job->message = $message;
        $job->meta = $mergedMeta;
        $job->finished_at = now();
        $job->save();
    }

    private function mergeMeta(ProvisioningJob $job, string $state, string $message, array $meta = []): array
    {
        $current = $job->meta ?? [];
        $events = $current['events'] ?? [];
        $events[] = [
            'at' => now()->toIso8601String(),
            'state' => $state,
            'message' => $message,
            'meta' => $meta,
        ];
        if (count($events) > 100) {
            $events = array_slice($events, -100);
        }

        $merged = array_merge($current, $meta);
        $merged['events'] = $events;

        return $merged;
    }
}
