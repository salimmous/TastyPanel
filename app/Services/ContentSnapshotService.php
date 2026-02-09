<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\ContentSnapshot;
use App\Models\Recipe;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ContentSnapshotService
{
    public function createSnapshot(int $tenantId, string $environment, ?int $userId = null, ?string $label = null, ?string $description = null): ContentSnapshot
    {
        $data = $this->collectData($tenantId, $environment);

        return ContentSnapshot::create([
            'tenant_id' => $tenantId,
            'environment' => $environment,
            'label' => $label,
            'description' => $description,
            'created_by' => $userId,
            'total_categories' => count($data['categories']),
            'total_recipes' => count($data['recipes']),
            'total_articles' => count($data['articles']),
            'data' => $data,
        ]);
    }

    public function restoreSnapshot(ContentSnapshot $snapshot, string $targetEnvironment): void
    {
        $data = $snapshot->data ?? null;
        if (!$data || !is_array($data)) {
            throw new RuntimeException('Snapshot data is missing.');
        }

        $this->applyData($snapshot->tenant_id, $targetEnvironment, $data);
    }

    public function syncEnvironment(int $tenantId, string $sourceEnvironment, string $targetEnvironment): void
    {
        $data = $this->collectData($tenantId, $sourceEnvironment);
        $this->applyData($tenantId, $targetEnvironment, $data);
    }

    private function collectData(int $tenantId, string $environment): array
    {
        $categories = Category::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->orderBy('id')
            ->get(['slug', 'name', 'image', 'description'])
            ->map(fn ($category) => [
                'slug' => $category->slug,
                'name' => $category->name,
                'image' => $category->image,
                'description' => $category->description,
            ])
            ->values()
            ->all();

        $recipes = Recipe::with('category:id,slug')
            ->where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->orderBy('id')
            ->get()
            ->map(fn ($recipe) => [
                'slug' => $recipe->slug,
                'title' => $recipe->title,
                'description' => $recipe->description,
                'image' => $recipe->image,
                'prep_time' => $recipe->prep_time,
                'cook_time' => $recipe->cook_time,
                'servings' => $recipe->servings,
                'ingredients' => $recipe->ingredients,
                'instructions' => $recipe->instructions,
                'nutrition' => $recipe->nutrition,
                'category_slug' => $recipe->category?->slug,
            ])
            ->values()
            ->all();

        $articles = Article::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->orderBy('id')
            ->get(['slug', 'title', 'description', 'image'])
            ->map(fn ($article) => [
                'slug' => $article->slug,
                'title' => $article->title,
                'description' => $article->description,
                'image' => $article->image,
            ])
            ->values()
            ->all();

        $settings = SiteSetting::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->first()?->data ?? [];

        return [
            'categories' => $categories,
            'recipes' => $recipes,
            'articles' => $articles,
            'settings' => $settings,
        ];
    }

    private function applyData(int $tenantId, string $environment, array $data): void
    {
        DB::transaction(function () use ($tenantId, $environment, $data) {
            Recipe::where('tenant_id', $tenantId)->where('environment', $environment)->delete();
            Category::where('tenant_id', $tenantId)->where('environment', $environment)->delete();
            Article::where('tenant_id', $tenantId)->where('environment', $environment)->delete();

            $categoryMap = [];
            foreach (($data['categories'] ?? []) as $category) {
                $slug = $category['slug'] ?? null;
                if (!$slug && !empty($category['name'])) {
                    $slug = Str::slug($category['name']);
                }
                $record = Category::create([
                    'tenant_id' => $tenantId,
                    'environment' => $environment,
                    'slug' => $slug,
                    'name' => $category['name'] ?? 'Untitled',
                    'image' => $category['image'] ?? '',
                    'description' => $category['description'] ?? '',
                ]);
                if ($record->slug) {
                    $categoryMap[$record->slug] = $record->id;
                }
            }

            foreach (($data['recipes'] ?? []) as $recipe) {
                $categorySlug = $recipe['category_slug'] ?? null;
                $categoryId = $categorySlug ? ($categoryMap[$categorySlug] ?? null) : null;
                if (!$categoryId) {
                    continue;
                }
                $slug = $recipe['slug'] ?? null;
                if (!$slug && !empty($recipe['title'])) {
                    $slug = Str::slug($recipe['title']);
                }

                Recipe::create([
                    'tenant_id' => $tenantId,
                    'environment' => $environment,
                    'slug' => $slug,
                    'category_id' => $categoryId,
                    'title' => $recipe['title'] ?? 'Untitled',
                    'description' => $recipe['description'] ?? '',
                    'image' => $recipe['image'] ?? '',
                    'prep_time' => $recipe['prep_time'] ?? '',
                    'cook_time' => $recipe['cook_time'] ?? '',
                    'servings' => $recipe['servings'] ?? 0,
                    'ingredients' => $recipe['ingredients'] ?? [],
                    'instructions' => $recipe['instructions'] ?? [],
                    'nutrition' => $recipe['nutrition'] ?? null,
                ]);
            }

            foreach (($data['articles'] ?? []) as $article) {
                $slug = $article['slug'] ?? null;
                if (!$slug && !empty($article['title'])) {
                    $slug = Str::slug($article['title']);
                }
                Article::create([
                    'tenant_id' => $tenantId,
                    'environment' => $environment,
                    'slug' => $slug,
                    'title' => $article['title'] ?? 'Untitled',
                    'description' => $article['description'] ?? '',
                    'image' => $article['image'] ?? '',
                ]);
            }

            SiteSetting::updateOrCreate(
                ['tenant_id' => $tenantId, 'environment' => $environment],
                ['data' => $data['settings'] ?? []]
            );
        });
    }
}
