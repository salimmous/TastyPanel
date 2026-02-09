<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('nginx_status')->default('pending')->after('status');
            $table->text('nginx_error')->nullable()->after('nginx_status');
            $table->timestamp('nginx_updated_at')->nullable()->after('nginx_error');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['nginx_status', 'nginx_error', 'nginx_updated_at']);
        });
    }
};
