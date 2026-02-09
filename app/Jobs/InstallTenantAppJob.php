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

class InstallTenantAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    public function __construct(
        public Tenant $tenant,
        public string $appType,
        public ?string $repoUrl = null,
        public array $adminConfig = []
    ) {}

    public function handle(): void
    {
        $appType = $this->appType;
        $repoUrl = $this->repoUrl ?: '';
        
        $tenantKey = $this->tenant->instance_key;
        $tenantRoot = $this->tenant->instance_root;

        if (!$tenantKey || !$tenantRoot) {
            Log::error("Missing tenant key or root for ID {$this->tenant->id}");
            return;
        }

        $script = base_path('infrastructure/install-tenant-app.sh');
        
        Log::info("Starting app installation for Tenant {$this->tenant->id}: $appType");

        // Ensure script is executable
        chmod($script, 0755);

        $systemUser = $this->tenant->instance_system_user ?: 'www-data';
        $dbName = $this->tenant->instance_db_name ?: '';
        $dbUser = $this->tenant->instance_db_user ?: '';
        $dbPass = $this->tenant->instance_db_password ?: '';
        $adminEmail = $this->adminConfig['admin_email'] ?? '';
        $adminUser = $this->adminConfig['admin_user'] ?? '';
        $adminPass = $this->adminConfig['admin_password'] ?? '';
        $appUrl = $this->adminConfig['url'] ?? "http://localhost";

        $result = Process::run("sudo $script \"$tenantKey\" \"$tenantRoot\" \"$appType\" \"$repoUrl\" \"$systemUser\" \"www-data\" \"$dbName\" \"$dbUser\" \"$dbPass\" \"$adminEmail\" \"$adminUser\" \"$adminPass\" \"$appUrl\"");

        if ($result->failed()) {
            $error = $result->errorOutput();
            Log::error("App installation failed for Tenant {$this->tenant->id}: " . $error);
            
            $this->tenant->instance_last_error = "App Install Failed: " . substr($error, 0, 200);
            $this->tenant->save();
        } else {
            Log::info("App installation success for Tenant {$this->tenant->id}");
            $this->tenant->instance_last_error = null;
            $this->tenant->instance_installed_at = now();
            $this->tenant->instance_installed_app = $appType;
            $this->tenant->save();
        }
    }
}
