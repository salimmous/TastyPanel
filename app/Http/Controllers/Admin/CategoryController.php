<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\AdminEnvironmentResolver;
use App\Support\AdminTenantResolver;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $tenantId = AdminTenantResolver::resolveId(request());
        $environment = AdminEnvironmentResolver::resolve(request());
        $query = Category::withCount([
            'recipes' => fn ($q) => $q->where('environment', $environment),
        ])->orderBy('created_at', 'desc');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        $query->where('environment', $environment);
        $categories = $query->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $role = AdminPermissions::role($request->user());
        if (!AdminPermissions::canManageContent($request->user()) || $role === AdminPermissions::ROLE_WRITER) {
            abort(403);
        }
        $environment = AdminEnvironmentResolver::resolve($request);
        $tenantId = AdminTenantResolver::resolveId($request);
        $targetTenantId = $request->input('tenant_id') ?? $tenantId;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|url',
            'tenant_id' => 'nullable|exists:tenants,id',
            'slug' => [
                'nullable',
                Rule::unique('categories', 'slug')
                    ->where('tenant_id', $targetTenantId)
                    ->where('environment', $environment),
            ],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['tenant_id'] = AdminTenantResolver::enforceTenantId(
            $request,
            $validated['tenant_id'] ?? $tenantId
        );
        $validated['environment'] = $environment;

        $category = Category::create($validated);
        return response()->json($category, 201);
    }

    public function show($id)
    {
        $tenantId = AdminTenantResolver::resolveId(request());
        $environment = AdminEnvironmentResolver::resolve(request());
        $category = Category::with([
            'recipes' => fn ($q) => $q->where('environment', $environment),
        ])
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $role = AdminPermissions::role($request->user());
        if (!AdminPermissions::canManageContent($request->user()) || $role === AdminPermissions::ROLE_WRITER) {
            abort(403);
        }
        $tenantId = AdminTenantResolver::resolveId($request);
        $environment = AdminEnvironmentResolver::resolve($request);
        $category = Category::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|url',
            'tenant_id' => 'nullable|exists:tenants,id',
            'slug' => [
                'nullable',
                Rule::unique('categories', 'slug')
                    ->where('tenant_id', $category->tenant_id)
                    ->where('environment', $environment)
                    ->ignore($category->id),
            ],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        if (array_key_exists('tenant_id', $validated)) {
            $validated['tenant_id'] = AdminTenantResolver::enforceTenantId(
                $request,
                $validated['tenant_id'] ?: AdminTenantResolver::resolveId($request)
            );
        }

        $category->update($validated);
        return response()->json($category);
    }

    public function destroy($id)
    {
        if (!AdminPermissions::canDeleteContent(request()->user())) {
            abort(403);
        }
        $tenantId = AdminTenantResolver::resolveId(request());
        $environment = AdminEnvironmentResolver::resolve(request());
        $category = Category::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
