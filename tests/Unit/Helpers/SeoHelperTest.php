<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SeoHelper;
use Tests\TestCase;
use Illuminate\Support\Str;

class SeoHelperTest extends TestCase
{
    /**
     * Test recipeMetaTags with full SEO data provided.
     */
    public function test_recipe_meta_tags_with_full_data(): void
    {
        $recipe = (object) [
            'title' => 'Delicious Pasta',
            'description' => 'A great pasta recipe.',
            'seo_title' => 'Best Pasta Recipe Ever',
            'seo_description' => 'Custom SEO description for the best pasta recipe.',
            'keywords' => 'pasta, italian, dinner',
            'image' => 'https://example.com/pasta.jpg',
            'slug' => 'delicious-pasta',
        ];

        $tags = SeoHelper::recipeMetaTags($recipe);

        $this->assertEquals('Best Pasta Recipe Ever', $tags['title']);
        $this->assertEquals('Custom SEO description for the best pasta recipe.', $tags['description']);
        $this->assertEquals('pasta, italian, dinner', $tags['keywords']);
        $this->assertEquals('Delicious Pasta', $tags['og:title']);
        $this->assertEquals('A great pasta recipe.', $tags['og:description']);
        $this->assertEquals('https://example.com/pasta.jpg', $tags['og:image']);
        $this->assertEquals('article', $tags['og:type']);
        $this->assertEquals(url('/recipe/delicious-pasta'), $tags['og:url']);
        $this->assertEquals('summary_large_image', $tags['twitter:card']);
        $this->assertEquals('Delicious Pasta', $tags['twitter:title']);
        $this->assertEquals('A great pasta recipe.', $tags['twitter:description']);
        $this->assertEquals('https://example.com/pasta.jpg', $tags['twitter:image']);
    }

    /**
     * Test recipeMetaTags with default values when SEO fields are missing.
     */
    public function test_recipe_meta_tags_with_defaults(): void
    {
        $recipe = (object) [
            'title' => 'Basic Salad',
            'description' => 'Simple green salad.',
            'slug' => 'basic-salad',
            // Missing: seo_title, seo_description, keywords, image
        ];

        $tags = SeoHelper::recipeMetaTags($recipe);

        $this->assertEquals('Basic Salad | Recipe', $tags['title']);
        $this->assertEquals('Simple green salad.', $tags['description']);
        $this->assertEquals('', $tags['keywords']);
        $this->assertEquals('', $tags['og:image']);
        $this->assertEquals(url('/recipe/basic-salad'), $tags['og:url']);
        $this->assertEquals('', $tags['twitter:image']);
    }

    /**
     * Test that HTML tags are stripped and description is limited.
     */
    public function test_recipe_meta_tags_strips_html_and_limits_length(): void
    {
        $longDescription = 'This is a <b>very long</b> description that contains <a href="#">HTML tags</a> and should be stripped and truncated to a certain length to ensure it fits within the recommended meta tag limits. ' . str_repeat('Additional text to make it longer. ', 10);

        $recipe = (object) [
            'title' => 'Long Description Recipe',
            'description' => $longDescription,
            'slug' => 'long-description',
        ];

        $tags = SeoHelper::recipeMetaTags($recipe);

        $stripped = strip_tags($longDescription);

        $this->assertEquals(Str::limit($stripped, 160), $tags['description']);
        $this->assertEquals(Str::limit($stripped, 200), $tags['og:description']);
        $this->assertEquals(Str::limit($stripped, 200), $tags['twitter:description']);

        $this->assertStringNotContainsString('<b>', $tags['description']);
        $this->assertStringNotContainsString('</a>', $tags['description']);
    }

