<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Support\Str;

class PhpMyAdminProvisioningService
{
    public function provision(Tenant $tenant): array
    {
        $domain = $tenant->domains->firstWhere('is_primary', true) ?? $tenant->domains->first();
        if (! $domain instanceof Domain) {
            return [
                'success' => false,
                'output' => 'No primary domain. Add a domain to this site first.',
            ];
        }

        $dbName = $tenant->instance_db_name;
        if (empty($dbName)) {
            return [
                'success' => false,
                'output' => 'Tenant database not provisioned yet. Run instance provisioning first.',
            ];
        }

        $slug = $this->slugForTenant($tenant);
        $script = (string) config('services.phpmyadmin.provision_script');
        if ($script === '' || ! is_file($script)) {
            return [
                'success' => false,
                'output' => 'phpMyAdmin provision script not configured or missing.',
            ];
        }

        $commandParts = [];
        if (config('services.phpmyadmin.provision_use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = 'env';
        $commandParts[] = 'TENANT_SLUG='.$slug;
        $commandParts[] = 'PRIMARY_DOMAIN='.$domain->hostname;
        $commandParts[] = 'DB_NAME='.$dbName;
        $mysqlRootPass = env('MYSQL_ROOT_PASSWORD');
        if ($mysqlRootPass !== null && $mysqlRootPass !== '') {
            $commandParts[] = 'MYSQL_ROOT_PASSWORD='.$mysqlRootPass;
        }
        $commandParts[] = $script;
        $commandParts[] = $slug;
        $commandParts[] = $domain->hostname;
        $commandParts[] = $dbName;

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));
        $output = [];
        $exitCode = 0;
        exec($escaped.' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
            'exit_code' => $exitCode,
        ];
    }

    private function slugForTenant(Tenant $tenant): string
    {
        if (! empty($tenant->slug) && is_string($tenant->slug)) {
            return Str::slug($tenant->slug);
        }
        if (! empty($tenant->instance_key)) {
            return preg_replace('/[^a-zA-Z0-9_-]/', '-', $tenant->instance_key);
        }

        return 'tenant-'.$tenant->id;
    }
}
