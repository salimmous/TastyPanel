<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Recipes",
 *     description="Recipe management and browsing"
 * )
 */
class RecipeController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/recipes",
     *     tags={"Recipes"},
     *     summary="Get all recipes",
     *     description="Returns paginated list of published recipes",
     *
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category slug",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in title and description",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/Recipe")
     *         )
     *     )
     * )
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $environment = $this->getEnvironment();
        $query = Recipe::with('category');

        $this->scopeWithTenant($query);

        if ($environment === 'production') {
            $query->where('status', 'published');
        }

        // Filter by category if provided
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request, $environment) {
                $q->where('slug', $request->category)
                    ->where('environment', $environment);
            });
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $recipes = $query->get();

        return response()->json($recipes);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/recipes",
     *     tags={"Recipes"},
     *     summary="Create new recipe",
     *     description="Create a new recipe (requires authentication)",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/RecipeInput")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Recipe created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Recipe")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $tenantId = $this->getTenantId();
        $environment = $this->getEnvironment();
        $validated = $request->validate([
            'slug' => 'required',
            'category_id' => 'required|exists:categories,id',
            'title' => 'required',
            'description' => 'required',
            'image' => 'required',
            'prep_time' => 'required',
            'cook_time' => 'required',
            'servings' => 'required|integer',
            'ingredients' => 'required|array',
            'instructions' => 'required|array',
            'nutrition' => 'nullable|array',
        ]);

        if (! $this->isSlugUnique(Recipe::class, $validated['slug'])) {
            return response()->json(['message' => 'Slug already exists.'], 422);
        }

        if ($tenantId) {
            $category = Category::where('environment', $environment)->find($validated['category_id']);
            if (! $category) {
                return response()->json([
                    'message' => 'Category does not belong to current environment.',
                ], 422);
            }
            if ($category->tenant_id && $category->tenant_id !== $tenantId) {
                return response()->json([
                    'message' => 'Category does not belong to current tenant.',
                ], 422);
            }
        }

        $validated = $this->applyTenantData($validated);

        $recipe = Recipe::create($validated);

        return response()->json($recipe->load('category'), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/recipes/{slug}",
     *     tags={"Recipes"},
     *     summary="Get recipe by slug",
     *     description="Returns detailed recipe information",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Recipe slug",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Recipe")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Recipe not found"
     *     )
     * )
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $environment = $this->getEnvironment();
        $query = Recipe::where('slug', $slug);

        $this->scopeWithTenant($query);

        if ($environment === 'production') {
            $query->where('status', 'published');
        }

        $recipe = $query->with('category')->firstOrFail();

        return response()->json($recipe);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/recipes/{id}",
     *     tags={"Recipes"},
     *     summary="Update recipe",
     *     description="Update existing recipe (requires authentication)",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/RecipeInput")
     *     ),
     *
     *     @OA\Response(response=200, description="Recipe updated"),
     *     @OA\Response(response=404, description="Recipe not found")
     * )
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tenantId = $this->getTenantId();
        $environment = $this->getEnvironment();

        $query = Recipe::query();
        $this->scopeWithTenant($query);
        $recipe = $query->findOrFail($id);

        $validated = $request->validate([
            'slug' => 'sometimes',
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes',
            'description' => 'sometimes',
            'image' => 'sometimes',
            'prep_time' => 'sometimes',
            'cook_time' => 'sometimes',
            'servings' => 'sometimes|integer',
            'ingredients' => 'sometimes|array',
            'instructions' => 'sometimes|array',
            'nutrition' => 'nullable|array',
        ]);

        if (! empty($validated['slug'])) {
            if (! $this->isSlugUnique(Recipe::class, $validated['slug'], $recipe->id)) {
                return response()->json(['message' => 'Slug already exists.'], 422);
            }
        }

        if ($tenantId && isset($validated['category_id'])) {
            $category = Category::where('environment', $environment)->find($validated['category_id']);
            if (! $category) {
                return response()->json([
                    'message' => 'Category does not belong to current environment.',
                ], 422);
            }
            if ($category->tenant_id && $category->tenant_id !== $tenantId) {
                return response()->json([
                    'message' => 'Category does not belong to current tenant.',
                ], 422);
            }
        }

        $recipe->update($validated);

        return response()->json($recipe->load('category'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/recipes/{id}",
     *     tags={"Recipes"},
     *     summary="Delete recipe",
     *     description="Delete recipe (requires authentication)",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(response=204, description="Recipe deleted"),
     *     @OA\Response(response=404, description="Recipe not found")
     * )
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $query = Recipe::query();
        $this->scopeWithTenant($query);
        $recipe = $query->findOrFail($id);
        $recipe->delete();

        return response()->json(null, 204);
    }
}
