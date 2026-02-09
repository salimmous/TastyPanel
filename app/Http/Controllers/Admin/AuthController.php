<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $settings = PlatformSetting::getData();
        if (($settings['sso_enforce'] ?? false) || ($settings['saml_enforce'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'SSO login is required.',
            ], 403);
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::guard('web')->user();

            if (($settings['force_2fa'] ?? false) && AdminPermissions::isSuperadmin($user) && !$user->two_factor_enabled) {
                Auth::guard('web')->logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Two-factor authentication is required for superadmins.',
                ], 403);
            }

            $requires2fa = false;
            if ($user->two_factor_enabled) {
                $this->sendTwoFactorCode($user);
                $request->session()->put('two_factor_verified', false);
                $requires2fa = true;
            } else {
                $request->session()->put('two_factor_verified', true);
            }
            
            return response()->json([
                'success' => true,
                'user' => $this->transformUser($user),
                'requires_2fa' => $requires2fa,
                'must_change_password' => (bool) $user?->force_password_reset,
                'message' => 'تم تسجيل الدخول بنجاح'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'
        ], 401);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json([
                'user' => null
            ], 401);
        }
        
        return response()->json([
            'user' => $this->transformUser($user),
            'two_factor_verified' => (bool) $request->session()->get('two_factor_verified', false),
            'tenant_mode' => (bool) config('services.tenant_mode.enabled', false),
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->forget(['two_factor_verified', 'two_factor_pending']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }

    public function requestTwoFactor(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor is not enabled.',
            ], 422);
        }

        $this->sendTwoFactorCode($user);
        $request->session()->put('two_factor_verified', false);

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent.',
        ]);
    }

    public function verifyTwoFactor(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor is not enabled.',
            ], 422);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'min:4'],
        ]);

        if (!$user->two_factor_code || !$user->two_factor_expires_at) {
            return response()->json([
                'message' => 'Verification code expired.',
            ], 422);
        }

        if (now()->greaterThan($user->two_factor_expires_at)) {
            return response()->json([
                'message' => 'Verification code expired.',
            ], 422);
        }

        if (!Hash::check($data['code'], $user->two_factor_code)) {
            return response()->json([
                'message' => 'Invalid verification code.',
            ], 422);
        }

        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->save();

        $request->session()->put('two_factor_verified', true);

        return response()->json([
            'success' => true,
            'user' => $this->transformUser($user),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->password = $data['password'];
        $user->force_password_reset = false;
        $user->save();

        return response()->json([
            'success' => true,
            'user' => $this->transformUser($user),
        ]);
    }

    private function sendTwoFactorCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $user->two_factor_code = Hash::make($code);
        $user->two_factor_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            Mail::raw("Your TastyPanel verification code is: {$code}", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Your TastyPanel verification code');
            });
        } catch (\Throwable $e) {
            // Ignore email failures in environments without mail.
        }
    }

    private function transformUser(User $user): array
    {
        $payload = $user->toArray();
        $tenantMode = (bool) config('services.tenant_mode.enabled', false);
        if ($tenantMode) {
            $payload['is_superadmin'] = false;
            $payload['role'] = $payload['role'] === 'superadmin' ? 'tenant-admin' : ($payload['role'] ?: 'tenant-admin');
        }
        $payload['app_mode'] = $tenantMode ? 'tenant' : 'platform';

        return $payload;
    }
}
