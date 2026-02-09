<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class UninstallTenantAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(
        public Tenant $tenant
    ) {}

    public function handle(): void
    {
        $tenantRoot = $this->tenant->instance_root;

        if (!$tenantRoot || !is_dir($tenantRoot)) {
            Log::warning("Tenant root not found or invalid for ID {$this->tenant->id}");
            return;
        }

        Log::info("Starting app uninstallation for Tenant {$this->tenant->id}");

        // Using a simple command to wipe the content of the directory
        // We use sudo because some files might be owned by the system user
        $result = Process::run("sudo rm -rf {$tenantRoot}/* && sudo rm -rf {$tenantRoot}/.* 2>/dev/null || true");

        if ($result->failed()) {
            Log::error("App uninstallation failed for Tenant {$this->tenant->id}: " . $result->errorOutput());
        } else {
            Log::info("App uninstallation success for Tenant {$this->tenant->id}");
            
            // Re-create the empty directory if it was deleted by any chance
            if (!is_dir($tenantRoot)) {
                mkdir($tenantRoot, 0755, true);
            }
            
            // Reset the system user ownership to ensure it's ready for next install
            $systemUser = $this->tenant->instance_system_user ?: 'www-data';
            Process::run("sudo chown -R $systemUser:www-data $tenantRoot");

            $this->tenant->instance_installed_at = null;
            $this->tenant->instance_installed_app = null;
            $this->tenant->save();
        }
    }
}
