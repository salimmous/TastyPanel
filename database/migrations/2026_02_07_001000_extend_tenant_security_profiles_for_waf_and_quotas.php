<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_security_profiles', function (Blueprint $table) {
            $table->boolean('waf_enabled')->default(true)->after('mode');
            $table->string('waf_mode', 16)->default('block')->after('waf_enabled');
            $table->boolean('waf_block_sqli')->default(true)->after('waf_mode');
            $table->boolean('waf_block_xss')->default(true)->after('waf_block_sqli');
            $table->boolean('waf_block_lfi')->default(true)->after('waf_block_xss');
            $table->unsignedInteger('max_monthly_requests')->nullable()->after('waf_block_lfi');
            $table->unsignedInteger('max_storage_mb')->nullable()->after('max_monthly_requests');
            $table->unsignedInteger('max_db_size_mb')->nullable()->after('max_storage_mb');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_security_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'waf_enabled',
                'waf_mode',
                'waf_block_sqli',
                'waf_block_xss',
                'waf_block_lfi',
                'max_monthly_requests',
                'max_storage_mb',
                'max_db_size_mb',
            ]);
        });
    }
};

