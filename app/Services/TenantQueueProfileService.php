<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantQueueProfile;
use Illuminate\Support\Facades\Queue;

class TenantQueueProfileService
{
    public function profileFor(Tenant $tenant): TenantQueueProfile
    {
        return $tenant->queueProfile ?: new TenantQueueProfile([
            'tenant_id' => $tenant->id,
            'min_workers' => 1,
            'max_workers' => 4,
            'scale_up_threshold' => 100,
            'scale_down_threshold' => 20,
        ]);
    }

    public function decide(Tenant $tenant): array
    {
        $profile = $this->profileFor($tenant);

        $queue = $profile->default_queue ?: 'default';
        $depth = $this->queueDepth($queue);

        $desired = $profile->min_workers;
        if ($depth >= $profile->scale_up_threshold) {
            $desired = $profile->max_workers;
        } elseif ($depth <= $profile->scale_down_threshold) {
            $desired = $profile->min_workers;
        } else {
            // scale proportionally
            $range = max(1, $profile->scale_up_threshold - $profile->scale_down_threshold);
            $ratio = ($depth - $profile->scale_down_threshold) / $range;
            $desired = (int) round($profile->min_workers + $ratio * ($profile->max_workers - $profile->min_workers));
        }

        $desired = max($profile->min_workers, min($desired, $profile->max_workers));

        return [
            'desired_workers' => $desired,
            'queue_depth' => $depth,
            'queue' => $queue,
            'min' => $profile->min_workers,
            'max' => $profile->max_workers,
        ];
    }

    private function queueDepth(string $queue): int
    {
        try {
            return Queue::size($queue);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
