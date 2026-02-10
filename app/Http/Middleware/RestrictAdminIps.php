<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictAdminIps
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = $this->allowedIps();
        if (empty($allowed)) {
            return $next($request);
        }

        $ip = $request->ip();
        foreach ($allowed as $rule) {
            if ($rule === '*' || $rule === $ip) {
                return $next($request);
            }

            if ($this->matchesCidr($ip, $rule)) {
                return $next($request);
            }
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Access denied.',
            ], 403);
        }

        abort(403);
    }

    private function allowedIps(): array
    {
        $raw = config('services.panel.allowed_ips', '');
        try {
            $settings = \App\Models\PlatformSetting::getData();
            if (!empty($settings['panel_allowed_ips'])) {
                $raw = $settings['panel_allowed_ips'];
            }
        } catch (\Throwable $e) {
        }
        if (empty($raw)) {
            return [];
        }

        $parts = array_map('trim', explode(',', $raw));
        return array_values(array_filter($parts, fn ($value) => $value !== ''));
    }

    private function matchesCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return false;
        }

        [$subnet, $maskBits] = explode('/', $cidr, 2);
        if (!is_numeric($maskBits)) {
            return false;
        }

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $maskBits = (int) $maskBits;
        $len = strlen($ipBin);
        $bytes = intdiv($maskBits, 8);
        $remainder = $maskBits % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = (~0 << (8 - $remainder)) & 0xFF;
        return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
    }
}
