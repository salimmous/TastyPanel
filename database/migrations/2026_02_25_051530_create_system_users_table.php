<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('username', 64)->unique();
            $table->string('password')->nullable(); // encrypted password
            $table->string('home_dir')->nullable();
            $table->string('shell')->default('/bin/bash');
            $table->json('ssh_keys')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // Data Migration: Move existing system users from tenants table
        if (Schema::hasTable('tenants')) {
            $tenants = DB::table('tenants')->whereNotNull('instance_system_user')->get();
            foreach ($tenants as $tenant) {
                $exists = DB::table('system_users')->where('username', $tenant->instance_system_user)->exists();
                if (!$exists && $tenant->instance_system_user) {
                    DB::table('system_users')->insert([
                        'tenant_id' => $tenant->id,
                        'username' => $tenant->instance_system_user,
                        'password' => null, // Password not stored in tenants table for system user
                        'home_dir' => "/home/{$tenant->instance_system_user}",
                        'shell' => '/bin/bash',
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_users');
    }
};
