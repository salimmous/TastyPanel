<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformSecurityController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! PlatformInstallController::isInstalled()) {
            return redirect()->route('platform.install');
        }

        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $settings = PlatformSetting::getData();
        $payload = [
            'panel_allowed_ips' => (string) ($settings['panel_allowed_ips'] ?? config('services.panel.allowed_ips', '')),
            'force_2fa' => (bool) ($settings['force_2fa'] ?? false),
            'rate_limit_per_minute' => (int) ($settings['rate_limit_per_minute'] ?? config('services.platform.rate_limit_per_minute', 120)),
        ];

        $sessions = collect();
        try {
            $sessions = DB::table('sessions')
                ->where('user_id', Auth::id())
                ->orderByDesc('last_activity')
                ->limit(20)
                ->get();
        } catch (\Throwable $e) {
            $sessions = collect();
        }

        return view('platform.security', [
            'settings' => $payload,
            'currentIp' => $request->ip(),
            'sessions' => $sessions,
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }
        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $data = $request->validate([
            'panel_allowed_ips' => ['nullable', 'string'],
            'force_2fa' => ['nullable', 'boolean'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1'],
        ]);

        $current = PlatformSetting::getData();
        $next = array_merge($current, $data);
        PlatformSetting::updateData($next);

        $this->audit('security_settings_update', [
            'panel_allowed_ips' => $data['panel_allowed_ips'] ?? null,
            'force_2fa' => isset($data['force_2fa']) ? (bool) $data['force_2fa'] : null,
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? null,
        ]);

        return back()->with('success', 'Security settings updated.');
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }
        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $user = Auth::user();
        $user->two_factor_enabled = true;
        $user->save();

        $this->audit('2fa_enabled', ['user_id' => $user->id]);

        return back()->with('success', 'Two-factor enabled for your account.');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }
        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $settings = PlatformSetting::getData();
        if (($settings['force_2fa'] ?? false) && AdminPermissions::isSuperadmin(Auth::user())) {
            return back()->with('error', 'Cannot disable 2FA while "Force 2FA" is enabled.');
        }

        $user = Auth::user();
        $user->two_factor_enabled = false;
        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->save();

        $request->session()->forget(['two_factor_verified']);

        $this->audit('2fa_disabled', ['user_id' => $user->id]);

        return back()->with('success', 'Two-factor disabled for your account.');
    }

    public function revokeOtherSessions(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }
        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $currentSession = $request->session()->getId();
        $deleted = 0;
        try {
            $deleted = DB::table('sessions')
                ->where('user_id', Auth::id())
                ->where('id', '!=', $currentSession)
                ->delete();
        } catch (\Throwable $e) {
        }

        $this->audit('sessions_revoked_other', ['count' => $deleted]);

        return back()->with('success', "Revoked {$deleted} other session(s).");
    }

    public function emergencyLock(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }
        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $data = $request->validate([
            'confirm' => ['required', 'string'],
        ]);

        if (($data['confirm'] ?? '') !== 'emergency_lock') {
            return back()->with('error', 'Confirmation phrase is invalid.');
        }

        $ip = (string) $request->ip();

        $current = PlatformSetting::getData();
        $next = array_merge($current, [
            'panel_allowed_ips' => $ip,
            'force_2fa' => true,
        ]);
        PlatformSetting::updateData($next);

        // Revoke other sessions (keep current session alive).
        $currentSession = $request->session()->getId();
        try {
            DB::table('sessions')
                ->where('user_id', Auth::id())
                ->where('id', '!=', $currentSession)
                ->delete();
        } catch (\Throwable $e) {
        }

        $this->audit('emergency_lock', ['ip' => $ip]);

        return back()->with('success', 'Emergency lock applied (Allowed IPs = current IP, Force 2FA enabled, other sessions revoked).');
    }

    private function audit(string $action, array $newValues = []): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'tenant_id' => null,
                'action' => $action,
                'resource_type' => 'platform_security',
                'resource_id' => null,
                'description' => $action,
                'old_values' => null,
                'new_values' => $newValues ?: null,
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
