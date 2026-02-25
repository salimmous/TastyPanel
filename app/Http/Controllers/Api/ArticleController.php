<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $environment = $this->getEnvironment();
        $query = Article::query();

        $this->scopeWithTenant($query);

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
        $validated = $request->validate([
            'slug' => 'required',
            'title' => 'required',
            'description' => 'required',
            'image' => 'required',
        ]);

        if (! $this->isSlugUnique(Article::class, $validated['slug'])) {
            return response()->json(['message' => 'Slug already exists.'], 422);
        }

        $validated = $this->applyTenantData($validated);

        $article = Article::create($validated);

        return response()->json($article, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $environment = $this->getEnvironment();
        $query = Article::where('slug', $slug);

        $this->scopeWithTenant($query);

        if ($environment === 'production') {
            $query->where('status', 'published');
        }

        $article = $query->firstOrFail();

        return response()->json($article);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $query = Article::query();
        $this->scopeWithTenant($query);
        $article = $query->findOrFail($id);

        $validated = $request->validate([
            'slug' => 'sometimes',
            'title' => 'sometimes',
            'description' => 'sometimes',
            'image' => 'sometimes',
        ]);

        if (! empty($validated['slug'])) {
            if (! $this->isSlugUnique(Article::class, $validated['slug'], $article->id)) {
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
        $query = Article::query();
        $this->scopeWithTenant($query);
        $article = $query->findOrFail($id);
        $article->delete();

        return response()->json(null, 204);
    }
}
