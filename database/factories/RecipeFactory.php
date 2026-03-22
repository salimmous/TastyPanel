<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RecipeFactory extends Factory
{
    protected $model = Recipe::class;

    public function definition(): array
    {
        $title = $this->faker->sentence();
        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'environment' => 'production',
            'image' => $this->faker->imageUrl(),
            'description' => $this->faker->paragraph(),
            'prep_time' => $this->faker->numberBetween(10, 60),
            'cook_time' => $this->faker->numberBetween(15, 120),
            'servings' => 4,
            'ingredients' => [
                ['name' => 'Salt', 'quantity' => 1, 'unit' => 'tsp'],
                ['name' => 'Pepper', 'quantity' => 0.5, 'unit' => 'tsp'],
                ['name' => 'Olive Oil', 'quantity' => 2, 'unit' => 'tbsp'],
            ],
            'instructions' => [
                'Step 1: Prep ingredients.',
                'Step 2: Cook.',
                'Step 3: Serve.',
            ],
            'nutrition' => [
                'calories' => 500,
                'protein' => 20,
                'fat' => 15,
                'carbs' => 60,
            ],
            'status' => 'published',
            'published_at' => now(),
        ];
    }
}
