<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SystemUser;
use App\Models\Tenant;
use App\Services\SystemUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        // Assuming 2 tabs or sections
        $users = User::paginate(20);
        $systemUsers = SystemUser::with('tenant')->paginate(20);

        return view('platform.users.index', compact('users', 'systemUsers'));
    }

    public function create()
    {
        if (!Auth::check()) return redirect()->route('platform.login');
        return view('platform.users.create');
    }

    public function store(Request $request)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        // Logic for creating panel admin user
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,superadmin,viewer',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_superadmin' => $validated['role'] === 'superadmin',
        ]);

        return redirect()->route('platform.users.index')->with('success', 'User created successfully.');
    }

    public function destroy(User $user)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Cannot delete yourself.');
        }

        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    // System User Management methods

    public function createSystemUser()
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $tenants = Tenant::all();
        return view('platform.users.create_system_user', compact('tenants'));
    }

    public function storeSystemUser(Request $request, SystemUserService $service)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $validated = $request->validate([
            'username' => 'required|string|max:64|unique:system_users,username',
            'password' => 'nullable|string|min:8',
            'tenant_id' => 'nullable|exists:tenants,id',
            'ssh_key' => 'nullable|string',
        ]);

        try {
            $service->create($validated);
            return redirect()->route('platform.users.index')->with('success', 'System user created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['username' => $e->getMessage()]);
        }
    }

    public function destroySystemUser(SystemUser $user, SystemUserService $service)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        try {
            $service->delete($user);
            return back()->with('success', 'System user deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
