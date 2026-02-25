<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $recipes = [
            [
                'title' => 'Classic Pancakes',
                'description' => 'Fluffy and delicious breakfast pancakes',
                'ingredients' => json_encode([
                    '2 cups all-purpose flour',
                    '2 tablespoons sugar',
                    '2 teaspoons baking powder',
                    '1 teaspoon salt',
                    '2 eggs',
                    '1 3/4 cups milk',
                    '1/4 cup melted butter',
                ]),
                'instructions' => "1. Mix dry ingredients in a bowl\n2. Whisk eggs, milk, and butter in another bowl\n3. Combine wet and dry ingredients\n4. Cook on heated griddle until bubbles form\n5. Flip and cook until golden brown",
                'prep_time' => 10,
                'cook_time' => 15,
                'servings' => 4,
                'difficulty' => 'easy',
                'category' => 'Breakfast',
            ],
            [
                'title' => 'Spaghetti Carbonara',
                'description' => 'Classic Italian pasta dish',
                'ingredients' => json_encode([
                    '400g spaghetti',
                    '200g pancetta',
                    '4 egg yolks',
                    '100g Parmesan cheese',
                    'Black pepper',
                    'Salt',
                ]),
                'instructions' => "1. Cook spaghetti according to package\n2. Fry pancetta until crispy\n3. Mix egg yolks with Parmesan\n4. Toss hot pasta with pancetta\n5. Add egg mixture and toss quickly\n6. Season with pepper",
                'prep_time' => 10,
                'cook_time' => 20,
                'servings' => 4,
                'difficulty' => 'medium',
                'category' => 'Dinner',
            ],
            [
                'title' => 'Chocolate Chip Cookies',
                'description' => 'Chewy and delicious cookies',
                'ingredients' => json_encode([
                    '2 1/4 cups flour',
                    '1 teaspoon baking soda',
                    '1 cup butter',
                    '3/4 cup sugar',
                    '2 eggs',
                    '2 cups chocolate chips',
                ]),
                'instructions' => "1. Preheat oven to 375°F\n2. Mix flour and baking soda\n3. Cream butter and sugar\n4. Add eggs and mix\n5. Stir in flour mixture and chocolate chips\n6. Drop spoonfuls on baking sheet\n7. Bake 9-11 minutes",
                'prep_time' => 15,
                'cook_time' => 10,
                'servings' => 24,
                'difficulty' => 'easy',
                'category' => 'Desserts',
            ],
            [
                'title' => 'Caesar Salad',
                'description' => 'Fresh and crispy salad with homemade dressing',
                'ingredients' => json_encode([
                    '1 head romaine lettuce',
                    '1/2 cup Caesar dressing',
                    '1/2 cup croutons',
                    '1/4 cup Parmesan cheese',
                    'Black pepper',
                ]),
                'instructions' => "1. Wash and chop romaine lettuce\n2. Toss with Caesar dressing\n3. Add croutons and Parmesan\n4. Season with black pepper\n5. Serve immediately",
                'prep_time' => 10,
                'cook_time' => 0,
                'servings' => 4,
                'difficulty' => 'easy',
                'category' => 'Salads',
            ],
            [
                'title' => 'Chicken Stir Fry',
                'description' => 'Quick and healthy Asian-inspired dish',
                'ingredients' => json_encode([
                    '500g chicken breast',
                    '2 cups mixed vegetables',
                    '3 tablespoons soy sauce',
                    '2 tablespoons sesame oil',
                    '2 cloves garlic',
                    '1 teaspoon ginger',
                ]),
                'instructions' => "1. Cut chicken into bite-sized pieces\n2. Heat oil in wok\n3. Stir fry chicken until cooked\n4. Add vegetables and cook 5 minutes\n5. Add soy sauce, garlic, and ginger\n6. Serve over rice",
                'prep_time' => 15,
                'cook_time' => 15,
                'servings' => 4,
                'difficulty' => 'easy',
                'category' => 'Quick & Easy',
            ],
        ];

        foreach ($recipes as $recipeData) {
            $categoryName = $recipeData['category'];
            unset($recipeData['category']);

            $category = Category::where('name', $categoryName)->first();

            Recipe::create([
                ...$recipeData,
                'slug' => Str::slug($recipeData['title']),
                'category_id' => $category?->id,
                'status' => 'published',
                'is_featured' => rand(0, 1) === 1,
            ]);
        }

        $this->command->info('Created '.count($recipes).' demo recipes');
    }
}
