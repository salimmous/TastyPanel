<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\SslCertificate;
use App\Services\CloudflareService;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlatformDomainController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (!PlatformInstallController::isInstalled()) {
            return redirect()->route('platform.install');
        }

        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (!AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $settings = PlatformSetting::getData();
        $sslDays = (int) ($settings['ssl_alert_days'] ?? 14);

        $q = trim((string) $request->query('q', ''));
        $env = trim((string) $request->query('env', ''));
        $allowedEnvs = ['production', 'staging', 'preview'];
        if (!in_array($env, $allowedEnvs, true)) {
            $env = '';
        }

        $query = Domain::query()
            ->with([
                'tenant:id,name,slug',
                'sslCertificate:id,domain_id,status,expires_at,issued_at,last_error',
            ]);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('hostname', 'like', '%' . $q . '%')
                    ->orWhereHas('tenant', function ($tenantQ) use ($q) {
                        $tenantQ->where('name', 'like', '%' . $q . '%')
                            ->orWhere('slug', 'like', '%' . $q . '%');
                    });
            });
        }

        if ($env !== '') {
            $query->where('environment', $env);
        }

        $domains = $query
            ->orderBy('hostname')
            ->paginate(100)
            ->withQueryString();

        $envCounts = Domain::query()
            ->selectRaw('environment, count(*) as c')
            ->groupBy('environment')
            ->pluck('c', 'environment')
            ->all();

        $stats = [
            'total' => (int) Domain::count(),
            'production' => (int) ($envCounts['production'] ?? 0),
            'staging' => (int) ($envCounts['staging'] ?? 0),
            'preview' => (int) ($envCounts['preview'] ?? 0),
            'ssl_expiring' => (int) SslCertificate::query()
                ->where('status', 'issued')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays($sslDays))
                ->count(),
            'http3_issues' => (int) Domain::query()
                ->where('http3_enabled', true)
                ->whereNotIn('http3_status', ['ok', 'advertised'])
                ->count(),
        ];

        $domainActions = [
            'domain_provision_full' => [
                'label' => 'Full provision (DNS + SSL + Nginx)',
                'confirm' => 'domain_provision_full',
            ],
            'domain_ssl_request' => [
                'label' => 'Request SSL (queue)',
                'confirm' => null,
            ],
            'domain_ssl_provision_force' => [
                'label' => 'Renew SSL (force)',
                'confirm' => 'domain_ssl_provision_force',
            ],
            'domain_nginx_test' => [
                'label' => 'Nginx test (nginx -t)',
                'confirm' => 'domain_nginx_test',
            ],
            'domain_nginx_apply' => [
                'label' => 'Nginx apply + reload',
                'confirm' => 'domain_nginx_apply',
            ],
            'domain_http3_enable' => [
                'label' => 'Enable HTTP/3',
                'confirm' => 'domain_http3_enable',
            ],
            'domain_http3_disable' => [
                'label' => 'Disable HTTP/3',
                'confirm' => 'domain_http3_disable',
            ],
            'domain_http3_check' => [
                'label' => 'HTTP/3 health check',
                'confirm' => null,
            ],
            'domain_cf_purge_cache_host' => [
                'label' => 'Cloudflare purge (host)',
                'confirm' => null,
            ],
            'domain_cf_purge_cache_zone' => [
                'label' => 'Cloudflare purge (zone)',
                'confirm' => 'domain_cf_purge_cache_zone',
            ],
            'domain_log_error_tail' => [
                'label' => 'Tail Nginx error log',
                'confirm' => null,
            ],
        ];

        return view('platform.domains', [
            'sslDays' => $sslDays,
            'stats' => $stats,
            'q' => $q,
            'env' => $env,
            'domains' => $domains,
            'domainActions' => $domainActions,
            'lastAction' => session('runbook_action'),
            'lastOutput' => session('runbook_output'),
            'lastSuccess' => session('runbook_success'),
            'lastDomainId' => session('runbook_domain_id'),
        ]);
    }

    public function dns(Request $request, Domain $domain, CloudflareService $cloudflare)
    {
        if (!PlatformInstallController::isInstalled()) {
            return response()->json(['message' => 'Not installed'], 409);
        }

        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $zoneId = $domain->cf_zone_id ?: (string) config('services.cloudflare.zone_id');
        if (!$zoneId) {
            return response()->json([
                'message' => 'Cloudflare zone id is missing for this domain (cf_zone_id).',
            ], 422);
        }

        try {
            $recordById = null;
            if (!empty($domain->cf_record_id)) {
                $recordById = $cloudflare->getDnsRecord($zoneId, (string) $domain->cf_record_id);
            }

            $name = (string) ($request->query('name') ?: $domain->hostname);
            $recordsByName = $cloudflare->listDnsRecords($zoneId, [
                'name' => $name,
                'per_page' => 100,
            ]);

            return response()->json([
                'zone_id' => $zoneId,
                'domain' => [
                    'id' => $domain->id,
                    'hostname' => $domain->hostname,
                    'cf_zone_id' => $domain->cf_zone_id,
                    'cf_record_id' => $domain->cf_record_id,
                ],
                'record_by_id' => $recordById,
                'records_by_name' => $recordsByName,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
