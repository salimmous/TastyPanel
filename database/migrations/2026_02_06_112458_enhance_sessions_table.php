<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sessions', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('payload');
            }
            if (!Schema::hasColumn('sessions', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('user_agent');
            }
            if (!Schema::hasColumn('sessions', 'device_name')) {
                $table->string('device_name')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('sessions', 'is_trusted')) {
                $table->boolean('is_trusted')->default(false)->after('device_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['user_agent', 'ip_address', 'device_name', 'is_trusted']);
        });
    }
};
