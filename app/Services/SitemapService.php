<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapService
{
    /**
     * Generate sitemap for tenant
     */
    public function generateSitemap(Tenant $tenant): string
    {
        $db = app(TenantDatabaseService::class)->connection($tenant);
        $sitemap = Sitemap::create();

        // Add homepage
        $sitemap->add(Url::create('/')
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(1.0));

        // Add recipes
        $recipes = $db->table('recipes')
            ->whereNotNull('published_at')
            ->select('slug', 'updated_at')
            ->get();

        foreach ($recipes as $recipe) {
            $sitemap->add(Url::create("/recipe/{$recipe->slug}")
                ->setLastModificationDate($recipe->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8));
        }

        // Add articles
        $articles = $db->table('articles')
            ->whereNotNull('published_at')
            ->select('slug', 'updated_at')
            ->get();

        foreach ($articles as $article) {
            $sitemap->add(Url::create("/article/{$article->slug}")
                ->setLastModificationDate($article->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.7));
        }

        // Add categories
        $categories = $db->table('categories')
            ->select('slug', 'updated_at')
            ->get();

        foreach ($categories as $category) {
            $sitemap->add(Url::create("/category/{$category->slug}")
                ->setLastModificationDate($category->updated_at ?? now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.6));
        }

        // Save to tenant's public directory
        $path = storage_path("app/tenant-files/{$tenant->id}/public/sitemap.xml");
        $sitemap->writeToFile($path);

        return $path;
    }

    /**
     * Generate robots.txt for tenant
     */
    public function generateRobotsTxt(Tenant $tenant): string
    {
        $domain = $tenant->domains()->where('is_primary', true)->first()?->domain ?? '';

        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin\n";
        $content .= "Disallow: /api\n\n";
        $content .= "Sitemap: https://{$domain}/sitemap.xml\n";

        $path = storage_path("app/tenant-files/{$tenant->id}/public/robots.txt");
        file_put_contents($path, $content);

        return $path;
    }
}
