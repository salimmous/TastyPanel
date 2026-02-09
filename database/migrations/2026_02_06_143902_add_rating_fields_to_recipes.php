<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // Average rating (computed from ratings table)
            $table->decimal('average_rating', 3, 2)->default(0)->after('status');

            // Total number of ratings
            $table->integer('rating_count')->default(0)->after('average_rating');

            // Total number of favorites
            $table->integer('favorites_count')->default(0)->after('rating_count');

            // Indexes for sorting
            $table->index('average_rating');
            $table->index('rating_count');
            $table->index('favorites_count');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex(['average_rating']);
            $table->dropIndex(['rating_count']);
            $table->dropIndex(['favorites_count']);
            $table->dropColumn(['average_rating', 'rating_count', 'favorites_count']);
        });
    }
};
