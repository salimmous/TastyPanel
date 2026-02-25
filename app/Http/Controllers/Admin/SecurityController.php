<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityScan;
use App\Services\SecurityScanService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function scans(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $scans = SecurityScan::orderByDesc('id')->paginate(20);

        return response()->json($scans);
    }

    public function runScan(Request $request, SecurityScanService $scanner)
    {
        $this->authorizeSuperadmin($request);

        $data = $request->validate([
            'path' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
        ]);

        $path = $data['path'] ?? base_path();
        $type = $data['type'] ?? 'malware';
        $scan = $scanner->run($path, $request->user()?->id, $type);

        return response()->json([
            'data' => $scan,
        ], 201);
    }

    private function authorizeSuperadmin(Request $request): void
    {
        if (! AdminPermissions::isSuperadmin($request->user())) {
            abort(403);
        }
    }
}
