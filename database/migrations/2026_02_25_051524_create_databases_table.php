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
        Schema::create('databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('name', 100)->unique(); // db_name
            $table->string('username', 100);       // db_user
            $table->text('password');              // db_password_encrypted
            $table->decimal('size_mb', 10, 2)->default(0);
            $table->timestamp('last_backup_at')->nullable();
            $table->string('status')->default('active'); // active, pending, etc.
            $table->timestamps();
        });

        // Data Migration: Move existing databases from tenants table
        if (Schema::hasTable('tenants')) {
            $tenants = DB::table('tenants')->whereNotNull('instance_db_name')->get();
            foreach ($tenants as $tenant) {
                // Check if database already exists to avoid unique constraint violation
                $exists = DB::table('databases')->where('name', $tenant->instance_db_name)->exists();
                if (!$exists && $tenant->instance_db_name) {
                    DB::table('databases')->insert([
                        'tenant_id' => $tenant->id,
                        'name' => $tenant->instance_db_name,
                        'username' => $tenant->instance_db_user ?? 'root', // Fallback
                        'password' => $tenant->instance_db_password ?? '', // Already encrypted or raw? Assuming encrypted as per prompt "All DB passwords stored encrypted"
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
        Schema::dropIfExists('databases');
    }
};
