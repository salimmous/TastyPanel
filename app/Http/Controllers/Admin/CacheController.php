<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Tenant;
use App\Services\CloudflareService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class CacheController extends Controller
{
    public function purgeTenant(Request $request, Tenant $tenant, CloudflareService $cloudflare)
    {
        $user = $request->user();
        if (!AdminPermissions::isSuperadmin($user) && (int) $user?->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $domainId = $request->input('domain_id');
        $domains = Domain::where('tenant_id', $tenant->id)
            ->when($domainId, fn ($q) => $q->where('id', $domainId))
            ->get();

        $results = [];
        foreach ($domains as $domain) {
            if (!$domain->cf_zone_id) {
                $results[] = [
                    'domain' => $domain->hostname,
                    'status' => 'skipped',
                    'message' => 'Missing Cloudflare zone id.',
                ];
                continue;
            }

            try {
                $cloudflare->purgeCache($domain->cf_zone_id, [$domain->hostname]);
                $results[] = [
                    'domain' => $domain->hostname,
                    'status' => 'ok',
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'domain' => $domain->hostname,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'data' => $results,
        ]);
    }
}
