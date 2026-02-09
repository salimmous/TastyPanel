<?php

namespace App\Services;

use App\Models\UptimeCheck;
use App\Models\UptimeEvent;
use Illuminate\Support\Facades\Http;

class UptimeMonitorService
{
    public function run(): array
    {
        $summary = ['checks' => 0, 'failures' => 0];

        UptimeCheck::where('is_active', true)->chunkById(50, function ($checks) use (&$summary) {
            foreach ($checks as $check) {
                $summary['checks']++;
                $result = $this->check($check);
                if (!$result['success']) {
                    $summary['failures']++;
                }
            }
        });

        return $summary;
    }

    public function check(UptimeCheck $check): array
    {
        $status = null;
        $error = null;
        $responseMs = null;
        $success = false;

        $start = microtime(true);
        try {
            $response = Http::timeout(10)->get($check->url);
            $status = $response->status();
            $responseMs = (int) round((microtime(true) - $start) * 1000);
            $success = $status === (int) $check->expected_status;
            if (!$success) {
                $error = 'Unexpected status: ' . $status;
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $responseMs = (int) round((microtime(true) - $start) * 1000);
        }

        UptimeEvent::create([
            'uptime_check_id' => $check->id,
            'status' => $status,
            'response_ms' => $responseMs,
            'error' => $error,
            'checked_at' => now(),
        ]);

        $check->last_checked_at = now();
        $check->last_status = $status;
        $check->last_response_ms = $responseMs;
        $check->last_error = $error;
        $check->save();

        return [
            'success' => $success,
            'status' => $status,
            'error' => $error,
            'response_ms' => $responseMs,
        ];
    }
}
