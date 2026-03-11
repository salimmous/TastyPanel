<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Category;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;

/**
 * @OA\Tag(
 *     name="Recipes",
 *     description="Recipe management and browsing"
 * )
 */
class RecipeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/recipes",
     *     tags={"Recipes"},
     *     summary="Get all recipes",
     *     description="Returns paginated list of published recipes",
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category slug",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in title and description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Recipe")
     *         )
     *     )
     * )
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $query = Recipe::with('category');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        $query->where('environment', $environment);
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RecipeInput")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Recipe created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Recipe")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     * Store a newly created resource in storage.
     */
    public function store(StoreRecipeRequest $request)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $validated = $request->validated();

        if ($tenantId) {
            $validated['tenant_id'] = $tenantId;
        }
        $validated['environment'] = $environment;

        $recipe = Recipe::create($validated);
        return response()->json($recipe->load('category'), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/recipes/{slug}",
     *     tags={"Recipes"},
     *     summary="Get recipe by slug",
     *     description="Returns detailed recipe information",
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Recipe slug",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Recipe")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recipe not found"
     *     )
     * )
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $recipe = Recipe::where('slug', $slug)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->when($environment === 'production', fn($q) => $q->where('status', 'published'))
            ->with('category')
            ->firstOrFail();
        return response()->json($recipe);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/recipes/{id}",
     *     tags={"Recipes"},
     *     summary="Update recipe",
     *     description="Update existing recipe (requires authentication)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RecipeInput")
     *     ),
     *     @OA\Response(response=200, description="Recipe updated"),
     *     @OA\Response(response=404, description="Recipe not found")
     * )
     * Update the specified resource in storage.
     */
    public function update(UpdateRecipeRequest $request, string $id)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $recipe = Recipe::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);

        $recipe->update($request->validated());
        return response()->json($recipe->load('category'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/recipes/{id}",
     *     tags={"Recipes"},
     *     summary="Delete recipe",
     *     description="Delete recipe (requires authentication)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=204, description="Recipe deleted"),
     *     @OA\Response(response=404, description="Recipe not found")
     * )
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $recipe = Recipe::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);
        $recipe->delete();
        return response()->json(null, 204);
    }
}
