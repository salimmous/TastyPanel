<?php

namespace App\Console\Commands;

use App\Models\FeatureFlag;
use Illuminate\Console\Command;

class TenantFeatureRollout extends Command
{
    protected $signature = 'feature:rollout {key} {--tenant=} {--percent=} {--enable} {--disable}';

    protected $description = 'Adjust feature flag rollout for tenant or global.';

    public function handle(): int
    {
        $key = $this->argument('key');
        $tenantId = $this->option('tenant');
        $percent = $this->option('percent');

        $flag = FeatureFlag::firstOrNew([
            'key' => $key,
            'tenant_id' => $tenantId ?: null,
            'environment' => null,
        ]);

        if ($percent !== null) {
            $flag->rollout_percentage = (int) $percent;
        }

        if ($this->option('enable')) {
            $flag->enabled = true;
        } elseif ($this->option('disable')) {
            $flag->enabled = false;
        }

        $flag->name = $flag->name ?: $key;
        $flag->save();

        $this->info(sprintf(
            'Flag %s (%s) enabled=%s rollout=%d%%',
            $key,
            $tenantId ? "tenant {$tenantId}" : 'global',
            $flag->enabled ? 'yes' : 'no',
            $flag->rollout_percentage
        ));

        return Command::SUCCESS;
    }
}
