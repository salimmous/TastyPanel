<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $query = Article::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        $query->where('environment', $environment);
        if ($environment === 'production') {
            $query->where('status', 'published');
        }
        $articles = $query->get();
        return response()->json($articles);
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
            'title' => 'required',
            'description' => 'required',
            'image' => 'required',
        ]);

        if ($tenantId) {
            $exists = Article::where('tenant_id', $tenantId)
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

        $article = Article::create($validated);
        return response()->json($article, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $article = Article::where('slug', $slug)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->when($environment === 'production', fn ($q) => $q->where('status', 'published'))
            ->firstOrFail();
        return response()->json($article);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $article = Article::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);

        $validated = $request->validate([
            'slug' => 'sometimes',
            'title' => 'sometimes',
            'description' => 'sometimes',
            'image' => 'sometimes',
        ]);

        if (!empty($validated['slug']) && $tenantId) {
            $exists = Article::where('tenant_id', $tenantId)
                ->where('environment', $environment)
                ->where('slug', $validated['slug'])
                ->where('id', '!=', $article->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Slug already exists.'], 422);
            }
        }

        $article->update($validated);
        return response()->json($article);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $article = Article::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);
        $article->delete();
        return response()->json(null, 204);
    }
}
