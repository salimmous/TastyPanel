<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $query = Category::with([
            'recipes' => fn ($q) => $q->where('environment', $environment),
        ]);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        $query->where('environment', $environment);
        $categories = $query->get();
        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $validated = $request->validate([
            'slug' => 'required',
            'name' => 'required',
            'image' => 'required',
            'description' => 'required',
        ]);

        if ($tenantId) {
            $exists = Category::where('tenant_id', $tenantId)
                ->where('environment', $environment)
                ->where('slug', $validated['slug'])
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Slug already exists.'], 422);
            }
        }

        if ($tenantId) {
            $validated['tenant_id'] = $tenantId;
        }
        $validated['environment'] = $environment;

        $category = Category::create($validated);
        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $category = Category::where('slug', $slug)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->with([
                'recipes' => fn ($q) => $q->where('environment', $environment),
            ])
            ->firstOrFail();
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $category = Category::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);

        $validated = $request->validate([
            'slug' => 'sometimes',
            'name' => 'sometimes',
            'image' => 'sometimes',
            'description' => 'sometimes',
        ]);

        if (!empty($validated['slug']) && $tenantId) {
            $exists = Category::where('tenant_id', $tenantId)
                ->where('environment', $environment)
                ->where('slug', $validated['slug'])
                ->where('id', '!=', $category->id)
                ->exists();
            if ($exists) {
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
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $category = Category::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);
        $category->delete();
        return response()->json(null, 204);
    }
}
