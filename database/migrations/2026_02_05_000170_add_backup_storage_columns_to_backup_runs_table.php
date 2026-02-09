<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_runs', function (Blueprint $table) {
            $table->string('disk')->nullable()->after('path');
            $table->string('remote_path')->nullable()->after('disk');
            $table->string('checksum')->nullable()->after('remote_path');
        });
    }

    public function down(): void
    {
        Schema::table('backup_runs', function (Blueprint $table) {
            $table->dropColumn(['disk', 'remote_path', 'checksum']);
        });
    }
};
