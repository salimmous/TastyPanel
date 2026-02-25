<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Tenant;
use App\Services\ContentScoringService;
use App\Services\TenantLimitService;
use App\Services\WebhookService;
use App\Support\AdminEnvironmentResolver;
use App\Support\AdminPermissions;
use App\Support\AdminTenantResolver;
use App\Support\ContentWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::query();
        $tenantId = AdminTenantResolver::resolveId($request);
        $environment = AdminEnvironmentResolver::resolve($request);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        $query->where('environment', $environment);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $articles = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($articles);
    }

    public function store(Request $request)
    {
        if (! AdminPermissions::canManageContent($request->user())) {
            abort(403);
        }
        $environment = AdminEnvironmentResolver::resolve($request);
        $tenantId = AdminTenantResolver::resolveId($request);
        $targetTenantId = $request->input('tenant_id') ?? $tenantId;
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|url',
            'tenant_id' => 'nullable|exists:tenants,id',
            'status' => ['nullable', 'string', Rule::in(ContentWorkflow::statuses())],
            'slug' => [
                'nullable',
                Rule::unique('articles', 'slug')
                    ->where('tenant_id', $targetTenantId)
                    ->where('environment', $environment),
            ],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['tenant_id'] = AdminTenantResolver::enforceTenantId(
            $request,
            $validated['tenant_id'] ?? $tenantId
        );
        $validated['environment'] = $environment;
        $requestedStatus = $validated['status'] ?? null;
        $status = $requestedStatus
            ? ContentWorkflow::normalizeStatus($requestedStatus, $request->user())
            : (AdminPermissions::canPublishContent($request->user())
                ? ContentWorkflow::STATUS_PUBLISHED
                : ContentWorkflow::STATUS_DRAFT);
        $validated['status'] = $status;

        if ($validated['tenant_id']) {
            $tenant = Tenant::find($validated['tenant_id']);
            if ($tenant) {
                $limits = app(TenantLimitService::class);
                if (! $limits->canCreatePost($tenant, $environment)) {
                    return response()->json([
                        'message' => 'Post limit reached for this tenant.',
                    ], 422);
                }
            }
        }

        $article = new Article($validated);
        $score = app(ContentScoringService::class)->score($article->title ?? '', $article->description ?? '');
        $article->fill($score);
        ContentWorkflow::applyStatusTimestamps($article, $status);
        $article->save();

        if ($article->tenant_id) {
            $tenant = Tenant::find($article->tenant_id);
            if ($tenant) {
                app(WebhookService::class)->dispatchEvent($tenant, 'article.created', $article->toArray());
            }
        }

        return response()->json($article, 201);
    }

    public function show($id)
    {
        $tenantId = AdminTenantResolver::resolveId(request());
        $environment = AdminEnvironmentResolver::resolve(request());
        $article = Article::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);

        return response()->json($article);
    }

    public function update(Request $request, $id)
    {
        if (! AdminPermissions::canManageContent($request->user())) {
            abort(403);
        }
        $tenantId = AdminTenantResolver::resolveId($request);
        $environment = AdminEnvironmentResolver::resolve($request);
        $article = Article::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|url',
            'tenant_id' => 'nullable|exists:tenants,id',
            'status' => ['nullable', 'string', Rule::in(ContentWorkflow::statuses())],
            'slug' => [
                'nullable',
                Rule::unique('articles', 'slug')
                    ->where('tenant_id', $article->tenant_id)
                    ->where('environment', $environment)
                    ->ignore($article->id),
            ],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        if (array_key_exists('tenant_id', $validated) && ! $validated['tenant_id']) {
            $validated['tenant_id'] = AdminTenantResolver::resolveId($request);
        }

        $status = $article->status ?? ContentWorkflow::STATUS_DRAFT;
        if (array_key_exists('status', $validated)) {
            $status = ContentWorkflow::normalizeStatus($validated['status'], $request->user());
        }
        unset($validated['status']);

        $article->fill($validated);
        $score = app(ContentScoringService::class)->score($article->title ?? '', $article->description ?? '');
        $article->fill($score);
        $article->status = $status;
        ContentWorkflow::applyStatusTimestamps($article, $status);
        $article->save();

        if ($article->tenant_id) {
            $tenant = Tenant::find($article->tenant_id);
            if ($tenant) {
                app(WebhookService::class)->dispatchEvent($tenant, 'article.updated', $article->toArray());
            }
        }

        return response()->json($article);
    }

    public function destroy($id)
    {
        if (! AdminPermissions::canDeleteContent(request()->user())) {
            abort(403);
        }
        $tenantId = AdminTenantResolver::resolveId(request());
        $environment = AdminEnvironmentResolver::resolve(request());
        $article = Article::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);
        $payload = $article->toArray();
        $tenant = $article->tenant_id ? Tenant::find($article->tenant_id) : null;
        $article->delete();

        if ($tenant) {
            app(WebhookService::class)->dispatchEvent($tenant, 'article.deleted', $payload);
        }

        return response()->json(['message' => 'Article deleted successfully']);
    }
}
