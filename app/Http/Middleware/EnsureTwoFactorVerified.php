<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->two_factor_enabled) {
            return $next($request);
        }

        if ($request->is('api/admin/2fa*') || $request->is('api/admin/logout') || $request->is('api/admin/user')) {
            return $next($request);
        }

        if ($request->is('platform/2fa*') || $request->is('platform/logout')) {
            return $next($request);
        }

        $verified = $request->session()->get('two_factor_verified', false);
        if (! $verified) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Two-factor authentication required.',
                    'code' => 'two_factor_required',
                ], 403);
            }

            return redirect()->route('platform.2fa');
        }

        return $next($request);
    }
}
