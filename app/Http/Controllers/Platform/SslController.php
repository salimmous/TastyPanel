<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Domain;
use App\Models\SslCertificate;
use App\Services\SslProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SslController extends Controller
{
    public function index()
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $certificates = SslCertificate::with('site')->paginate(20);
        return view('platform.ssl.index', compact('certificates'));
    }

    public function issue(Request $request, $siteId, SslProvisioningService $service)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $site = Tenant::findOrFail($siteId);
        $domain = $site->primaryDomain;

        if (!$domain) {
            return back()->with('error', 'No primary domain found for site.');
        }

        $result = $service->provisionCertificate($domain, true);

        if ($result->status === 'error') {
            return back()->with('error', 'SSL Issue Failed: ' . $result->last_error);
        }

        return back()->with('success', 'SSL certificate issued successfully.');
    }

    public function revoke($id, SslProvisioningService $service)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        // Need revoke logic in service
        // service->revoke($id);

        return back()->with('info', 'Revoke feature coming soon.');
    }
}
