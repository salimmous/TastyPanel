<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantMailGuardService
{
    public function checkAndTrack(Tenant $tenant, int $count = 1): array
    {
        $count = max(1, $count);

        $dailyLimit = (int) ($tenant->mail_daily_limit ?: config('services.mail.default_daily_limit', 500));
        $minuteLimit = (int) ($tenant->mail_per_minute_limit ?: config('services.mail.default_per_minute_limit', 30));

        $dayKey = sprintf('tenant_mail:day:%d:%s', $tenant->id, now()->format('Ymd'));
        $minuteKey = sprintf('tenant_mail:minute:%d:%s', $tenant->id, now()->format('YmdHi'));

        $dayTtl = now()->endOfDay()->addMinutes(10);
        $minuteTtl = now()->addMinutes(2);

        if (! Cache::has($dayKey)) {
            Cache::put($dayKey, 0, $dayTtl);
        }
        if (! Cache::has($minuteKey)) {
            Cache::put($minuteKey, 0, $minuteTtl);
        }

        $dayUsed = (int) Cache::get($dayKey, 0);
        $minuteUsed = (int) Cache::get($minuteKey, 0);

        if ($dailyLimit > 0 && ($dayUsed + $count) > $dailyLimit) {
            return [
                'allowed' => false,
                'status' => 429,
                'reason' => 'daily_mail_limit_exceeded',
                'usage' => [
                    'daily' => ['used' => $dayUsed, 'limit' => $dailyLimit],
                    'minute' => ['used' => $minuteUsed, 'limit' => $minuteLimit],
                ],
            ];
        }

        if ($minuteLimit > 0 && ($minuteUsed + $count) > $minuteLimit) {
            return [
                'allowed' => false,
                'status' => 429,
                'reason' => 'mail_rate_limit_exceeded',
                'usage' => [
                    'daily' => ['used' => $dayUsed, 'limit' => $dailyLimit],
                    'minute' => ['used' => $minuteUsed, 'limit' => $minuteLimit],
                ],
            ];
        }

        Cache::increment($dayKey, $count);
        Cache::increment($minuteKey, $count);

        return [
            'allowed' => true,
            'status' => 200,
            'reason' => null,
            'usage' => [
                'daily' => ['used' => $dayUsed + $count, 'limit' => $dailyLimit],
                'minute' => ['used' => $minuteUsed + $count, 'limit' => $minuteLimit],
            ],
        ];
    }
}
