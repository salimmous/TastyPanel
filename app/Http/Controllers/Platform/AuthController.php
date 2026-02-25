<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PlatformSetting;
use App\Models\AuditLog;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('platform.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if (!AdminPermissions::isSuperadmin($user)) {
                Auth::logout();
                return back()->withErrors(['email' => 'Unauthorized access.']);
            }

            $settings = PlatformSetting::getData();
            if (($settings['force_2fa'] ?? false) && !$user->two_factor_enabled) {
                Auth::logout();
                return back()->withErrors(['email' => 'Two-factor authentication is required. Enable 2FA for your account first.']);
            }

            $request->session()->regenerate();

            if ($user->two_factor_enabled) {
                $this->sendTwoFactorCode($user);
                $request->session()->put('two_factor_verified', false);
                $this->auditAuthEvent('login_2fa_challenge', $user, $request);
                return redirect()->route('platform.2fa');
            }

            $request->session()->put('two_factor_verified', true);
            $this->auditAuthEvent('login', $user, $request);
            return redirect()->intended(route('platform.dashboard'));

        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        Auth::logout();
        $request->session()->forget(['two_factor_verified']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($user) {
            $this->auditAuthEvent('logout', $user, $request);
        }
        return redirect()->route('platform.login');
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

    private function auditAuthEvent(string $action, User $user, Request $request): void
    {
        try {
            AuditLog::create([
                'user_id' => $user->id,
                'tenant_id' => null,
                'action' => $action,
                'resource_type' => 'user',
                'resource_id' => $user->id,
                'description' => $action,
                'old_values' => null,
                'new_values' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => 'success',
                'error_message' => null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
