<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');

            // Rating value (1-5 stars)
            $table->tinyInteger('rating')->unsigned();

            // Optional review text
            $table->text('review')->nullable();

            $table->timestamps();

            // Unique constraint - one rating per user per recipe
            $table->unique(['user_id', 'recipe_id']);

            // Indexes
            $table->index('recipe_id');
            $table->index(['recipe_id', 'rating']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
