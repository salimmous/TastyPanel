<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PlatformTwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $user = Auth::user();
        if (!$user?->two_factor_enabled) {
            return redirect()->route('platform.dashboard');
        }

        if ((bool) $request->session()->get('two_factor_verified', false)) {
            return redirect()->route('platform.dashboard');
        }

        return view('platform.2fa', [
            'email' => (string) ($user->email ?? ''),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $user = Auth::user();
        if (!$user?->two_factor_enabled) {
            return redirect()->route('platform.dashboard');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'min:4'],
        ]);

        if (!$user->two_factor_code || !$user->two_factor_expires_at) {
            return back()->with('error', 'Verification code expired. Please request a new code.');
        }

        if (now()->greaterThan($user->two_factor_expires_at)) {
            return back()->with('error', 'Verification code expired. Please request a new code.');
        }

        if (!Hash::check((string) $data['code'], $user->two_factor_code)) {
            return back()->with('error', 'Invalid verification code.');
        }

        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->save();

        $request->session()->put('two_factor_verified', true);

        $this->audit('2fa_verified', $user);

        return redirect()->route('platform.dashboard')->with('success', 'Two-factor verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        $user = Auth::user();
        if (!$user?->two_factor_enabled) {
            return redirect()->route('platform.dashboard');
        }

        $this->sendTwoFactorCode($user);
        $request->session()->put('two_factor_verified', false);

        $this->audit('2fa_code_resent', $user);

        return back()->with('success', 'Verification code sent.');
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

    private function audit(string $action, User $user): void
    {
        try {
            AuditLog::create([
                'user_id' => $user->id,
                'tenant_id' => null,
                'action' => $action,
                'resource_type' => 'two_factor',
                'resource_id' => $user->id,
                'description' => $action,
                'old_values' => null,
                'new_values' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'status' => 'success',
                'error_message' => null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}

