<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->unsignedSmallInteger('readability_score')->nullable();
            $table->unsignedSmallInteger('seo_score')->nullable();
            $table->unsignedInteger('word_count')->nullable();
            $table->unsignedSmallInteger('reading_time_minutes')->nullable();
            $table->string('language', 12)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn([
                'readability_score',
                'seo_score',
                'word_count',
                'reading_time_minutes',
                'language',
            ]);
        });
    }
};
