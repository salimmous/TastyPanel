<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_baselines', function (Blueprint $table) {
            $table->json('paths')->nullable()->after('root_path');
        });
    }

    public function down(): void
    {
        Schema::table('security_baselines', function (Blueprint $table) {
            $table->dropColumn(['paths']);
        });
    }
};
