<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Breakfast', 'slug' => 'breakfast', 'description' => 'Start your day right with delicious breakfast recipes'],
            ['name' => 'Lunch', 'slug' => 'lunch', 'description' => 'Quick and tasty lunch ideas'],
            ['name' => 'Dinner', 'slug' => 'dinner', 'description' => 'Satisfying dinner recipes for the whole family'],
            ['name' => 'Desserts', 'slug' => 'desserts', 'description' => 'Sweet treats and desserts'],
            ['name' => 'Appetizers', 'slug' => 'appetizers', 'description' => 'Perfect starters for any meal'],
            ['name' => 'Salads', 'slug' => 'salads', 'description' => 'Fresh and healthy salad recipes'],
            ['name' => 'Soups', 'slug' => 'soups', 'description' => 'Comforting soup recipes'],
            ['name' => 'Vegetarian', 'slug' => 'vegetarian', 'description' => 'Delicious meat-free recipes'],
            ['name' => 'Vegan', 'slug' => 'vegan', 'description' => 'Plant-based recipes'],
            ['name' => 'Gluten-Free', 'slug' => 'gluten-free', 'description' => 'Gluten-free friendly recipes'],
            ['name' => 'Keto', 'slug' => 'keto', 'description' => 'Low-carb keto recipes'],
            ['name' => 'Quick & Easy', 'slug' => 'quick-easy', 'description' => 'Recipes ready in 30 minutes or less'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Created '.count($categories).' categories');
    }
}
