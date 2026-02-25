<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollectionController extends Controller
{
    /**
     * List user's collections
     */
    public function index(Request $request)
    {
        $tenantId = $request->get('tenant_id');

        $query = Collection::where('tenant_id', $tenantId)
            ->where('user_id', Auth::id())
            ->withCount('recipes');

        if ($request->get('include_public')) {
            $query->orWhere(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('is_public', true);
            });
        }

        $collections = $query->orderByDesc('updated_at')
            ->paginate($request->get('per_page', 20));

        return response()->json($collections);
    }

    /**
     * Create collection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_public' => ['boolean'],
        ]);

        $collection = Collection::create([
            'tenant_id' => $validated['tenant_id'],
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
        ]);

        return response()->json([
            'data' => $collection,
            'message' => 'Collection created',
        ], 201);
    }

    /**
     * Show collection with recipes
     */
    public function show(Collection $collection)
    {
        $this->authorizeAccess($collection);

        return response()->json([
            'data' => $collection->load('recipes:id,title,image,prep_time'),
        ]);
    }

    /**
     * Update collection
     */
    public function update(Request $request, Collection $collection)
    {
        $this->authorizeOwner($collection);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_public' => ['boolean'],
            'cover_image' => ['nullable', 'string'],
        ]);

        $collection->update($validated);

        return response()->json([
            'data' => $collection->fresh(),
            'message' => 'Collection updated',
        ]);
    }

    /**
     * Delete collection
     */
    public function destroy(Collection $collection)
    {
        $this->authorizeOwner($collection);

        $collection->delete();

        return response()->json([
            'message' => 'Collection deleted',
        ]);
    }

    /**
     * Add recipe to collection
     */
    public function addRecipe(Request $request, Collection $collection)
    {
        $this->authorizeOwner($collection);

        $validated = $request->validate([
            'recipe_id' => ['required', 'exists:recipes,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $collection->addRecipe($validated['recipe_id'], $validated['notes'] ?? null);

        return response()->json([
            'message' => 'Recipe added to collection',
            'recipes_count' => $collection->fresh()->recipes_count,
        ]);
    }

    /**
     * Remove recipe from collection
     */
    public function removeRecipe(Collection $collection, int $recipeId)
    {
        $this->authorizeOwner($collection);

        $collection->removeRecipe($recipeId);

        return response()->json([
            'message' => 'Recipe removed from collection',
            'recipes_count' => $collection->fresh()->recipes_count,
        ]);
    }

    /**
     * Reorder recipes in collection
     */
    public function reorder(Request $request, Collection $collection)
    {
        $this->authorizeOwner($collection);

        $validated = $request->validate([
            'recipe_ids' => ['required', 'array'],
            'recipe_ids.*' => ['integer'],
        ]);

        $collection->reorderRecipes($validated['recipe_ids']);

        return response()->json([
            'message' => 'Recipes reordered',
        ]);
    }

    protected function authorizeOwner(Collection $collection): void
    {
        if ($collection->user_id !== Auth::id()) {
            abort(403, 'You do not own this collection');
        }
    }

    protected function authorizeAccess(Collection $collection): void
    {
        if (! $collection->is_public && $collection->user_id !== Auth::id()) {
            abort(403, 'Access denied');
        }
    }
}
