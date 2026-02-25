<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantMailEvent;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class TenantMailService
{
    public function updateTenantSettings(Tenant $tenant, array $config): Tenant
    {
        $errors = $this->validateSmtpConfig($config);
        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'mail' => $errors,
            ]);
        }

        $tenant->mail_driver = (string) ($config['mail_driver'] ?? ($tenant->mail_driver ?: 'smtp'));
        $tenant->mail_host = (string) ($config['mail_host'] ?? $tenant->mail_host);
        $tenant->mail_port = (int) ($config['mail_port'] ?? ($tenant->mail_port ?: 587));
        $tenant->mail_username = (string) ($config['mail_username'] ?? $tenant->mail_username);

        if (array_key_exists('mail_password', $config)) {
            $password = trim((string) $config['mail_password']);
            $tenant->mail_password = $password !== '' ? encrypt($password) : null;
        }

        $encryption = $config['mail_encryption'] ?? $tenant->mail_encryption;
        $tenant->mail_encryption = $encryption === 'none' ? null : (string) $encryption;
        $tenant->mail_from_address = (string) ($config['mail_from_address'] ?? $tenant->mail_from_address);
        $tenant->mail_from_name = (string) ($config['mail_from_name'] ?? $tenant->mail_from_name ?: $tenant->name);
        $tenant->mail_local_enabled = (bool) ($config['mail_local_enabled'] ?? $tenant->mail_local_enabled ?? true);
        $tenant->mail_provider = (string) ($config['mail_provider'] ?? $tenant->mail_provider ?: 'local');
        $tenant->mail_daily_limit = max(1, (int) ($config['mail_daily_limit'] ?? $tenant->mail_daily_limit ?: config('services.mail.default_daily_limit', 500)));
        $tenant->mail_per_minute_limit = max(1, (int) ($config['mail_per_minute_limit'] ?? $tenant->mail_per_minute_limit ?: config('services.mail.default_per_minute_limit', 30)));
        $tenant->mail_configured = (bool) ($config['mail_configured'] ?? true);
        $tenant->save();

        return $tenant->fresh();
    }

    public function settingsPayload(Tenant $tenant): array
    {
        return [
            'mail_driver' => $tenant->mail_driver,
            'mail_host' => $tenant->mail_host,
            'mail_port' => $tenant->mail_port,
            'mail_username' => $tenant->mail_username,
            'has_password' => ! empty($tenant->mail_password),
            'mail_encryption' => $tenant->mail_encryption ?: 'none',
            'mail_from_address' => $tenant->mail_from_address,
            'mail_from_name' => $tenant->mail_from_name,
            'mail_configured' => (bool) $tenant->mail_configured,
            'mail_local_enabled' => (bool) ($tenant->mail_local_enabled ?? false),
            'mail_provider' => $tenant->mail_provider ?: 'local',
            'mail_daily_limit' => (int) ($tenant->mail_daily_limit ?: config('services.mail.default_daily_limit', 500)),
            'mail_per_minute_limit' => (int) ($tenant->mail_per_minute_limit ?: config('services.mail.default_per_minute_limit', 30)),
        ];
    }

    /**
     * Configure mailer for specific tenant
     */
    public function configureTenantMailer(Tenant $tenant): void
    {
        if (! $tenant->mail_configured) {
            throw new \RuntimeException('Tenant email is not configured');
        }

        Config::set([
            'mail.default' => 'tenant_smtp',
            'mail.mailers.tenant_smtp' => [
                'transport' => $tenant->mail_driver,
                'host' => $tenant->mail_host,
                'port' => $tenant->mail_port,
                'username' => $tenant->mail_username,
                'password' => $tenant->mail_password ? decrypt($tenant->mail_password) : null,
                'encryption' => $tenant->mail_encryption ?: null,
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN'),
            ],
            'mail.from' => [
                'address' => $tenant->mail_from_address ? $tenant->mail_from_address : ('noreply@'.($tenant->domains()->where('is_primary', true)->value('hostname') ?: 'localhost')),
                'name' => $tenant->mail_from_name ? $tenant->mail_from_name : $tenant->name,
            ],
        ]);

        // Clear any cached mail manager instance
        app()->forgetInstance('mail.manager');
        app()->forgetInstance(MailManager::class);
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(Tenant $tenant, object $user): bool
    {
        try {
            $gate = app(TenantMailGuardService::class)->checkAndTrack($tenant, 1);
            if (! ($gate['allowed'] ?? true)) {
                $this->logEvent($tenant, [
                    'event_type' => 'welcome_email',
                    'recipient' => (string) ($user->email ?? ''),
                    'status' => 'throttled',
                    'response' => $gate['reason'] ?? 'mail_rate_limit_exceeded',
                    'meta' => ['usage' => $gate['usage'] ?? []],
                ]);

                return false;
            }

            $this->configureTenantMailer($tenant);

            Mail::send('emails.welcome', [
                'tenant' => $tenant,
                'user' => $user,
                'loginUrl' => "https://{$tenant->primary_domain}/login",
            ], function ($message) use ($user, $tenant) {
                $brandName = $tenant->brand_name ?: $tenant->name;
                $message->to($user->email, $user->name)
                    ->subject("Welcome to {$brandName}!");
            });

            Log::info('Welcome email sent', [
                'tenant' => $tenant->id,
                'user' => $user->email,
            ]);
            $this->logEvent($tenant, [
                'event_type' => 'welcome_email',
                'recipient' => (string) ($user->email ?? ''),
                'status' => 'success',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'tenant' => $tenant->id,
                'user' => $user->email,
                'error' => $e->getMessage(),
            ]);
            $this->logEvent($tenant, [
                'event_type' => 'welcome_email',
                'recipient' => (string) ($user->email ?? ''),
                'status' => 'failed',
                'response' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(Tenant $tenant, object $user, string $resetToken): bool
    {
        try {
            $gate = app(TenantMailGuardService::class)->checkAndTrack($tenant, 1);
            if (! ($gate['allowed'] ?? true)) {
                $this->logEvent($tenant, [
                    'event_type' => 'password_reset',
                    'recipient' => (string) ($user->email ?? ''),
                    'status' => 'throttled',
                    'response' => $gate['reason'] ?? 'mail_rate_limit_exceeded',
                    'meta' => ['usage' => $gate['usage'] ?? []],
                ]);

                return false;
            }

            $this->configureTenantMailer($tenant);

            $resetUrl = "https://{$tenant->primary_domain}/reset-password?token={$resetToken}";

            Mail::send('emails.password-reset', [
                'tenant' => $tenant,
                'user' => $user,
                'resetUrl' => $resetUrl,
            ], function ($message) use ($user, $tenant) {
                $brandName = $tenant->brand_name ?: $tenant->name;
                $message->to($user->email, $user->name)
                    ->subject("Password Reset - {$brandName}");
            });

            Log::info('Password reset email sent', [
                'tenant' => $tenant->id,
                'user' => $user->email,
            ]);
            $this->logEvent($tenant, [
                'event_type' => 'password_reset',
                'recipient' => (string) ($user->email ?? ''),
                'status' => 'success',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'tenant' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            $this->logEvent($tenant, [
                'event_type' => 'password_reset',
                'recipient' => (string) ($user->email ?? ''),
                'status' => 'failed',
                'response' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send test email
     */
    public function sendTestEmail(Tenant $tenant, string $toEmail): bool
    {
        $result = $this->sendTestEmailWithResult($tenant, $toEmail);
        if (! ($result['success'] ?? false) && ! empty($result['exception'])) {
            throw $result['exception'];
        }

        return (bool) ($result['success'] ?? false);
    }

    public function sendTestEmailWithResult(Tenant $tenant, string $toEmail): array
    {
        $toEmail = trim($toEmail);
        $gate = app(TenantMailGuardService::class)->checkAndTrack($tenant, 1);
        if (! ($gate['allowed'] ?? true)) {
            $reason = $gate['reason'] ?? 'mail_rate_limit_exceeded';
            $this->logEvent($tenant, [
                'event_type' => 'smtp_test',
                'recipient' => $toEmail,
                'status' => 'throttled',
                'response' => $reason,
                'meta' => ['usage' => $gate['usage'] ?? []],
            ]);

            return [
                'success' => false,
                'message' => 'Rate limit reached for tenant mail sending.',
                'reason' => $reason,
                'usage' => $gate['usage'] ?? [],
            ];
        }

        try {
            $this->configureTenantMailer($tenant);

            Mail::raw("This is a test email from {$tenant->name}. Your SMTP configuration is working correctly!", function ($message) use ($toEmail, $tenant) {
                $message->to($toEmail)
                    ->subject("Test Email - {$tenant->name}");
            });

            $this->logEvent($tenant, [
                'event_type' => 'smtp_test',
                'recipient' => $toEmail,
                'status' => 'success',
                'meta' => ['usage' => $gate['usage'] ?? []],
            ]);

            return [
                'success' => true,
                'message' => 'Test email sent.',
                'usage' => $gate['usage'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Test email failed', [
                'tenant' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            $this->logEvent($tenant, [
                'event_type' => 'smtp_test',
                'recipient' => $toEmail,
                'status' => 'failed',
                'response' => $e->getMessage(),
                'meta' => ['usage' => $gate['usage'] ?? []],
            ]);

            return [
                'success' => false,
                'message' => 'SMTP test failed.',
                'error' => $e->getMessage(),
                'usage' => $gate['usage'] ?? [],
                'exception' => $e,
            ];
        }
    }

    /**
     * Validate SMTP configuration
     */
    public function validateSmtpConfig(array $config): array
    {
        $errors = [];

        if (empty($config['mail_host'])) {
            $errors[] = 'SMTP host is required';
        }

        if (empty($config['mail_port'])) {
            $errors[] = 'SMTP port is required';
        } elseif ((int) $config['mail_port'] <= 0 || (int) $config['mail_port'] > 65535) {
            $errors[] = 'SMTP port is invalid';
        }

        if (empty($config['mail_from_address'])) {
            $errors[] = 'From email address is required';
        } elseif (! filter_var($config['mail_from_address'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'From email address is invalid';
        }

        return $errors;
    }

    private function logEvent(Tenant $tenant, array $payload): void
    {
        try {
            TenantMailEvent::create([
                'tenant_id' => $tenant->id,
                'direction' => 'outbound',
                'event_type' => (string) ($payload['event_type'] ?? 'send'),
                'recipient' => isset($payload['recipient']) ? (string) $payload['recipient'] : null,
                'status' => (string) ($payload['status'] ?? 'success'),
                'message_id' => isset($payload['message_id']) ? (string) $payload['message_id'] : null,
                'response' => isset($payload['response']) ? (string) $payload['response'] : null,
                'meta' => $payload['meta'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Unable to log tenant mail event', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
