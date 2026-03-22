<?php

namespace Database\Factories;

use App\Models\MealPlanItem;
use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class MealPlanItemFactory extends Factory
{
    protected $model = MealPlanItem::class;

    public function definition(): array
    {
        return [
            'meal_plan_id' => MealPlan::factory(),
            'recipe_id' => Recipe::factory(),
            'planned_date' => Carbon::now()->addDay(),
            'meal_type' => 'dinner',
            'servings' => 2,
            'is_completed' => false,
        ];
    }
}
