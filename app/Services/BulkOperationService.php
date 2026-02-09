<?php

namespace App\Services;

use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

class BulkOperationService
{
    /**
     * Bulk update recipes
     */
    public function bulkUpdate(array $recipeIds, array $data): array
    {
        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($recipeIds as $recipeId) {
            try {
                $recipe = Recipe::findOrFail($recipeId);
                $recipe->update($data);
                $successCount++;
            } catch (\Exception $e) {
                $failureCount++;
                $errors[] = [
                    'id' => $recipeId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => $errors,
        ];
    }

    /**
     * Bulk delete recipes
     */
    public function bulkDelete(array $recipeIds, bool $permanent = false): array
    {
        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($recipeIds as $recipeId) {
            try {
                $recipe = Recipe::findOrFail($recipeId);

                if ($permanent) {
                    $recipe->forceDelete();
                } else {
                    $recipe->delete();
                }

                $successCount++;
            } catch (\Exception $e) {
                $failureCount++;
                $errors[] = [
                    'id' => $recipeId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => $errors,
        ];
    }

    /**
     * Bulk publish recipes
     */
    public function bulkPublish(array $recipeIds): array
    {
        return $this->bulkUpdate($recipeIds, ['status' => 'published']);
    }

    /**
     * Bulk set to draft
     */
    public function bulkDraft(array $recipeIds): array
    {
        return $this->bulkUpdate($recipeIds, ['status' => 'draft']);
    }

    /**
     * Bulk change category
     */
    public function bulkChangeCategory(array $recipeIds, int $categoryId): array
    {
        return $this->bulkUpdate($recipeIds, ['category_id' => $categoryId]);
    }

    /**
     * Bulk export to array
     */
    public function bulkExport(array $recipeIds): array
    {
        return Recipe::whereIn('id', $recipeIds)
            ->with(['category', 'user'])
            ->get()
            ->toArray();
    }
}
