<?php

namespace App\Services;

use App\Models\TwoFactorSecret;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    /**
     * Generate 2FA secret for user
     */
    public function generateSecret(User $user): TwoFactorSecret
    {
        $secret = $this->google2fa->generateSecretKey();
        $recoveryCodes = $this->generateRecoveryCodes();

        return TwoFactorSecret::updateOrCreate(
            ['user_id' => $user->id],
            [
                'secret' => Crypt::encryptString($secret),
                'recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
                'enabled' => false,
            ]
        );
    }

    /**
     * Generate QR code for user
     */
    public function getQrCode(User $user): string
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor) {
            $twoFactor = $this->generateSecret($user);
        }

        $secret = Crypt::decryptString($twoFactor->secret);
        $companyName = config('app.name', 'TastyPanel');

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $companyName,
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($qrCodeUrl);
    }

    /**
     * Verify TOTP code
     */
    public function verify(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor) {
            return false;
        }

        $secret = Crypt::decryptString($twoFactor->secret);

        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor) {
            return false;
        }

        $recoveryCodes = json_decode(
            Crypt::decryptString($twoFactor->recovery_codes),
            true
        );

        $index = array_search($code, $recoveryCodes);

        if ($index === false) {
            return false;
        }

        // Remove used recovery code
        unset($recoveryCodes[$index]);

        $twoFactor->update([
            'recovery_codes' => Crypt::encryptString(json_encode(array_values($recoveryCodes))),
        ]);

        return true;
    }

    /**
     * Enable 2FA for user
     */
    public function enable(User $user, string $code): bool
    {
        if (! $this->verify($user, $code)) {
            return false;
        }

        $user->twoFactorSecret->update([
            'enabled' => true,
            'verified_at' => now(),
            'enabled_at' => now(),
        ]);

        return true;
    }

    /**
     * Disable 2FA for user
     */
    public function disable(User $user): bool
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor) {
            return false;
        }

        $twoFactor->update(['enabled' => false]);

        return true;
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor) {
            throw new \Exception('2FA not set up for this user');
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $twoFactor->update([
            'recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
        ]);

        return $recoveryCodes;
    }

    /**
     * Get recovery codes
     */
    public function getRecoveryCodes(User $user): array
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor) {
            return [];
        }

        return json_decode(
            Crypt::decryptString($twoFactor->recovery_codes),
            true
        );
    }

    /**
     * Trust current device
     */
    public function trustDevice(User $user, string $deviceId): void
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor) {
            return;
        }

        $trustedDevices = $twoFactor->trusted_devices ?? [];

        $trustedDevices[$deviceId] = [
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addDays(30)->toIso8601String(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        $twoFactor->update(['trusted_devices' => $trustedDevices]);
    }

    /**
     * Check if device is trusted
     */
    public function isDeviceTrusted(User $user, string $deviceId): bool
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor || ! $twoFactor->enabled) {
            return true;
        }

        $trustedDevices = $twoFactor->trusted_devices ?? [];

        if (! isset($trustedDevices[$deviceId])) {
            return false;
        }

        $device = $trustedDevices[$deviceId];
        $expiresAt = \Carbon\Carbon::parse($device['expires_at']);

        return $expiresAt->isFuture();
    }

    /**
     * Generate recovery codes
     */
    protected function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(8));
        }

        return $codes;
    }

    /**
     * Get device ID from request
     */
    public function getDeviceId(): string
    {
        return hash('sha256', request()->userAgent().request()->ip());
    }
}
