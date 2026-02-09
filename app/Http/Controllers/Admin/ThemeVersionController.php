<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Models\ThemeVersion;
use App\Services\ThemePackageService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ThemeVersionController extends Controller
{
    public function index(Request $request, Theme $theme)
    {
        abort_unless(AdminPermissions::canManageThemes($request->user()), 403);

        $versions = $theme->versions()->orderByDesc('id')->get();

        return response()->json([
            'data' => $versions,
        ]);
    }

    public function store(Request $request, Theme $theme, ThemePackageService $packages)
    {
        abort_unless(AdminPermissions::canManageThemes($request->user()), 403);

        $data = $request->validate([
            'version' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
            'zip' => ['required', 'file', 'mimes:zip'],
        ]);

        $package = $packages->importThemeZip($request->file('zip'), $theme->key);

        $themeVersion = ThemeVersion::create([
            'theme_id' => $theme->id,
            'version' => $data['version'] ?? null,
            'zip_path' => $package['zip_path'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        if (!empty($data['version'])) {
            $theme->version = $data['version'];
        }
        if (!empty($package['view'])) {
            $theme->view = $package['view'];
        }
        $theme->save();

        return response()->json([
            'data' => $themeVersion,
        ], 201);
    }

    public function restore(Request $request, Theme $theme, ThemeVersion $version, ThemePackageService $packages)
    {
        abort_unless(AdminPermissions::canManageThemes($request->user()), 403);

        if ($version->theme_id !== $theme->id) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($version->zip_path)) {
            return response()->json(['message' => 'Theme package not found.'], 404);
        }

        $zipPath = Storage::disk('local')->path($version->zip_path);
        $view = $packages->extractThemeZip($zipPath, $theme->key);

        if (!empty($version->version)) {
            $theme->version = $version->version;
        }
        if (!empty($view)) {
            $theme->view = $view;
        }
        $theme->save();

        return response()->json([
            'data' => $theme,
            'version' => $version,
        ]);
    }
}
