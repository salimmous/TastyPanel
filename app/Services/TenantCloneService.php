<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Recipe;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantCloneService
{
    /**
     * Clone a tenant with all its data
     */
    public function clone(Tenant $source, array $options = []): Tenant
    {
        $newName = $options['name'] ?? $source->name . ' (Copy)';
        $newDomain = $options['domain'] ?? null;
        $cloneContent = $options['clone_content'] ?? true;
        $cloneSettings = $options['clone_settings'] ?? true;

        return DB::transaction(function () use ($source, $newName, $newDomain, $cloneContent, $cloneSettings) {
            // Clone tenant record
            $newTenant = $this->cloneTenantRecord($source, $newName, $newDomain);

            // Clone settings
            if ($cloneSettings) {
                $this->cloneSettings($source, $newTenant);
            }

            // Clone content
            if ($cloneContent) {
                $this->cloneCategories($source, $newTenant);
                $this->cloneRecipes($source, $newTenant);
                $this->cloneArticles($source, $newTenant);
            }

            return $newTenant;
        });
    }

    /**
     * Clone tenant record
     */
    protected function cloneTenantRecord(Tenant $source, string $name, ?string $domain): Tenant
    {
        $data = $source->toArray();

        // Remove unique identifiers
        unset($data['id'], $data['created_at'], $data['updated_at']);

        // Update name and domain
        $data['name'] = $name;
        $data['domain'] = $domain ?? Str::slug($name) . '.localhost';
        $data['status'] = 'pending'; // Start as pending

        // Clear instance-specific data
        $data['instance_id'] = null;
        $data['instance_root'] = null;
        $data['instance_ssh_user'] = null;
        $data['instance_ssh_password'] = null;

        return Tenant::create($data);
    }

    /**
     * Clone tenant settings
     */
    protected function cloneSettings(Tenant $source, Tenant $target): void
    {
        $settings = DB::table('site_settings')
            ->where('tenant_id', $source->id)
            ->first();

        if ($settings) {
            $data = (array) $settings;
            unset($data['id'], $data['created_at'], $data['updated_at']);
            $data['tenant_id'] = $target->id;
            $data['created_at'] = now();
            $data['updated_at'] = now();

            DB::table('site_settings')->insert($data);
        }
    }

    /**
     * Clone categories
     */
    protected function cloneCategories(Tenant $source, Tenant $target): array
    {
        $mapping = []; // old_id => new_id

        $categories = Category::where('tenant_id', $source->id)->get();

        foreach ($categories as $category) {
            $newCategory = $category->replicate();
            $newCategory->tenant_id = $target->id;
            $newCategory->save();

            $mapping[$category->id] = $newCategory->id;
        }

        return $mapping;
    }

    /**
     * Clone recipes
     */
    protected function cloneRecipes(Tenant $source, Tenant $target): int
    {
        $recipes = Recipe::where('tenant_id', $source->id)->get();
        $count = 0;

        foreach ($recipes as $recipe) {
            $newRecipe = $recipe->replicate();
            $newRecipe->tenant_id = $target->id;
            $newRecipe->save();
            $count++;
        }

        return $count;
    }

    /**
     * Clone articles
     */
    protected function cloneArticles(Tenant $source, Tenant $target): int
    {
        $articles = Article::where('tenant_id', $source->id)->get();
        $count = 0;

        foreach ($articles as $article) {
            $newArticle = $article->replicate();
            $newArticle->tenant_id = $target->id;
            $newArticle->save();
            $count++;
        }

        return $count;
    }

    /**
     * Get clone preview (counts of what will be cloned)
     */
    public function getClonePreview(Tenant $tenant): array
    {
        return [
            'tenant' => [
                'name' => $tenant->name,
                'domain' => $tenant->domain,
            ],
            'counts' => [
                'categories' => Category::where('tenant_id', $tenant->id)->count(),
                'recipes' => Recipe::where('tenant_id', $tenant->id)->count(),
                'articles' => Article::where('tenant_id', $tenant->id)->count(),
            ],
            'storage' => [
                'used_bytes' => $tenant->storage_used_bytes ?? 0,
            ],
        ];
    }

    /**
     * Validate clone request
     */
    public function validateClone(Tenant $source, array $options): array
    {
        $errors = [];

        if (!empty($options['domain'])) {
            $exists = Tenant::where('domain', $options['domain'])
                ->where('id', '!=', $source->id)
                ->exists();

            if ($exists) {
                $errors[] = 'Domain already exists';
            }
        }

        return $errors;
    }
}
