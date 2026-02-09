<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('mail_local_enabled')->default(false)->after('mail_configured');
            $table->string('mail_provider', 32)->default('local')->after('mail_local_enabled');
            $table->unsignedInteger('mail_daily_limit')->default(500)->after('mail_provider');
            $table->unsignedInteger('mail_per_minute_limit')->default(30)->after('mail_daily_limit');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'mail_local_enabled',
                'mail_provider',
                'mail_daily_limit',
                'mail_per_minute_limit',
            ]);
        });
    }
};
