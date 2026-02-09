<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('http3_status')->default('unknown')->after('http3_enabled');
            $table->text('http3_error')->nullable()->after('http3_status');
            $table->timestamp('http3_checked_at')->nullable()->after('http3_error');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['http3_status', 'http3_error', 'http3_checked_at']);
        });
    }
};
