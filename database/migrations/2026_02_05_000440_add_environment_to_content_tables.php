<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('environment')->default('production')->after('tenant_id');
            $table->index(['tenant_id', 'environment']);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->string('environment')->default('production')->after('tenant_id');
            $table->index(['tenant_id', 'environment']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('environment')->default('production')->after('tenant_id');
            $table->index(['tenant_id', 'environment']);
        });

        DB::table('categories')->update(['environment' => 'production']);
        DB::table('recipes')->update(['environment' => 'production']);
        DB::table('articles')->update(['environment' => 'production']);

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_slug_unique');
            $table->unique(['tenant_id', 'environment', 'slug']);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropUnique('recipes_slug_unique');
            $table->unique(['tenant_id', 'environment', 'slug']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique('articles_slug_unique');
            $table->unique(['tenant_id', 'environment', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'environment', 'slug']);
            $table->dropIndex(['tenant_id', 'environment']);
            $table->dropColumn('environment');
            $table->unique('slug');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'environment', 'slug']);
            $table->dropIndex(['tenant_id', 'environment']);
            $table->dropColumn('environment');
            $table->unique('slug');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'environment', 'slug']);
            $table->dropIndex(['tenant_id', 'environment']);
            $table->dropColumn('environment');
            $table->unique('slug');
        });
    }
};
