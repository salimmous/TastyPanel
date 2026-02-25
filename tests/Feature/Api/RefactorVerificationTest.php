<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Domain;
use App\Models\Recipe;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefactorVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected $domain;

    protected $category;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create a tenant manually
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);

        // Create domain
        $this->domain = Domain::create([
            'tenant_id' => $this->tenant->id,
            'hostname' => 'tenant.test',
            'environment' => 'production',
            'status' => 'active',
        ]);

        // Create a category for the tenant
        $this->category = Category::create([
            'tenant_id' => $this->tenant->id,
            'environment' => 'production',
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test Description',
            'image' => 'test.jpg',
        ]);
    }

    public function test_recipe_creation_scoped_to_tenant()
    {
        $this->actingAs($this->user);

        $recipeData = [
            'slug' => 'pancakes',
            'category_id' => $this->category->id,
            'title' => 'Delicious Pancakes',
            'description' => 'Fluffy pancakes',
            'image' => 'pancakes.jpg',
            'prep_time' => '10m',
            'cook_time' => '15m',
            'servings' => 4,
            'ingredients' => ['flour', 'milk', 'eggs'],
            'instructions' => ['mix', 'cook'],
            'nutrition' => ['calories' => 300],
        ];

        // 1. Create Recipe
        $response = $this->postJson('http://tenant.test/api/recipes', $recipeData);

        if ($response->status() !== 201) {
            dump($response->json());
        }
        $response->assertStatus(201);

        $this->assertDatabaseHas('recipes', [
            'slug' => 'pancakes',
            'tenant_id' => $this->tenant->id,
            'environment' => 'production',
        ]);

        // 2. Try to create duplicate slug in same tenant/env
        $response = $this->postJson('http://tenant.test/api/recipes', $recipeData);
        $response->assertStatus(422)
            ->assertJson(['message' => 'Slug already exists.']);
    }

    public function test_recipe_creation_with_different_environment()
    {
        $this->actingAs($this->user);

        // First create in production
        Recipe::create([
            'tenant_id' => $this->tenant->id,
            'environment' => 'production',
            'slug' => 'pancakes',
            'category_id' => $this->category->id,
            'title' => 'Prod Pancakes',
            'description' => 'Desc',
            'image' => 'img.jpg',
            'prep_time' => '10m',
            'cook_time' => '15m',
            'servings' => 4,
            'ingredients' => ['flour'],
            'instructions' => ['mix'],
        ]);

        // Create staging domain
        Domain::create([
            'tenant_id' => $this->tenant->id,
            'hostname' => 'staging.tenant.test',
            'environment' => 'staging',
            'status' => 'active',
        ]);

        $stagingCategory = Category::create([
            'tenant_id' => $this->tenant->id,
            'environment' => 'staging',
            'name' => 'Test Category Staging',
            'slug' => 'test-category',
            'description' => 'Test Description',
            'image' => 'test.jpg',
        ]);

        $recipeData = [
            'slug' => 'pancakes', // Same slug
            'category_id' => $stagingCategory->id,
            'title' => 'Staging Pancakes',
            'description' => 'Fluffy pancakes',
            'image' => 'pancakes.jpg',
            'prep_time' => '10m',
            'cook_time' => '15m',
            'servings' => 4,
            'ingredients' => ['flour'],
            'instructions' => ['mix'],
            'nutrition' => ['calories' => 300],
        ];

        // Should succeed in staging even if slug exists in production
        $response = $this->postJson('http://staging.tenant.test/api/recipes', $recipeData);

        if ($response->status() !== 201) {
            dump($response->json());
        }
        $response->assertStatus(201);

        $this->assertDatabaseHas('recipes', [
            'slug' => 'pancakes',
            'tenant_id' => $this->tenant->id,
            'environment' => 'staging',
        ]);
    }
}
