<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {
    }

    /**
     * List all roles
     */
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->orderByDesc('level')
            ->get();

        return view('platform.roles.index', compact('roles'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $permissions = Permission::getGrouped();
        return view('platform.roles.form', compact('permissions'));
    }

    /**
     * Show edit form
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::getGrouped();
        return view('platform.roles.form', compact('role', 'permissions'));
    }

    /**
     * Create role
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name', 'regex:/^[a-z_]+$/'],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'level' => ['integer', 'min:0', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'] ?? 0,
            'is_system' => false,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('platform.roles.index')->with('success', 'Role created successfully.');
    }

    /**
     * Update role
     */
    public function update(Request $request, Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'Cannot modify system role.');
        }

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'level' => ['integer', 'min:0', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? $role->description,
            'level' => $validated['level'] ?? $role->level,
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('platform.roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Delete role
     */
    public function destroy(Role $role)
    {
        if ($role->is_system) {
             return back()->with('error', 'Cannot delete system role.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role assigned to users.');
        }

        $role->delete();

        return redirect()->route('platform.roles.index')->with('success', 'Role deleted successfully.');
    }
}
