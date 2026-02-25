<?php

namespace Tests\Unit\Services;

use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MealPlanningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class MealPlanningServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MealPlanningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MealPlanningService();
    }

    protected function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /** @test */
    public function it_aggregates_ingredients_from_multiple_items()
    {
        $tenant = Tenant::factory()->create();
        $mealPlan = MealPlan::factory()->create(['tenant_id' => $tenant->id]);

        $recipe1 = Recipe::factory()->create([
            'tenant_id' => $tenant->id,
            'ingredients' => [
                ['name' => 'Salt', 'quantity' => 1, 'unit' => 'tsp'],
            ]
        ]);

        $recipe2 = Recipe::factory()->create([
            'tenant_id' => $tenant->id,
            'ingredients' => [
                ['name' => 'Salt', 'quantity' => 2, 'unit' => 'tsp'],
            ]
        ]);

        MealPlanItem::factory()->create(['meal_plan_id' => $mealPlan->id, 'recipe_id' => $recipe1->id, 'servings' => 4]);
        MealPlanItem::factory()->create(['meal_plan_id' => $mealPlan->id, 'recipe_id' => $recipe2->id, 'servings' => 4]);

        $result = $this->invokeMethod($this->service, 'aggregateIngredients', [$mealPlan]);

        $this->assertCount(1, $result);
        $this->assertEquals('Salt', $result[0]['name']);
        $this->assertEquals(3, $result[0]['quantity']);
        $this->assertEquals('tsp', $result[0]['unit']);
    }

    /** @test */
    public function it_scales_ingredients_based_on_servings()
    {
        $tenant = Tenant::factory()->create();
        $mealPlan = MealPlan::factory()->create(['tenant_id' => $tenant->id]);

        $recipe = Recipe::factory()->create([
            'tenant_id' => $tenant->id,
            'servings' => 4,
            'ingredients' => [
                ['name' => 'Chicken', 'quantity' => 500, 'unit' => 'g'],
            ]
        ]);

        // 2 servings instead of 4 (0.5 scale)
        MealPlanItem::factory()->create([
            'meal_plan_id' => $mealPlan->id,
            'recipe_id' => $recipe->id,
            'servings' => 2
        ]);

        $result = $this->invokeMethod($this->service, 'aggregateIngredients', [$mealPlan]);

        $this->assertEquals(250, $result[0]['quantity']);
    }

    /** @test */
    public function it_keeps_different_units_separate()
    {
        $tenant = Tenant::factory()->create();
        $mealPlan = MealPlan::factory()->create(['tenant_id' => $tenant->id]);

        $recipe1 = Recipe::factory()->create([
            'tenant_id' => $tenant->id,
            'ingredients' => [['name' => 'Milk', 'quantity' => 100, 'unit' => 'ml']]
        ]);

        $recipe2 = Recipe::factory()->create([
            'tenant_id' => $tenant->id,
            'ingredients' => [['name' => 'Milk', 'quantity' => 1, 'unit' => 'cup']]
        ]);

        MealPlanItem::factory()->create(['meal_plan_id' => $mealPlan->id, 'recipe_id' => $recipe1->id, 'servings' => 4]);
        MealPlanItem::factory()->create(['meal_plan_id' => $mealPlan->id, 'recipe_id' => $recipe2->id, 'servings' => 4]);

        $result = $this->invokeMethod($this->service, 'aggregateIngredients', [$mealPlan]);

        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_guesses_category_correctly()
    {
        $tenant = Tenant::factory()->create();
        $mealPlan = MealPlan::factory()->create(['tenant_id' => $tenant->id]);

        $recipe = Recipe::factory()->create([
            'tenant_id' => $tenant->id,
            'ingredients' => [
                ['name' => 'Chicken Breast', 'quantity' => 1, 'unit' => 'kg'], // Should be meat
                ['name' => 'Carrot', 'quantity' => 1, 'unit' => 'kg'], // Should be produce
                ['name' => 'Milk', 'quantity' => 1, 'unit' => 'l'], // Should be dairy
            ]
        ]);

        MealPlanItem::factory()->create(['meal_plan_id' => $mealPlan->id, 'recipe_id' => $recipe->id]);

        $result = $this->invokeMethod($this->service, 'aggregateIngredients', [$mealPlan]);

        // Convert result to keyed array for easier assertion
        $keyed = collect($result)->mapWithKeys(fn($item) => [$item['name'] => $item['category']])->toArray();

        $this->assertEquals('meat', $keyed['Chicken Breast']);
        $this->assertEquals('produce', $keyed['Carrot']);
        $this->assertEquals('dairy', $keyed['Milk']);
    }

    /** @test */
    public function it_sorts_ingredients_by_category()
    {
        $tenant = Tenant::factory()->create();
        $mealPlan = MealPlan::factory()->create(['tenant_id' => $tenant->id]);

        $recipe = Recipe::factory()->create([
            'tenant_id' => $tenant->id,
            'ingredients' => [
                ['name' => 'Milk', 'quantity' => 1, 'unit' => 'l'], // dairy
                ['name' => 'Carrot', 'quantity' => 1, 'unit' => 'kg'], // produce
                ['name' => 'Chicken', 'quantity' => 1, 'unit' => 'kg'], // meat
            ]
        ]);

        MealPlanItem::factory()->create(['meal_plan_id' => $mealPlan->id, 'recipe_id' => $recipe->id]);

        $result = $this->invokeMethod($this->service, 'aggregateIngredients', [$mealPlan]);

        // Expected order: dairy, meat, produce (alphabetical keys)
        // dairy, meat, produce
        $this->assertEquals('dairy', $result[0]['category']);
        $this->assertEquals('meat', $result[1]['category']);
        $this->assertEquals('produce', $result[2]['category']);
    }

    /** @test */
    public function it_handles_empty_ingredients()
    {
        $tenant = Tenant::factory()->create();
        $mealPlan = MealPlan::factory()->create(['tenant_id' => $tenant->id]);

        $recipe = Recipe::factory()->create([
            'tenant_id' => $tenant->id,
            'ingredients' => []
        ]);

        MealPlanItem::factory()->create(['meal_plan_id' => $mealPlan->id, 'recipe_id' => $recipe->id]);

        $result = $this->invokeMethod($this->service, 'aggregateIngredients', [$mealPlan]);

        $this->assertCount(0, $result);
    }
}
