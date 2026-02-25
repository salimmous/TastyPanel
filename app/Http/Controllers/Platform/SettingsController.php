<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function index()
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        // Load all platform settings with defaults
        $settings = $this->getSettingsPayload();

        return view('platform.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        // Validate settings
        $validated = $request->validate([
            // Security
            'panel_allowed_ips' => 'nullable|string',
            'force_2fa' => 'nullable|boolean',
            'rate_limit_per_minute' => 'nullable|integer|min:1',

            // Backups
            'backup_retention_days' => 'nullable|integer|min:1',
            'backup_s3_enabled' => 'nullable|boolean',
            'backup_keep_local' => 'nullable|boolean',
            'backup_s3_prefix' => 'nullable|string',

            // Alerts
            'ssl_alert_days' => 'nullable|integer|min:1',
            'alerts_emails' => 'nullable|string',
            'alerts_slack_webhook' => 'nullable|url',
            'alerts_interval_hours' => 'nullable|integer|min:1',

            // Scheduler
            'http3_check_interval_minutes' => 'nullable|integer|min:1',
            'ssl_check_interval_hours' => 'nullable|integer|min:1',
            'backup_interval_hours' => 'nullable|integer|min:1',
            'analytics_interval_hours' => 'nullable|integer|min:1',
            'uptime_check_interval_minutes' => 'nullable|integer|min:1',
            'integrity_check_interval_hours' => 'nullable|integer|min:1',
            'cron_enabled' => 'nullable|boolean',
            'cron_timezone' => 'nullable|string',

            // Branding
            'brand_name' => 'nullable|string',
            'brand_logo_url' => 'nullable|url',
            'brand_favicon_url' => 'nullable|url',
            'brand_primary_color' => 'nullable|string',
            'brand_secondary_color' => 'nullable|string',
            'brand_accent_color' => 'nullable|string',
            'brand_login_message' => 'nullable|string',

            // Search
            'search_enabled' => 'nullable|boolean',
            'search_driver' => 'nullable|string',
            'search_endpoint' => 'nullable|url',
            'search_api_key' => 'nullable|string',

            // SSO
            'sso_enabled' => 'nullable|boolean',
            'sso_provider_label' => 'nullable|string',
            'sso_client_id' => 'nullable|string',
            'sso_client_secret' => 'nullable|string',
            'sso_auth_url' => 'nullable|url',
            'sso_token_url' => 'nullable|url',
            'sso_userinfo_url' => 'nullable|url',

            // SAML
            'saml_enabled' => 'nullable|boolean',
            'saml_provider_label' => 'nullable|string',
            'saml_idp_metadata_url' => 'nullable|url',
            'saml_idp_entity_id' => 'nullable|string',
        ]);

        // Merge with current settings and update
        $current = PlatformSetting::getData();
        $updated = array_merge($current, $validated);
        PlatformSetting::updateData($updated);

        return redirect()->route('platform.settings')->with('success', 'Settings updated successfully.');
    }

    public function sendTestEmail(Request $request)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $email = $request->input('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'Invalid email address for testing.');
        }

        try {
            Mail::raw('This is a test email from TastyPanel to verify your SMTP configuration.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('TastyPanel SMTP Test');
            });

            return back()->with('success', 'Test email sent successfully to ' . $email);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    private function getSettingsPayload(): array
    {
        // ... Same as PlatformController::getSettingsPayload ...
        // Re-implementing logic for now to avoid dependency
        $defaults = [
            'panel_allowed_ips' => config('services.panel.allowed_ips', ''),
            'force_2fa' => false,
            'rate_limit_per_minute' => config('services.platform.rate_limit_per_minute', 120),
            'backup_retention_days' => 7,
            'backup_s3_enabled' => false,
            'backup_keep_local' => true,
            'backup_s3_prefix' => 'tastypanel/backups',
            'ssl_alert_days' => 14,
            'alerts_emails' => '',
            'alerts_slack_webhook' => '',
            'alerts_interval_hours' => 24,
            'alerts_send_empty' => false,
            'http3_check_interval_minutes' => 30,
            'ssl_check_interval_hours' => 6,
            'backup_interval_hours' => 24,
            'analytics_interval_hours' => 6,
            'uptime_check_interval_minutes' => 5,
            'integrity_check_interval_hours' => 24,
            'cron_enabled' => true,
            'cron_timezone' => config('app.timezone', 'UTC'),
            'brand_name' => 'TastyPanel',
            'brand_logo_url' => '',
            'brand_favicon_url' => '',
            'brand_primary_color' => '#2563eb',
            'brand_secondary_color' => '#111827',
            'brand_accent_color' => '#f97316',
            'brand_login_message' => 'Admin Dashboard',
            'search_enabled' => true,
            'search_driver' => 'database',
            'search_endpoint' => '',
            'search_api_key' => '',
            'search_index_prefix' => 'tastypanel',
            'sso_enabled' => false,
            'sso_provider_label' => 'SSO',
            'sso_client_id' => '',
            'sso_client_secret' => '',
            'sso_auth_url' => '',
            'sso_token_url' => '',
            'sso_userinfo_url' => '',
            'sso_redirect_url' => config('app.url') . '/admin/sso/callback',
            'sso_scopes' => 'openid email profile',
            'sso_allowed_domains' => '',
            'sso_auto_create' => false,
            'sso_enforce' => false,
            'saml_enabled' => false,
            'saml_provider_label' => 'SAML SSO',
            'saml_idp_metadata_url' => '',
            'saml_idp_metadata_xml' => '',
            'saml_idp_entity_id' => '',
            'saml_idp_sso_url' => '',
            'saml_idp_x509' => '',
            'saml_attribute_email' => 'email',
            'saml_attribute_name' => 'name',
        ];

        return array_merge($defaults, PlatformSetting::getData());
    }
}
