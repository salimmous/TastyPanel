<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Models\ThemeVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\AdminPermissions;
use App\Services\ThemePackageService;

class ThemeController extends Controller
{
    public function index()
    {
        $themes = Theme::orderBy('name')->get();

        return response()->json([
            'data' => $themes,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(AdminPermissions::canManageThemes($request->user()), 403);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:themes,key'],
            'name' => ['required', 'string', 'max:160'],
            'view' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable'],
            'author' => ['nullable', 'string', 'max:160'],
            'version' => ['nullable', 'string', 'max:60'],
            'preview_image' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['nullable', 'boolean'],
            'is_marketplace' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $theme = Theme::create([
            'key' => $data['key'],
            'name' => $data['name'],
            'view' => $data['view'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'tags' => $this->normalizeTags($data['tags'] ?? null),
            'author' => $data['author'] ?? null,
            'version' => $data['version'] ?? null,
            'preview_image' => $data['preview_image'] ?? null,
            'is_featured' => $data['is_featured'] ?? false,
            'is_marketplace' => $data['is_marketplace'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'data' => $theme,
        ], 201);
    }

    public function update(Request $request, Theme $theme)
    {
        abort_unless(AdminPermissions::canManageThemes($request->user()), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'view' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable'],
            'author' => ['nullable', 'string', 'max:160'],
            'version' => ['nullable', 'string', 'max:60'],
            'preview_image' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['nullable', 'boolean'],
            'is_marketplace' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('tags', $data)) {
            $data['tags'] = $this->normalizeTags($data['tags']);
        }

        $theme->fill($data);
        $theme->save();

        return response()->json([
            'data' => $theme,
        ]);
    }

    public function upload(Request $request, ThemePackageService $packages)
    {
        abort_unless(AdminPermissions::canManageThemes($request->user()), 403);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:themes,key'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable'],
            'author' => ['nullable', 'string', 'max:160'],
            'version' => ['nullable', 'string', 'max:60'],
            'is_featured' => ['nullable', 'boolean'],
            'is_marketplace' => ['nullable', 'boolean'],
            'zip' => ['required', 'file', 'mimes:zip'],
            'preview' => ['nullable', 'image', 'max:2048'],
        ]);

        $key = Str::slug($data['key']);
        try {
            $package = $packages->importThemeZip($request->file('zip'), $key);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $previewPath = null;
        if ($request->hasFile('preview')) {
            $previewPath = $request->file('preview')->storeAs(
                'theme-previews',
                $key . '-' . time() . '.' . $request->file('preview')->getClientOriginalExtension(),
                'public'
            );
        }

        $theme = Theme::create([
            'key' => $key,
            'name' => $data['name'],
            'view' => $package['view'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'tags' => $this->normalizeTags($data['tags'] ?? null),
            'author' => $data['author'] ?? null,
            'version' => $data['version'] ?? null,
            'preview_image' => $previewPath ? '/storage/' . $previewPath : null,
            'is_featured' => $data['is_featured'] ?? false,
            'is_marketplace' => $data['is_marketplace'] ?? false,
            'is_active' => true,
        ]);

        ThemeVersion::create([
            'theme_id' => $theme->id,
            'version' => $data['version'] ?? null,
            'zip_path' => $package['zip_path'],
            'notes' => null,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'data' => $theme,
        ], 201);
    }

    private function normalizeTags($value): ?array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), fn ($tag) => $tag !== ''));
        }

        if (is_string($value)) {
            $parts = array_map('trim', explode(',', $value));
            $parts = array_values(array_filter($parts, fn ($tag) => $tag !== ''));
            return $parts ?: null;
        }

        return null;
    }
}
