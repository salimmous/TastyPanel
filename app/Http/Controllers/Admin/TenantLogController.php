<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Tenant;
use App\Services\LogReaderService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class TenantLogController extends Controller
{
    public function meta(Request $request, Tenant $tenant)
    {
        $this->authorizeTenant($request, $tenant);

        return response()->json([
            'types' => [
                'laravel',
                'php_fpm',
                'domain_access',
                'domain_error',
            ],
            'domains' => $tenant->domains()->select('id', 'hostname')->orderBy('id')->get(),
        ]);
    }

    public function tail(Request $request, Tenant $tenant, LogReaderService $reader)
    {
        $this->authorizeTenant($request, $tenant);

        $type = $request->get('type');
        $lines = (int) $request->get('lines', 200);
        $lines = max(1, min($lines, 2000));

        $path = null;

        if ($type === 'laravel') {
            $path = $this->resolveLaravelLog($tenant);
        } elseif ($type === 'php_fpm') {
            $path = $this->resolvePhpFpmLog($tenant);
        } elseif ($type === 'domain_access' || $type === 'domain_error') {
            $domainId = (int) $request->get('domain_id');
            $domain = Domain::findOrFail($domainId);
            if ($domain->tenant_id !== $tenant->id) {
                abort(403);
            }

            $template = $type === 'domain_access'
                ? config('services.logs.nginx_access_template')
                : config('services.logs.nginx_error_template');

            $path = str_contains($template, '%s') ? sprintf($template, $domain->hostname) : $template;
        }

        if (!$path) {
            return response()->json(['message' => 'Invalid log type.'], 422);
        }

        return response()->json([
            'path' => $path,
            'lines' => $reader->tail($path, $lines),
        ]);
    }

    private function resolveLaravelLog(Tenant $tenant): ?string
    {
        if (!$tenant->instance_root) {
            return null;
        }
        $logDir = rtrim($tenant->instance_root, '/') . '/storage/logs';
        $default = $logDir . '/laravel.log';
        if (file_exists($default)) {
            return $default;
        }

        if (!is_dir($logDir)) {
            return $default;
        }

        $files = glob($logDir . '/laravel*.log');
        if (!$files) {
            return $default;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return $files[0] ?? $default;
    }

    private function resolvePhpFpmLog(Tenant $tenant): ?string
    {
        if (!$tenant->instance_root) {
            return null;
        }
        return rtrim($tenant->instance_root, '/') . '/storage/logs/php-fpm.log';
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        $user = $request->user();
        if (!AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && !AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $tenant->id) {
            abort(403);
        }
    }
}
