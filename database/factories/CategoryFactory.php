<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->word();
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'environment' => 'production',
            'image' => $this->faker->imageUrl(),
            'description' => $this->faker->sentence(),
        ];
    }
}
