<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;

class TwoFactorAuth
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Skip if user not authenticated or 2FA not enabled
        if (! $user || ! $user->twoFactorSecret?->enabled) {
            return $next($request);
        }

        // Check if device is trusted
        $deviceId = $this->twoFactorService->getDeviceId();
        if ($this->twoFactorService->isDeviceTrusted($user, $deviceId)) {
            return $next($request);
        }

        // Check if 2FA session is verified
        if (session('2fa_verified') === $user->id) {
            return $next($request);
        }

        // Redirect to 2FA verification
        return redirect()->route('2fa.verify');
    }
}
