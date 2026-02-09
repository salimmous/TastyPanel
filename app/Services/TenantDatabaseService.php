<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantDatabaseService
{
    /**
     * Create MySQL database and user for tenant
     */
    public function create(Tenant $tenant): array
    {
        $dbName = $this->generateDatabaseName($tenant);
        $dbUser = $this->generateDatabaseUser($tenant);
        $dbPass = Str::random(32);

        // Create database
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Create user
        DB::statement("CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPass}'");

        // Grant privileges
        DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost'");

        // Flush privileges
        DB::statement("FLUSH PRIVILEGES");

        return [
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass,
        ];
    }

    /**
     * Drop tenant database and user
     */
    public function drop(Tenant $tenant): void
    {
        $dbName = $tenant->instance_db_name;
        $dbUser = $tenant->instance_db_user;

        if ($dbName && $dbUser) {
            try {
                DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
                DB::statement("DROP USER IF EXISTS '{$dbUser}'@'localhost'");
                DB::statement("FLUSH PRIVILEGES");
            } catch (\Exception $e) {
                \Log::error("Failed to drop tenant database: " . $e->getMessage());
            }
        }
    }

    /**
     * Get connection to tenant database
     */
    public function connection(Tenant $tenant)
    {
        $name = $this->connectionName($tenant);
        $base = config('database.connections.mysql');

        Config::set("database.connections.{$name}", [
            'driver' => 'mysql',
            'host' => $base['host'] ?? '127.0.0.1',
            'port' => $base['port'] ?? 3306,
            'database' => $tenant->instance_db_name,
            'username' => $tenant->instance_db_user,
            'password' => $tenant->instance_db_password,
            'charset' => $base['charset'] ?? 'utf8mb4',
            'collation' => $base['collation'] ?? 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => $base['strict'] ?? true,
            'engine' => $base['engine'] ?? null,
            'options' => $base['options'] ?? [],
        ]);

        return DB::connection($name);
    }

    public function purge(Tenant $tenant): void
    {
        DB::purge($this->connectionName($tenant));
    }

    private function connectionName(Tenant $tenant): string
    {
        return 'tenant_' . $tenant->id;
    }

    private function generateDatabaseName(Tenant $tenant): string
    {
        return 'tastypanel_tenant_' . $tenant->id;
    }

    private function generateDatabaseUser(Tenant $tenant): string
    {
        return 'tenant_' . $tenant->id;
    }
}
