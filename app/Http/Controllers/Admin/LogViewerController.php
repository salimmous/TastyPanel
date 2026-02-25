<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\LogReaderService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class LogViewerController extends Controller
{
    public function meta(Request $request)
    {
        $this->authorizeSuperadmin($request);

        return response()->json([
            'logs' => [
                'php_fpm' => config('services.logs.php_fpm'),
                'domain_access_template' => config('services.logs.nginx_access_template'),
                'domain_error_template' => config('services.logs.nginx_error_template'),
            ],
            'domains' => Domain::select('id', 'hostname', 'tenant_id')->orderBy('id')->get(),
        ]);
    }

    public function tail(Request $request, LogReaderService $reader)
    {
        $type = $request->get('type');
        $lines = (int) $request->get('lines', 200);
        $lines = max(1, min($lines, 2000));

        $path = null;

        if ($type === 'php_fpm') {
            $this->authorizeSuperadmin($request);
            $path = config('services.logs.php_fpm');
        } elseif ($type === 'domain_access' || $type === 'domain_error') {
            $domainId = (int) $request->get('domain_id');
            $domain = Domain::findOrFail($domainId);
            $this->authorizeDomain($request, $domain);

            $template = $type === 'domain_access'
                ? config('services.logs.nginx_access_template')
                : config('services.logs.nginx_error_template');

            $path = str_contains($template, '%s') ? sprintf($template, $domain->hostname) : $template;
        }

        if (! $path) {
            return response()->json(['message' => 'Invalid log type.'], 422);
        }

        return response()->json([
            'path' => $path,
            'lines' => $reader->tail($path, $lines),
        ]);
    }

    private function authorizeSuperadmin(Request $request): void
    {
        if (! AdminPermissions::isSuperadmin($request->user())) {
            abort(403);
        }
    }

    private function authorizeDomain(Request $request, Domain $domain): void
    {
        $user = $request->user();
        if (AdminPermissions::isSuperadmin($user)) {
            return;
        }
        if ((int) $user?->tenant_id !== (int) $domain->tenant_id) {
            abort(403);
        }
    }
}
