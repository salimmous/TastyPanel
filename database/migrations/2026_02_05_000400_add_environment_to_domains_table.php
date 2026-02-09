<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('environment')->default('production')->after('hostname');
            $table->index(['tenant_id', 'environment']);
        });

        DB::table('domains')->update(['environment' => 'production']);
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'environment']);
            $table->dropColumn('environment');
        });
    }
};
