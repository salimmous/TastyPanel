<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SsoService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SsoController extends Controller
{
    public function status(SsoService $sso)
    {
        $settings = $sso->settings();
        return response()->json([
            'enabled' => (bool) ($settings['sso_enabled'] ?? false),
            'label' => $settings['sso_provider_label'] ?? 'SSO',
            'oidc_enabled' => (bool) ($settings['sso_enabled'] ?? false),
            'oidc_label' => $settings['sso_provider_label'] ?? 'SSO',
            'saml_enabled' => (bool) ($settings['saml_enabled'] ?? false),
            'saml_label' => $settings['saml_provider_label'] ?? 'SAML SSO',
        ]);
    }

    public function redirect(Request $request, SsoService $sso)
    {
        $settings = $sso->settings();
        if (empty($settings['sso_enabled'])) {
            abort(404);
        }

        $state = $sso->state();
        $request->session()->put('sso_state', $state);

        return redirect($sso->buildAuthUrl($state));
    }

    public function callback(Request $request, SsoService $sso)
    {
        $settings = $sso->settings();
        if (empty($settings['sso_enabled'])) {
            abort(404);
        }

        $state = $request->get('state');
        if (!$state || $state !== $request->session()->get('sso_state')) {
            return redirect('/login?error=sso_state');
        }
        $request->session()->forget('sso_state');

        $code = $request->get('code');
        if (!$code) {
            return redirect('/login?error=sso_code');
        }

        $tokenData = $sso->exchangeCode($code);
        $profile = $sso->fetchUser($tokenData);
        $email = $profile['email'] ?? null;
        $name = $profile['name'] ?? 'SSO User';

        if (!$email) {
            return redirect('/login?error=sso_email');
        }

        $allowed = $sso->allowedDomains();
        if (!empty($allowed)) {
            $domain = substr(strrchr($email, '@') ?: '', 1);
            if (!in_array($domain, $allowed, true)) {
                return redirect('/login?error=sso_domain');
            }
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            if (!($settings['sso_auto_create'] ?? false)) {
                return redirect('/login?error=sso_user');
            }

            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make(bin2hex(random_bytes(16)));
            $user->role = $settings['sso_default_role'] ?? AdminPermissions::ROLE_TENANT_ADMIN;
            $defaultTenantId = $settings['sso_default_tenant_id'] ?? null;
            if ($defaultTenantId) {
                $tenant = Tenant::find($defaultTenantId);
                if ($tenant) {
                    $user->tenant_id = $tenant->id;
                }
            }
            $user->is_superadmin = $user->role === AdminPermissions::ROLE_SUPERADMIN;
            $user->save();
        }

        $platformSettings = PlatformSetting::getData();
        if (($platformSettings['force_2fa'] ?? false) && AdminPermissions::isSuperadmin($user) && !$user->two_factor_enabled) {
            return redirect('/login?error=sso_2fa');
        }

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        $requires2fa = false;
        if ($user->two_factor_enabled) {
            $this->sendTwoFactorCode($user);
            $request->session()->put('two_factor_verified', false);
            $requires2fa = true;
        } else {
            $request->session()->put('two_factor_verified', true);
        }

        if ($requires2fa) {
            return redirect('/admin/2fa');
        }

        if ($user->force_password_reset) {
            return redirect('/admin/force-password');
        }

        return redirect('/admin/dashboard');
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
            // ignore
        }
    }
}
