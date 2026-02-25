<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_public')->default(false);
            $table->integer('recipes_count')->default(0);

            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'slug']);
            $table->index(['tenant_id', 'is_public']);
        });

        Schema::create('collection_recipe', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('added_at')->useCurrent();

            $table->primary(['collection_id', 'recipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_recipe');
        Schema::dropIfExists('collections');
    }
};
