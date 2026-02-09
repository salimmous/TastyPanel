<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Category;
use App\Support\AdminEnvironmentResolver;
use App\Support\AdminTenantResolver;
use App\Support\AdminPermissions;
use App\Support\ContentWorkflow;
use App\Services\TenantLimitService;
use App\Services\WebhookService;
use App\Services\ContentScoringService;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Recipe::with('category');
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

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $recipes = $query->orderBy('created_at', 'desc')->paginate(20);
        return response()->json($recipes);
    }

    public function store(Request $request)
    {
        if (!AdminPermissions::canManageContent($request->user())) {
            abort(403);
        }
        $environment = AdminEnvironmentResolver::resolve($request);
        $resolvedTenantId = AdminTenantResolver::resolveId($request);
        $targetTenantId = $request->input('tenant_id') ?? $resolvedTenantId;
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|url',
            'prep_time' => 'nullable|integer',
            'cook_time' => 'nullable|integer',
            'servings' => 'nullable|integer',
            'ingredients' => 'nullable|array',
            'instructions' => 'nullable|array',
            'nutrition' => 'nullable|array',
            'tenant_id' => 'nullable|exists:tenants,id',
            'status' => ['nullable', 'string', Rule::in(ContentWorkflow::statuses())],
            'slug' => [
                'nullable',
                Rule::unique('recipes', 'slug')
                    ->where('tenant_id', $targetTenantId)
                    ->where('environment', $environment),
            ],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $category = Category::where('environment', $environment)->find($validated['category_id']);
        if (!$category) {
            return response()->json([
                'message' => 'Category does not belong to selected environment.',
            ], 422);
        }
        if ($resolvedTenantId && $category && $category->tenant_id && $category->tenant_id !== $resolvedTenantId) {
            return response()->json([
                'message' => 'Category does not belong to selected tenant.',
            ], 422);
        }
        if (!array_key_exists('tenant_id', $validated) || !$validated['tenant_id']) {
            $validated['tenant_id'] = AdminTenantResolver::enforceTenantId(
                $request,
                $category?->tenant_id ?? $resolvedTenantId
            );
        }
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
                if (!$limits->canCreatePost($tenant, $environment)) {
                    return response()->json([
                        'message' => 'Post limit reached for this tenant.',
                    ], 422);
                }
            }
        }

        $recipe = new Recipe($validated);
        $score = app(ContentScoringService::class)->score($recipe->title ?? '', $this->recipeText($recipe));
        $recipe->fill($score);
        ContentWorkflow::applyStatusTimestamps($recipe, $status);
        $recipe->save();

        if ($recipe->tenant_id) {
            $tenant = Tenant::find($recipe->tenant_id);
            if ($tenant) {
                app(WebhookService::class)->dispatchEvent($tenant, 'recipe.created', $recipe->toArray());
            }
        }

        return response()->json($recipe->load('category'), 201);
    }

    public function show($id)
    {
        $tenantId = AdminTenantResolver::resolveId(request());
        $environment = AdminEnvironmentResolver::resolve(request());
        $recipe = Recipe::with('category')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);
        return response()->json($recipe);
    }

    public function update(Request $request, $id)
    {
        if (!AdminPermissions::canManageContent($request->user())) {
            abort(403);
        }
        $tenantId = AdminTenantResolver::resolveId($request);
        $environment = AdminEnvironmentResolver::resolve($request);
        $recipe = Recipe::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|url',
            'prep_time' => 'nullable|integer',
            'cook_time' => 'nullable|integer',
            'servings' => 'nullable|integer',
            'ingredients' => 'nullable|array',
            'instructions' => 'nullable|array',
            'nutrition' => 'nullable|array',
            'tenant_id' => 'nullable|exists:tenants,id',
            'status' => ['nullable', 'string', Rule::in(ContentWorkflow::statuses())],
            'slug' => [
                'nullable',
                Rule::unique('recipes', 'slug')
                    ->where('tenant_id', $recipe->tenant_id)
                    ->where('environment', $environment)
                    ->ignore($recipe->id),
            ],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $category = Category::where('environment', $environment)->find($validated['category_id']);
        if (!$category) {
            return response()->json([
                'message' => 'Category does not belong to selected environment.',
            ], 422);
        }
        if ($tenantId && $category && $category->tenant_id && $category->tenant_id !== $tenantId) {
            return response()->json([
                'message' => 'Category does not belong to selected tenant.',
            ], 422);
        }
        if (array_key_exists('tenant_id', $validated) && !$validated['tenant_id']) {
            $validated['tenant_id'] = AdminTenantResolver::resolveId($request);
        }

        $status = $recipe->status ?? ContentWorkflow::STATUS_DRAFT;
        if (array_key_exists('status', $validated)) {
            $status = ContentWorkflow::normalizeStatus($validated['status'], $request->user());
        }
        unset($validated['status']);

        $recipe->fill($validated);
        $score = app(ContentScoringService::class)->score($recipe->title ?? '', $this->recipeText($recipe));
        $recipe->fill($score);
        $recipe->status = $status;
        ContentWorkflow::applyStatusTimestamps($recipe, $status);
        $recipe->save();

        if ($recipe->tenant_id) {
            $tenant = Tenant::find($recipe->tenant_id);
            if ($tenant) {
                app(WebhookService::class)->dispatchEvent($tenant, 'recipe.updated', $recipe->toArray());
            }
        }

        return response()->json($recipe->load('category'));
    }

    public function destroy($id)
    {
        if (!AdminPermissions::canDeleteContent(request()->user())) {
            abort(403);
        }
        $tenantId = AdminTenantResolver::resolveId(request());
        $environment = AdminEnvironmentResolver::resolve(request());
        $recipe = Recipe::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);
        $payload = $recipe->toArray();
        $tenant = $recipe->tenant_id ? Tenant::find($recipe->tenant_id) : null;
        $recipe->delete();

        if ($tenant) {
            app(WebhookService::class)->dispatchEvent($tenant, 'recipe.deleted', $payload);
        }
        return response()->json(['message' => 'Recipe deleted successfully']);
    }

    private function recipeText(Recipe $recipe): string
    {
        $parts = [];
        if (!empty($recipe->description)) {
            $parts[] = $recipe->description;
        }
        if (!empty($recipe->ingredients) && is_array($recipe->ingredients)) {
            $parts[] = implode(' ', $recipe->ingredients);
        }
        if (!empty($recipe->instructions) && is_array($recipe->instructions)) {
            $parts[] = implode(' ', array_map(function ($item) {
                if (is_string($item)) {
                    return $item;
                }
                if (is_array($item)) {
                    return implode(' ', $item);
                }
                return '';
            }, $recipe->instructions));
        }
        return implode("\n", array_filter($parts));
    }
}
