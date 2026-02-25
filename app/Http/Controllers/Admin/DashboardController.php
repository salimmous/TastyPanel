<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\User;
use App\Support\AdminEnvironmentResolver;
use App\Support\AdminTenantResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $today = Carbon::today();
        $lastWeek = Carbon::now()->subWeek();
        $tenantId = AdminTenantResolver::resolveId($request);
        $environment = AdminEnvironmentResolver::resolve($request);

        // Total counts
        $recipeQuery = Recipe::query();
        $articleQuery = Article::query();
        $categoryQuery = Category::query();
        $userQuery = User::query();

        if ($tenantId) {
            $recipeQuery->where('tenant_id', $tenantId);
            $articleQuery->where('tenant_id', $tenantId);
            $categoryQuery->where('tenant_id', $tenantId);
            $userQuery->where('tenant_id', $tenantId);
        }
        $recipeQuery->where('environment', $environment);
        $articleQuery->where('environment', $environment);
        $categoryQuery->where('environment', $environment);

        $totalRecipes = $recipeQuery->count();
        $totalArticles = $articleQuery->count();
        $totalPosts = $totalRecipes + $totalArticles;
        $totalCategories = $categoryQuery->count();
        $totalUsers = $userQuery->count();

        // Today's counts
        $todayRecipes = Recipe::whereDate('created_at', $today)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->count();
        $todayArticles = Article::whereDate('created_at', $today)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->count();
        $todayPosts = $todayRecipes + $todayArticles;

        // Last week performance
        $lastWeekPosts = Recipe::where('created_at', '>=', $lastWeek)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date => $item->count];
            });

        // Latest posts (recipes + articles)
        $latestRecipes = Recipe::with('category')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'type' => 'recipe',
                    'category' => $recipe->category ? ['name' => $recipe->category->name] : null,
                    'created_at' => $recipe->created_at,
                ];
            });

        $latestArticles = Article::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'type' => 'article',
                    'category' => null,
                    'created_at' => $article->created_at,
                ];
            });

        $latestPosts = $latestRecipes->concat($latestArticles)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        // Popular posts (most recent)
        $popularPosts = $latestPosts->take(5);

        return response()->json([
            'stats' => [
                'total_posts' => $totalPosts,
                'total_recipes' => $totalRecipes,
                'total_articles' => $totalArticles,
                'total_categories' => $totalCategories,
                'total_users' => $totalUsers,
                'today_posts' => $todayPosts,
                'today_recipes' => $todayRecipes,
                'today_articles' => $todayArticles,
            ],
            'last_week_performance' => $lastWeekPosts,
            'overview' => [
                'total_posts' => $totalPosts,
                'total_read_hits' => 0,
            ],
            'latest_posts' => $latestPosts,
            'popular_posts' => $popularPosts,
        ]);
    }
}
