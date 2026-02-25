<?php

namespace Tests\Unit;

use App\Helpers\SeoHelper;
use PHPUnit\Framework\TestCase;

class SeoHelperTest extends TestCase
{
    /**
     * Test that recipeSchema handles ingredients as a JSON string.
     */
    public function test_recipe_schema_handles_json_ingredients()
    {
        $ingredients = ['Flour', 'Sugar', 'Eggs'];
        $recipe = (object) [
            'title' => 'Test Recipe',
            'image' => 'test.jpg',
            'author' => 'Test Author',
            'published_at' => '2023-01-01',
            'created_at' => '2023-01-01',
            'description' => 'Test Description',
            'ingredients' => json_encode($ingredients),
            'instructions' => 'Test Instructions',
        ];

        $schema = SeoHelper::recipeSchema($recipe);

        $this->assertEquals($ingredients, $schema['recipeIngredient']);
    }

    /**
     * Test that recipeSchema handles ingredients as an array.
     */
    public function test_recipe_schema_handles_array_ingredients()
    {
        $ingredients = ['Flour', 'Sugar', 'Eggs'];
        $recipe = (object) [
            'title' => 'Test Recipe',
            'image' => 'test.jpg',
            'author' => 'Test Author',
            'published_at' => '2023-01-01',
            'created_at' => '2023-01-01',
            'description' => 'Test Description',
            'ingredients' => $ingredients,
            'instructions' => 'Test Instructions',
        ];

        $schema = SeoHelper::recipeSchema($recipe);

        $this->assertEquals($ingredients, $schema['recipeIngredient']);
    }

    /**
     * Test that recipeSchema handles null ingredients.
     */
    public function test_recipe_schema_handles_null_ingredients()
    {
        $recipe = (object) [
            'title' => 'Test Recipe',
            'image' => 'test.jpg',
            'author' => 'Test Author',
            'published_at' => '2023-01-01',
            'created_at' => '2023-01-01',
            'description' => 'Test Description',
            'ingredients' => null,
            'instructions' => 'Test Instructions',
        ];

        $schema = SeoHelper::recipeSchema($recipe);

        $this->assertNull($schema['recipeIngredient']);
    }

    /**
     * Test that recipeSchema handles invalid JSON ingredients.
     */
    public function test_recipe_schema_handles_invalid_json_ingredients()
    {
        $recipe = (object) [
            'title' => 'Test Recipe',
            'image' => 'test.jpg',
            'author' => 'Test Author',
            'published_at' => '2023-01-01',
            'created_at' => '2023-01-01',
            'description' => 'Test Description',
            'ingredients' => '{invalid json}',
            'instructions' => 'Test Instructions',
        ];

        $schema = SeoHelper::recipeSchema($recipe);

        // json_decode('{invalid json}', true) returns null
        $this->assertNull($schema['recipeIngredient']);
    }
}
