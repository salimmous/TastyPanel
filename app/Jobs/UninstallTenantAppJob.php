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
use Illuminate\Support\Str;

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
        $shouldUninstallFiles = false;

        if (!$tenantRoot) {
            Log::warning("Tenant instance_root is empty for ID {$this->tenant->id}. Skipping filesystem operations.");
        } elseif (!is_dir($tenantRoot)) {
            Log::warning("Tenant root not found: {$tenantRoot} for ID {$this->tenant->id}. Skipping filesystem operations.");
        } else {
            $shouldUninstallFiles = true;
        }

        if ($shouldUninstallFiles) {
            // Security: Ensure path is within allowed instances root
            $instancesRoot = config('services.instances.root', '/var/www/tastypanel-sites');
            $realTenantRoot = realpath($tenantRoot);
            $realInstancesRoot = realpath($instancesRoot);

            if (!$realInstancesRoot) {
                 Log::error("Misconfiguration: Instances root does not exist: {$instancesRoot}");
                 return;
            }

            // Check if realpath failed
            if ($realTenantRoot === false) {
                 Log::error("Security Alert: Failed to resolve realpath for {$tenantRoot}");
                 return;
            }

            // Ensure trailing slash for directory containment check
            if (!Str::endsWith($realInstancesRoot, DIRECTORY_SEPARATOR)) {
                $realInstancesRoot .= DIRECTORY_SEPARATOR;
            }

            // Validate that tenant root starts with instances root (prevents traversal and partial matches)
            // Also prevents deleting the root itself (since root/ != root)
            if (!Str::startsWith($realTenantRoot, $realInstancesRoot)) {
                Log::error("Security Alert: Tenant root {$tenantRoot} (resolved: {$realTenantRoot}) is outside allowed instances root {$instancesRoot}");
                return;
            }

            Log::info("Starting app uninstallation for Tenant {$this->tenant->id}");

            // Using a simple command to wipe the content of the directory
            // We use sudo because some files might be owned by the system user
            // Use escapeshellarg to safely handle spaces and special characters
            $safeTenantRoot = escapeshellarg($tenantRoot);

            // Note: escapeshellarg adds quotes. command becomes: sudo rm -rf 'path'/*
            $result = Process::run("sudo rm -rf {$safeTenantRoot}/* && sudo rm -rf {$safeTenantRoot}/.* 2>/dev/null || true");

            if ($result->failed()) {
                Log::error("App uninstallation failed for Tenant {$this->tenant->id}: " . $result->errorOutput());
                return;
            }

            Log::info("App uninstallation success for Tenant {$this->tenant->id}");
            
            // Re-create the empty directory if it was deleted by any chance
            if (!is_dir($tenantRoot)) {
                mkdir($tenantRoot, 0755, true);
            }
            
            // Reset the system user ownership to ensure it's ready for next install
            $systemUser = $this->tenant->instance_system_user ?: 'www-data';

            // Safe chown command
            $chownUserGroup = escapeshellarg("{$systemUser}:www-data");

            Process::run("sudo chown -R {$chownUserGroup} {$safeTenantRoot}");
        }

        // Proceed to update DB if uninstallation succeeded or was skipped (e.g. directory missing)
        $this->tenant->instance_installed_at = null;
        $this->tenant->instance_installed_app = null;
        $this->tenant->save();
    }
}
