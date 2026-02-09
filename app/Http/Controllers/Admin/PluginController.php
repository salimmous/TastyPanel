<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(AdminPermissions::isSuperadmin($request->user()), 403);

        $plugins = Plugin::orderBy('name')->get();

        return response()->json([
            'data' => $plugins,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(AdminPermissions::isSuperadmin($request->user()), 403);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:120', 'unique:plugins,key'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plugin = Plugin::create([
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'config' => $data['config'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'data' => $plugin,
        ], 201);
    }

    public function update(Request $request, Plugin $plugin)
    {
        abort_unless(AdminPermissions::isSuperadmin($request->user()), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plugin->fill($data);
        $plugin->save();

        return response()->json([
            'data' => $plugin,
        ]);
    }

    public function destroy(Request $request, Plugin $plugin)
    {
        abort_unless(AdminPermissions::isSuperadmin($request->user()), 403);

        $plugin->delete();

        return response()->json([
            'message' => 'Plugin deleted.',
        ]);
    }
}
