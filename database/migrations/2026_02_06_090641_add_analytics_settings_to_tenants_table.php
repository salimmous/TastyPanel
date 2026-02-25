<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Analytics configuration
            $table->string('analytics_provider')->default('none')->after('mail_configured');
            $table->string('analytics_id')->nullable()->after('analytics_provider');
            $table->boolean('analytics_enabled')->default(false)->after('analytics_id');
            $table->json('analytics_config')->nullable()->after('analytics_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'analytics_provider',
                'analytics_id',
                'analytics_enabled',
                'analytics_config',
            ]);
        });
    }
};
