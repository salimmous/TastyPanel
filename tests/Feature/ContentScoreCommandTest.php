<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Recipe;
use App\Models\Tenant;
use App\Models\Category;
use App\Services\ContentScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class ContentScoreCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_scores_articles()
    {
        $tenant = Tenant::create(['name' => 'Tenant 1', 'slug' => 'tenant-1']);
        $article = Article::create([
            'tenant_id' => $tenant->id,
            'title' => 'Test Article',
            'description' => 'Test Description',
            'slug' => 'test-article-1'
        ]);

        $mockService = Mockery::mock(ContentScoringService::class);
        $mockService->shouldReceive('score')
            ->once()
            ->with('Test Article', 'Test Description')
            ->andReturn([
                'readability_score' => 80,
                'seo_score' => 90,
                'word_count' => 100,
                'reading_time_minutes' => 5,
                'language' => 'en',
            ]);

        $this->instance(ContentScoringService::class, $mockService);

        $this->artisan('content:score', ['--type' => 'articles'])
            ->assertExitCode(0)
            ->expectsOutput('Scored 1 items.');

        $article->refresh();
        $this->assertEquals(80, $article->readability_score);
        $this->assertEquals(90, $article->seo_score);
    }

    public function test_it_scores_recipes()
    {
        $tenant = Tenant::create(['name' => 'Tenant 1', 'slug' => 'tenant-1']);
        $category = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cat 1',
            'slug' => 'cat-1',
            'image' => 'test.jpg',
            'description' => 'Test Category Description'
        ]);
        $recipe = Recipe::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'title' => 'Test Recipe',
            'description' => 'Delicious food',
            'ingredients' => ['Salt', 'Pepper'],
            'instructions' => ['Mix', 'Cook'],
            'slug' => 'test-recipe-1',
            'image' => 'recipe.jpg',
            'prep_time' => 10,
            'cook_time' => 20,
            'servings' => 4,
        ]);

        $mockService = Mockery::mock(ContentScoringService::class);
        $expectedBody = "Delicious food\nSalt Pepper\nMix Cook";

        $mockService->shouldReceive('score')
            ->once()
            ->with('Test Recipe', $expectedBody)
            ->andReturn([
                'readability_score' => 70,
                'seo_score' => 85,
            ]);

        $this->instance(ContentScoringService::class, $mockService);

        $this->artisan('content:score', ['--type' => 'recipes'])
            ->assertExitCode(0)
            ->expectsOutput('Scored 1 items.');

        $recipe->refresh();
        $this->assertEquals(70, $recipe->readability_score);
    }

    public function test_it_filters_by_tenant()
    {
        $tenant1 = Tenant::create(['name' => 'Tenant 1', 'slug' => 'tenant-1']);
        $tenant2 = Tenant::create(['name' => 'Tenant 2', 'slug' => 'tenant-2']);

        $article1 = Article::create([
            'tenant_id' => $tenant1->id,
            'title' => 'Article 1',
            'description' => 'Desc 1',
            'slug' => 'article-1'
        ]);

        $article2 = Article::create([
            'tenant_id' => $tenant2->id,
            'title' => 'Article 2',
            'description' => 'Desc 2',
            'slug' => 'article-2'
        ]);

        $mockService = Mockery::mock(ContentScoringService::class);
        $mockService->shouldReceive('score')
            ->once()
            ->with('Article 1', 'Desc 1')
            ->andReturn(['readability_score' => 80]);

        $this->instance(ContentScoringService::class, $mockService);

        $this->artisan('content:score', ['--tenant' => $tenant1->id, '--type' => 'articles'])
            ->assertExitCode(0)
            ->expectsOutput('Scored 1 items.');

        $article1->refresh();
        $article2->refresh();

        $this->assertEquals(80, $article1->readability_score);
        $this->assertNull($article2->readability_score);
    }

    public function test_it_scores_everything_if_no_type_specified()
    {
        $tenant = Tenant::create(['name' => 'Tenant 1', 'slug' => 'tenant-1']);
        $category = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cat 1',
            'slug' => 'cat-1',
            'image' => 'test.jpg',
            'description' => 'Test Category Description'
        ]);

        Article::create([
            'tenant_id' => $tenant->id,
            'title' => 'Article 1',
            'description' => 'Desc 1',
            'slug' => 'article-1'
        ]);

        Recipe::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'title' => 'Recipe 1',
            'description' => 'Desc 1',
            'ingredients' => ['Ing'],
            'instructions' => ['Inst'],
            'slug' => 'recipe-1',
            'image' => 'recipe.jpg',
            'prep_time' => 10,
            'cook_time' => 20,
            'servings' => 4,
        ]);

        $mockService = Mockery::mock(ContentScoringService::class);
        $mockService->shouldReceive('score')
            ->twice()
            ->andReturn(['readability_score' => 80]);

        $this->instance(ContentScoringService::class, $mockService);

        $this->artisan('content:score')
            ->assertExitCode(0)
            ->expectsOutput('Scored 2 items.');
    }
}
