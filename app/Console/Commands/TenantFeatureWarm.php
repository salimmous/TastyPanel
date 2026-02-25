<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\EdgeCacheService;
use Illuminate\Console\Command;

class TenantFeatureWarm extends Command
{
    protected $signature = 'tenant:prerender {tenant} {--limit=10}';

    protected $description = 'Pre-render and cache popular pages for a tenant (home + latest content).';

    public function handle(EdgeCacheService $cache): int
    {
        $tenant = Tenant::find($this->argument('tenant'));
        if (! $tenant) {
            $this->error('Tenant not found');

            return Command::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $count = $cache->warmTenant($tenant, $limit);
        $this->info("Pre-rendered {$count} pages for {$tenant->name}");

        return Command::SUCCESS;
    }
}
