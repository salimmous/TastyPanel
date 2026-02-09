<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at');

            // Unique constraint - one favorite per user per recipe
            $table->unique(['user_id', 'recipe_id']);

            // Indexes
            $table->index('user_id');
            $table->index('recipe_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
