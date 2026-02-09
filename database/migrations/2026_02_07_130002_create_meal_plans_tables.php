<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('meal_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');

            $table->date('planned_date');
            $table->string('meal_type'); // breakfast, lunch, dinner, snack
            $table->integer('servings')->default(1);
            $table->text('notes')->nullable();
            $table->boolean('is_completed')->default(false);

            $table->timestamps();

            $table->index(['meal_plan_id', 'planned_date']);
            $table->index('meal_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_items');
        Schema::dropIfExists('meal_plans');
    }
};
