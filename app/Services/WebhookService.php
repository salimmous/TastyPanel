<?php

namespace App\Services;

use App\Jobs\DispatchWebhookJob;
use App\Models\Tenant;
use App\Models\Webhook;

class WebhookService
{
    public function dispatchEvent(Tenant $tenant, string $event, array $payload): void
    {
        $webhooks = Webhook::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            if (!$this->matchesEvent($webhook->event, $event)) {
                continue;
            }

            DispatchWebhookJob::dispatch($webhook->id, $event, $payload);
        }
    }

    private function matchesEvent(string $rule, string $event): bool
    {
        $rule = trim($rule);
        if ($rule === '*') {
            return true;
        }

        if (str_ends_with($rule, '.*')) {
            $prefix = substr($rule, 0, -2);
            return str_starts_with($event, $prefix . '.');
        }

        return $rule === $event;
    }
}
