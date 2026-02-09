<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('staging_theme_id')->nullable()->after('theme_id')->constrained('themes')->nullOnDelete();
            $table->boolean('staging_enabled')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['staging_theme_id']);
            $table->dropColumn(['staging_theme_id', 'staging_enabled']);
        });
    }
};
