<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('backup_enabled')->default(true);
            $table->unsignedInteger('backup_interval_hours')->nullable();
            $table->unsignedInteger('backup_retention_days')->nullable();
            $table->boolean('backup_s3_enabled')->default(false);
            $table->boolean('backup_keep_local')->default(true);
            $table->string('backup_s3_prefix')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'backup_enabled',
                'backup_interval_hours',
                'backup_retention_days',
                'backup_s3_enabled',
                'backup_keep_local',
                'backup_s3_prefix',
            ]);
        });
    }
};
