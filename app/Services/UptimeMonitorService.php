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
            $times = [];
            $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($checks, &$times) {
                return $checks->map(function ($check) use ($pool, &$times) {
                    return $pool->as($check->id)->timeout(10)->withOptions([
                        'on_stats' => function (\GuzzleHttp\TransferStats $stats) use (&$times, $check) {
                            $times[$check->id] = (int) round($stats->getTransferTime() * 1000);
                        }
                    ])->get($check->url);
                });
            });

            foreach ($checks as $check) {
                $summary['checks']++;
                $response = $responses[$check->id] ?? null;
                $elapsedMs = $times[$check->id] ?? null;

                $result = $this->recordCheckResult($check, $response, $elapsedMs);
                if (!$result['success']) {
                    $summary['failures']++;
                }
            }
        });

        return $summary;
    }

    public function check(UptimeCheck $check): array
    {
        $responseMs = null;
        $response = null;

        $start = microtime(true);
        try {
            $response = Http::timeout(10)->get($check->url);
            $responseMs = (int) round((microtime(true) - $start) * 1000);
        } catch (\Throwable $e) {
            $response = $e;
            $responseMs = (int) round((microtime(true) - $start) * 1000);
        }

        return $this->recordCheckResult($check, $response, $responseMs);
    }

    /**
     * Record the result of an uptime check.
     *
     * @param UptimeCheck $check
     * @param mixed $response \Illuminate\Http\Client\Response or \Throwable
     * @param int|null $elapsedMs
     * @return array
     */
    private function recordCheckResult(UptimeCheck $check, $response, ?int $elapsedMs): array
    {
        $status = null;
        $error = null;
        $responseMs = $elapsedMs;
        $success = false;

        if ($response instanceof \Illuminate\Http\Client\Response) {
            $status = $response->status();
            $success = $status === (int) $check->expected_status;
            if (!$success) {
                $error = 'Unexpected status: ' . $status;
            }
        } elseif ($response instanceof \Throwable) {
            $error = $response->getMessage();
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
