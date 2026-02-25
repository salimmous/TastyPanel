<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class CleanupTenantInfrastructureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $tenantKey,
        public string $tenantRoot,
        public ?string $dbName = null,
        public ?string $dbUser = null,
        public ?string $systemUser = null,
        public array $domains = []
    ) {}

    public function handle(): void
    {
        Log::info("Starting infrastructure cleanup for tenant: {$this->tenantKey}");

        $scriptsDir = base_path('infrastructure');

        // 1. Cleanup Instance (FPM, DB, Files, System User)
        $deprovisionInstance = "{$scriptsDir}/deprovision-instance.sh";
        $dbName = $this->dbName ?: '';
        $dbUser = $this->dbUser ?: '';
        $systemUser = $this->systemUser ?: '';

        Log::info("Running deprovision-instance for {$this->tenantKey}");
        Process::run("sudo $deprovisionInstance \"{$this->tenantKey}\" \"{$this->tenantRoot}\" \"$dbName\" \"$dbUser\" \"8.3\" \"$systemUser\"");

        // 2. Cleanup Domains (Nginx)
        $provisionNginx = "{$scriptsDir}/provision-nginx.sh";
        foreach ($this->domains as $domain) {
            Log::info("Removing Nginx config for $domain");
            Process::run("sudo $provisionNginx \"$domain\" \"\" \"remove\"");
        }

        Log::info("Infrastructure cleanup completed for tenant: {$this->tenantKey}");
    }
}
