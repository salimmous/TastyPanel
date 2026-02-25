<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            $table->integer('version_number');
            $table->json('content'); // Full recipe snapshot
            $table->string('change_summary')->nullable();
            $table->boolean('is_current')->default(false);

            $table->timestamps();

            $table->unique(['recipe_id', 'version_number']);
            $table->index(['recipe_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_versions');
    }
};
