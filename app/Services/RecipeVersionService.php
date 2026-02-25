<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeVersion;
use Illuminate\Support\Facades\Auth;

class RecipeVersionService
{
    /**
     * Create a version snapshot
     */
    public function createVersion(Recipe $recipe, ?string $summary = null): RecipeVersion
    {
        return RecipeVersion::createFromRecipe($recipe, Auth::id(), $summary);
    }

    /**
     * Get version history for a recipe
     */
    public function getHistory(Recipe $recipe, int $limit = 10): array
    {
        return $recipe->versions()
            ->with('user:id,name')
            ->orderByDesc('version_number')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Restore a specific version
     */
    public function restore(RecipeVersion $version): Recipe
    {
        $version->restore();

        return $version->recipe->fresh();
    }

    /**
     * Compare two versions
     */
    public function compare(RecipeVersion $v1, RecipeVersion $v2): array
    {
        return $v1->diff($v2);
    }

    /**
     * Get the current version
     */
    public function getCurrentVersion(Recipe $recipe): ?RecipeVersion
    {
        return $recipe->versions()
            ->where('is_current', true)
            ->first();
    }

    /**
     * Hook: auto-version on update
     */
    public function trackUpdate(Recipe $recipe, ?string $summary = null): void
    {
        // Only create version if significant changes
        $dirty = $recipe->getDirty();
        $significantFields = ['title', 'description', 'ingredients', 'instructions', 'prep_time', 'cook_time'];

        if (array_intersect($significantFields, array_keys($dirty))) {
            $this->createVersion($recipe, $summary ?? 'Updated '.implode(', ', array_keys($dirty)));
        }
    }
}
