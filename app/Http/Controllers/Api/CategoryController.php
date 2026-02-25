<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $environment = $this->getEnvironment();
        $query = Category::with([
            'recipes' => fn ($q) => $q->where('environment', $environment),
        ]);

        $this->scopeWithTenant($query);

        $categories = $query->get();

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required',
            'name' => 'required',
            'image' => 'required',
            'description' => 'required',
        ]);

        if (! $this->isSlugUnique(Category::class, $validated['slug'])) {
            return response()->json(['message' => 'Slug already exists.'], 422);
        }

        $validated = $this->applyTenantData($validated);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $environment = $this->getEnvironment();
        $query = Category::where('slug', $slug);

        $this->scopeWithTenant($query);

        $category = $query->with([
            'recipes' => fn ($q) => $q->where('environment', $environment),
        ])->firstOrFail();

        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $query = Category::query();
        $this->scopeWithTenant($query);
        $category = $query->findOrFail($id);

        $validated = $request->validate([
            'slug' => 'sometimes',
            'name' => 'sometimes',
            'image' => 'sometimes',
            'description' => 'sometimes',
        ]);

        if (! empty($validated['slug'])) {
            if (! $this->isSlugUnique(Category::class, $validated['slug'], $category->id)) {
                return response()->json(['message' => 'Slug already exists.'], 422);
            }
        }

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $query = Category::query();
        $this->scopeWithTenant($query);
        $category = $query->findOrFail($id);
        $category->delete();

        return response()->json(null, 204);
    }
}
