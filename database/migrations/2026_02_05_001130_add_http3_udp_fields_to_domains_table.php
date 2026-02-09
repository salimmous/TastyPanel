<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('http3_udp_status')->nullable();
            $table->text('http3_udp_error')->nullable();
            $table->timestamp('http3_udp_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn([
                'http3_udp_status',
                'http3_udp_error',
                'http3_udp_checked_at',
            ]);
        });
    }
};
