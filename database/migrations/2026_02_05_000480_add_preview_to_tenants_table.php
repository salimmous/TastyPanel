<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('preview_enabled')->default(false)->after('staging_enabled');
            $table->foreignId('preview_theme_id')->nullable()->after('staging_theme_id')->constrained('themes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['preview_theme_id']);
            $table->dropColumn('preview_theme_id');
            $table->dropColumn('preview_enabled');
        });
    }
};
