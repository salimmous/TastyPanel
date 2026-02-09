<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SetupController extends Controller
{
    public function status()
    {
        return response()->json([
            'needs_setup' => $this->needsSetup(),
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->needsSetup()) {
            return response()->json([
                'message' => 'Setup already completed.',
            ], 409);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
        ]);

        $temporaryPassword = Str::random(16);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'email_verified_at' => now(),
            'is_superadmin' => true,
            'role' => 'superadmin',
            'force_password_reset' => true,
        ]);

        $this->ensureTenantBootstrap($user);

        return response()->json([
            'success' => true,
            'temporary_password' => $temporaryPassword,
            'user' => $user,
        ], 201);
    }

    private function needsSetup(): bool
    {
        return !User::where(function ($query) {
            $query->where('role', 'superadmin')
                ->orWhere('is_superadmin', true);
        })->exists();
    }

    private function ensureTenantBootstrap(User $user): void
    {
        if (!config('services.tenant_mode.enabled', false)) {
            return;
        }

        $tenant = Tenant::query()->first();
        if (!$tenant) {
            $name = config('app.name', 'Tenant Site');
            $slug = Str::slug($name) ?: 'tenant-site';
            $tenant = Tenant::create([
                'name' => $name,
                'slug' => $slug,
                'status' => 'active',
            ]);
            $tenant->settings()->create([
                'environment' => 'production',
                'data' => [
                    'brand_name' => $name,
                    'tagline' => '',
                ],
            ]);
        }

        if (!$user->tenant_id) {
            $user->tenant_id = $tenant->id;
            $user->save();
        }
    }
}