    /**
     * Test recipeSchema generates correct structure with full data.
     */
    public function test_recipe_schema_generates_correct_structure(): void
    {
        $recipe = (object) [
            'title' => 'Schema Test Recipe',
            'image' => 'https://example.com/recipe.jpg',
            'author' => 'Chef John',
            'published_at' => '2023-01-01 12:00:00',
            'created_at' => '2022-12-31 10:00:00',
            'description' => 'A <b>bold</b> description.',
            'ingredients' => ['1 cup flour', '2 eggs'],
            'instructions' => '<p>Mix ingredients.</p>',
            'cook_time' => 30,
            'prep_time' => 15,
            'total_time' => 45,
            'servings' => 4,
            'category_name' => 'Dessert',
            'keywords' => 'baking, sweet',
            'calories' => 500,
        ];

        $schema = SeoHelper::recipeSchema($recipe);

        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('Recipe', $schema['@type']);
        $this->assertEquals('Schema Test Recipe', $schema['name']);
        $this->assertEquals('https://example.com/recipe.jpg', $schema['image']);

        $this->assertIsArray($schema['author']);
        $this->assertEquals('Person', $schema['author']['@type']);
        $this->assertEquals('Chef John', $schema['author']['name']);

        $this->assertEquals('2023-01-01 12:00:00', $schema['datePublished']);
        $this->assertEquals('A bold description.', $schema['description']);

        $this->assertEquals(['1 cup flour', '2 eggs'], $schema['recipeIngredient']);
        $this->assertEquals('Mix ingredients.', $schema['recipeInstructions']);

        $this->assertEquals('PT30M', $schema['cookTime']);
        $this->assertEquals('PT15M', $schema['prepTime']);
        $this->assertEquals('PT45M', $schema['totalTime']);

        $this->assertEquals(4, $schema['recipeYield']);
        $this->assertEquals('Dessert', $schema['recipeCategory']);
        $this->assertEquals('baking, sweet', $schema['keywords']);

        $this->assertIsArray($schema['nutrition']);
        $this->assertEquals('NutritionInformation', $schema['nutrition']['@type']);
        $this->assertEquals('500 calories', $schema['nutrition']['calories']);
    }

    /**
     * Test recipeSchema with minimal data (nullable fields).
     */
    public function test_recipe_schema_with_minimal_data(): void
    {
        $recipe = (object) [
            'title' => 'Minimal Recipe',
            'image' => null,
            'created_at' => '2023-01-01 10:00:00', // used as fallback for published_at
            'description' => 'Just a description.',
            'ingredients' => null,
            'instructions' => null,
        ];

        $schema = SeoHelper::recipeSchema($recipe);

        $this->assertEquals('Minimal Recipe', $schema['name']);
        $this->assertNull($schema['image']);

        // Author defaults to Admin
        $this->assertEquals('Admin', $schema['author']['name']);

        // datePublished falls back to created_at
        $this->assertEquals('2023-01-01 10:00:00', $schema['datePublished']);

        $this->assertNull($schema['recipeIngredient']);
        $this->assertEquals('', $schema['recipeInstructions']); // stripping null results in empty string

        $this->assertNull($schema['cookTime']);
        $this->assertNull($schema['prepTime']);
        $this->assertNull($schema['totalTime']);
        $this->assertNull($schema['recipeYield']);
        $this->assertNull($schema['recipeCategory']);
        $this->assertEquals('', $schema['keywords']);
        $this->assertNull($schema['nutrition']);
    }

    /**
     * Test that recipeSchema handles ingredients as a JSON string.
     */
    public function test_recipe_schema_handles_json_ingredients(): void
    {
        $ingredients = ['Flour', 'Sugar', 'Eggs'];
        $recipe = (object) [
            'title' => 'Test Recipe',
            'image' => 'test.jpg',
            'created_at' => '2023-01-01',
            'description' => 'Test Description',
            'ingredients' => json_encode($ingredients),
            'instructions' => 'Test Instructions',
        ];

        $schema = SeoHelper::recipeSchema($recipe);

        $this->assertEquals($ingredients, $schema['recipeIngredient']);
    }

    /**
     * Test that recipeSchema handles invalid JSON ingredients.
     */
    public function test_recipe_schema_handles_invalid_json_ingredients(): void
    {
        $recipe = (object) [
            'title' => 'Test Recipe',
            'image' => 'test.jpg',
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
