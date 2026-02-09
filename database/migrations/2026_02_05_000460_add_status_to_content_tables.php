<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('environment');
            $table->timestamp('reviewed_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('reviewed_at');
            $table->timestamp('published_at')->nullable()->after('approved_at');
            $table->index(['tenant_id', 'environment', 'status']);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('environment');
            $table->timestamp('reviewed_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('reviewed_at');
            $table->timestamp('published_at')->nullable()->after('approved_at');
            $table->index(['tenant_id', 'environment', 'status']);
        });

        DB::table('articles')->update([
            'status' => 'published',
            'published_at' => DB::raw('created_at'),
        ]);
        DB::table('recipes')->update([
            'status' => 'published',
            'published_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'environment', 'status']);
            $table->dropColumn(['status', 'reviewed_at', 'approved_at', 'published_at']);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'environment', 'status']);
            $table->dropColumn(['status', 'reviewed_at', 'approved_at', 'published_at']);
        });
    }
};
