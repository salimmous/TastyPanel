<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SamlService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SamlController extends Controller
{
    public function login(Request $request, SamlService $saml)
    {
        if (! $saml->enabled()) {
            abort(404);
        }

        try {
            $auth = $saml->buildAuth();
        } catch (\Throwable $e) {
            return redirect('/login?error=saml_config');
        }
        $loginUrl = $auth->login(null, [], false, false, true);

        return redirect()->to($loginUrl);
    }

    public function acs(Request $request, SamlService $saml)
    {
        if (! $saml->enabled()) {
            abort(404);
        }

        try {
            $auth = $saml->buildAuth();
        } catch (\Throwable $e) {
            return redirect('/login?error=saml_config');
        }
        $auth->processResponse();
        $errors = $auth->getErrors();

        if (! empty($errors)) {
            return redirect('/login?error=saml_response');
        }

        if (! $auth->isAuthenticated()) {
            return redirect('/login?error=saml_auth');
        }

        $profile = $saml->extractUser($auth->getAttributes(), $auth->getNameId());
        $email = $profile['email'] ?? null;
        $name = $profile['name'] ?? 'SAML User';

        if (! $email) {
            return redirect('/login?error=saml_email');
        }

        $allowed = $saml->allowedDomains();
        if (! empty($allowed)) {
            $domain = substr(strrchr($email, '@') ?: '', 1);
            if (! in_array($domain, $allowed, true)) {
                return redirect('/login?error=saml_domain');
            }
        }

        $settings = PlatformSetting::getData();

        $user = User::where('email', $email)->first();
        if (! $user) {
            if (! ($settings['saml_auto_create'] ?? false)) {
                return redirect('/login?error=saml_user');
            }

            $user = new User;
            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make(bin2hex(random_bytes(16)));
            $user->role = $settings['saml_default_role'] ?? AdminPermissions::ROLE_TENANT_ADMIN;
            $defaultTenantId = $settings['saml_default_tenant_id'] ?? null;
            if ($defaultTenantId) {
                $tenant = Tenant::find($defaultTenantId);
                if ($tenant) {
                    $user->tenant_id = $tenant->id;
                }
            }
            $user->is_superadmin = $user->role === AdminPermissions::ROLE_SUPERADMIN;
            $user->save();
        }

        if (($settings['force_2fa'] ?? false) && AdminPermissions::isSuperadmin($user) && ! $user->two_factor_enabled) {
            return redirect('/login?error=saml_2fa');
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

    public function metadata(SamlService $saml)
    {
        if (! $saml->enabled()) {
            abort(404);
        }

        $metadata = $saml->buildMetadata();
        if (! empty($metadata['errors'])) {
            return response(implode(', ', $metadata['errors']), 500);
        }

        return response($metadata['xml'], 200, [
            'Content-Type' => 'text/xml',
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
            // ignore
        }
    }
}
