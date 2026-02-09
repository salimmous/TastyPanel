<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\PlatformSetting;

class SearchService
{
    public function settings(): array
    {
        $defaults = [
            'search_enabled' => true,
            'search_driver' => 'database',
            'search_endpoint' => '',
            'search_api_key' => '',
            'search_index_prefix' => 'tastypanel',
        ];

        return array_merge($defaults, PlatformSetting::getData());
    }

    public function status(): array
    {
        $settings = $this->settings();
        return [
            'enabled' => (bool) ($settings['search_enabled'] ?? true),
            'driver' => $settings['search_driver'] ?? 'database',
            'endpoint' => $settings['search_endpoint'] ?? '',
            'index_prefix' => $settings['search_index_prefix'] ?? 'tastypanel',
        ];
    }

    public function reindex(?int $tenantId = null, string $environment = 'production'): array
    {
        $settings = $this->settings();
        $driver = $settings['search_driver'] ?? 'database';
        if ($driver !== 'database') {
            throw new \RuntimeException('External search drivers are not configured yet.');
        }

        $articles = $this->baseArticleQuery($tenantId, $environment)->count();
        $recipes = $this->baseRecipeQuery($tenantId, $environment)->count();
        $categories = $this->baseCategoryQuery($tenantId, $environment)->count();

        return [
            'driver' => $driver,
            'counts' => [
                'articles' => $articles,
                'recipes' => $recipes,
                'categories' => $categories,
            ],
        ];
    }

    public function search(string $query, ?int $tenantId = null, string $environment = 'production', array $types = []): array
    {
        $settings = $this->settings();
        if (!($settings['search_enabled'] ?? true)) {
            return [
                'results' => [],
                'message' => 'Search disabled.',
            ];
        }

        $driver = $settings['search_driver'] ?? 'database';
        if ($driver !== 'database') {
            throw new \RuntimeException('External search drivers are not configured yet.');
        }

        $types = $types ?: ['articles', 'recipes', 'categories'];
        $results = [];

        if (in_array('articles', $types, true)) {
            $results['articles'] = $this->baseArticleQuery($tenantId, $environment)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                })
                ->limit(15)
                ->get();
        }

        if (in_array('recipes', $types, true)) {
            $results['recipes'] = $this->baseRecipeQuery($tenantId, $environment)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                })
                ->limit(15)
                ->get();
        }

        if (in_array('categories', $types, true)) {
            $results['categories'] = $this->baseCategoryQuery($tenantId, $environment)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                })
                ->limit(15)
                ->get();
        }

        return [
            'driver' => $driver,
            'results' => $results,
        ];
    }

    private function baseArticleQuery(?int $tenantId, string $environment)
    {
        $query = Article::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        $query->where('environment', $environment);
        if ($environment === 'production') {
            $query->where('status', 'published');
        }
        return $query;
    }

    private function baseRecipeQuery(?int $tenantId, string $environment)
    {
        $query = Recipe::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        $query->where('environment', $environment);
        if ($environment === 'production') {
            $query->where('status', 'published');
        }
        return $query;
    }

    private function baseCategoryQuery(?int $tenantId, string $environment)
    {
        $query = Category::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        $query->where('environment', $environment);
        return $query;
    }
}
