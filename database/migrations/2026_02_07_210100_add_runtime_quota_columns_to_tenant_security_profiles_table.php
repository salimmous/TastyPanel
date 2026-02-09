<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_security_profiles', function (Blueprint $table) {
            $table->unsignedInteger('max_cpu_percent')->nullable()->after('max_db_size_mb');
            $table->unsignedInteger('max_memory_mb')->nullable()->after('max_cpu_percent');
            $table->unsignedInteger('max_worker_processes')->nullable()->after('max_memory_mb');
            $table->unsignedTinyInteger('quota_alert_threshold_percent')->default(80)->after('max_worker_processes');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_security_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'max_cpu_percent',
                'max_memory_mb',
                'max_worker_processes',
                'quota_alert_threshold_percent',
            ]);
        });
    }
};
