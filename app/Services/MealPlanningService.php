<?php

namespace App\Services;

use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Models\ShoppingList;
use Illuminate\Support\Facades\DB;

class MealPlanningService
{
    /**
     * Generate shopping list from meal plan
     */
    public function generateShoppingList(MealPlan $mealPlan, ?string $name = null): ShoppingList
    {
        return DB::transaction(function () use ($mealPlan, $name) {
            $shoppingList = ShoppingList::create([
                'tenant_id' => $mealPlan->tenant_id,
                'user_id' => $mealPlan->user_id,
                'meal_plan_id' => $mealPlan->id,
                'name' => $name ?? "Shopping for: {$mealPlan->name}",
            ]);

            // Aggregate ingredients from all recipes
            $ingredients = $this->aggregateIngredients($mealPlan);

            // Create shopping list items
            foreach ($ingredients as $ingredient) {
                $shoppingList->items()->create($ingredient);
            }

            $shoppingList->updateCounts();

            return $shoppingList;
        });
    }

    /**
     * Aggregate ingredients from meal plan
     */
    protected function aggregateIngredients(MealPlan $mealPlan): array
    {
        $aggregated = [];
        $items = $mealPlan->items()->with('recipe')->get();

        foreach ($items as $item) {
            $scaled = $item->getScaledIngredients();

            foreach ($scaled as $ingredient) {
                $name = strtolower(trim($ingredient['name'] ?? ''));
                if (empty($name)) {
                    continue;
                }

                $key = $name.'_'.($ingredient['unit'] ?? 'unit');

                if (isset($aggregated[$key])) {
                    // Combine quantities
                    if (isset($ingredient['quantity']) && is_numeric($ingredient['quantity'])) {
                        $aggregated[$key]['quantity'] =
                            ($aggregated[$key]['quantity'] ?? 0) + $ingredient['quantity'];
                    }
                } else {
                    $aggregated[$key] = [
                        'name' => $ingredient['name'] ?? $name,
                        'quantity' => $ingredient['quantity'] ?? null,
                        'unit' => $ingredient['unit'] ?? null,
                        'category' => $this->guessCategory($name),
                        'recipe_id' => $item->recipe_id,
                    ];
                }
            }
        }

        // Sort by category and return
        usort($aggregated, fn ($a, $b) => strcmp($a['category'] ?? '', $b['category'] ?? ''));

        return array_values($aggregated);
    }

    /**
     * Guess ingredient category
     */
    protected function guessCategory(string $name): string
    {
        $categories = [
            'produce' => ['tomato', 'onion', 'garlic', 'lettuce', 'carrot', 'potato', 'apple', 'banana', 'lemon', 'lime', 'pepper', 'cucumber', 'spinach', 'broccoli', 'mushroom'],
            'dairy' => ['milk', 'cheese', 'butter', 'cream', 'yogurt', 'egg', 'sour cream'],
            'meat' => ['chicken', 'beef', 'pork', 'lamb', 'fish', 'salmon', 'tuna', 'shrimp', 'bacon', 'sausage'],
            'bakery' => ['bread', 'roll', 'bun', 'tortilla', 'pita'],
            'pantry' => ['rice', 'pasta', 'flour', 'sugar', 'oil', 'vinegar', 'beans', 'lentils', 'oats'],
            'condiments' => ['ketchup', 'mustard', 'mayo', 'soy sauce', 'hot sauce', 'salsa'],
            'spices' => ['salt', 'pepper', 'cumin', 'paprika', 'oregano', 'basil', 'thyme', 'cinnamon', 'chili'],
        ];

        $nameLower = strtolower($name);

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($nameLower, $keyword)) {
                    return $category;
                }
            }
        }

        return 'other';
    }

    /**
     * Create a weekly meal plan template
     */
    public function createWeeklyPlan(int $tenantId, int $userId, string $name, array $recipesByDay = []): MealPlan
    {
        return DB::transaction(function () use ($tenantId, $userId, $name, $recipesByDay) {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();

            $mealPlan = MealPlan::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'name' => $name,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            foreach ($recipesByDay as $dayOffset => $meals) {
                $date = $startDate->copy()->addDays($dayOffset);

                foreach ($meals as $mealType => $recipeId) {
                    $mealPlan->items()->create([
                        'recipe_id' => $recipeId,
                        'planned_date' => $date,
                        'meal_type' => $mealType,
                    ]);
                }
            }

            return $mealPlan;
        });
    }

    /**
     * Get meal plan calendar view
     */
    public function getCalendarView(MealPlan $mealPlan): array
    {
        $calendar = [];
        $current = $mealPlan->start_date->copy();

        while ($current <= $mealPlan->end_date) {
            $dateKey = $current->format('Y-m-d');
            $calendar[$dateKey] = [
                'date' => $dateKey,
                'day' => $current->format('l'),
                'meals' => [],
            ];

            foreach (MealPlanItem::MEAL_TYPES as $type) {
                $item = $mealPlan->items()
                    ->where('planned_date', $dateKey)
                    ->where('meal_type', $type)
                    ->with('recipe:id,title,image,prep_time,cook_time')
                    ->first();

                $calendar[$dateKey]['meals'][$type] = $item;
            }

            $current->addDay();
        }

        return array_values($calendar);
    }

    /**
     * Suggest recipes for empty slots
     */
    public function suggestRecipes(MealPlan $mealPlan, string $mealType, int $limit = 5): array
    {
        // Get recipes already in this plan
        $usedRecipeIds = $mealPlan->items()->pluck('recipe_id');

        return Recipe::where('tenant_id', $mealPlan->tenant_id)
            ->whereNotIn('id', $usedRecipeIds)
            ->inRandomOrder()
            ->limit($limit)
            ->get(['id', 'title', 'image', 'prep_time', 'cook_time'])
            ->toArray();
    }
}
