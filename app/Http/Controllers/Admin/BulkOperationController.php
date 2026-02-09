<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BulkOperationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkOperationController extends Controller
{
    public function __construct(
        protected BulkOperationService $bulkService
    ) {
    }

    /**
     * Bulk update recipes
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipe_ids' => ['required', 'array', 'min:1'],
            'recipe_ids.*' => ['required', 'integer', 'exists:recipes,id'],
            'data' => ['required', 'array'],
        ]);

        $result = $this->bulkService->bulkUpdate(
            $validated['recipe_ids'],
            $validated['data']
        );

        return response()->json([
            'message' => "Updated {$result['success_count']} recipe(s)",
            'result' => $result,
        ]);
    }

    /**
     * Bulk delete recipes
     */
    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipe_ids' => ['required', 'array', 'min:1'],
            'recipe_ids.*' => ['required', 'integer', 'exists:recipes,id'],
            'permanent' => ['boolean'],
        ]);

        $result = $this->bulkService->bulkDelete(
            $validated['recipe_ids'],
            $validated['permanent'] ?? false
        );

        return response()->json([
            'message' => "Deleted {$result['success_count']} recipe(s)",
            'result' => $result,
        ]);
    }

    /**
     * Bulk publish recipes
     */
    public function publish(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipe_ids' => ['required', 'array', 'min:1'],
            'recipe_ids.*' => ['required', 'integer', 'exists:recipes,id'],
        ]);

        $result = $this->bulkService->bulkPublish($validated['recipe_ids']);

        return response()->json([
            'message' => "Published {$result['success_count']} recipe(s)",
            'result' => $result,
        ]);
    }

    /**
     * Bulk draft recipes
     */
    public function draft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipe_ids' => ['required', 'array', 'min:1'],
            'recipe_ids.*' => ['required', 'integer', 'exists:recipes,id'],
        ]);

        $result = $this->bulkService->bulkDraft($validated['recipe_ids']);

        return response()->json([
            'message' => "Set {$result['success_count']} recipe(s) to draft",
            'result' => $result,
        ]);
    }

    /**
     * Bulk change category
     */
    public function changeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipe_ids' => ['required', 'array', 'min:1'],
            'recipe_ids.*' => ['required', 'integer', 'exists:recipes,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $result = $this->bulkService->bulkChangeCategory(
            $validated['recipe_ids'],
            $validated['category_id']
        );

        return response()->json([
            'message' => "Updated category for {$result['success_count']} recipe(s)",
            'result' => $result,
        ]);
    }

    /**
     * Bulk export recipes
     */
    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipe_ids' => ['required', 'array', 'min:1'],
            'recipe_ids.*' => ['required', 'integer', 'exists:recipes,id'],
        ]);

        $recipes = $this->bulkService->bulkExport($validated['recipe_ids']);

        return response()->json([
            'recipes' => $recipes,
            'count' => count($recipes),
        ]);
    }
}
