<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 15;

    public function __construct(
        public int $webhookId,
        public string $event,
        public array $payload
    ) {}

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);
        if (! $webhook || ! $webhook->is_active) {
            return;
        }

        $body = json_encode([
            'event' => $this->event,
            'tenant_id' => $webhook->tenant_id,
            'data' => $this->payload,
            'sent_at' => now()->toDateTimeString(),
        ], JSON_UNESCAPED_SLASHES);

        $signature = null;
        if ($webhook->secret) {
            $signature = 'sha256='.hash_hmac('sha256', $body, $webhook->secret);
        }

        $status = null;
        $responseBody = null;
        $error = null;
        $success = false;

        try {
            $response = Http::timeout(10)
                ->withHeaders(array_filter([
                    'X-TastyPanel-Event' => $this->event,
                    'X-TastyPanel-Signature' => $signature,
                ]))
                ->withBody($body, 'application/json')
                ->post($webhook->url);

            $status = $response->status();
            $responseBody = substr($response->body(), 0, 2000);
            $success = $status >= 200 && $status < 300;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event' => $this->event,
            'attempt' => $this->attempts() ?: 1,
            'status' => $status,
            'successful' => $success,
            'response' => $responseBody,
            'error' => $error,
            'sent_at' => now(),
        ]);

        $webhook->last_sent_at = now();
        $webhook->last_status = $status;
        $webhook->last_error = $error;
        $webhook->save();

        if (! $success && $this->shouldRetry($status, $error)) {
            throw new \RuntimeException('Webhook delivery failed');
        }
    }

    private function shouldRetry(?int $status, ?string $error): bool
    {
        if ($error) {
            return true;
        }
        if ($status === null) {
            return true;
        }

        return $status >= 500;
    }
}
