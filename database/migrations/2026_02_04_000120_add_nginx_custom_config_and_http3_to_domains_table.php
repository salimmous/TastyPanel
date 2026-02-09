<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->longText('nginx_custom_config')->nullable()->after('nginx_error');
            $table->boolean('http3_enabled')->default(false)->after('nginx_custom_config');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['nginx_custom_config', 'http3_enabled']);
        });
    }
};
