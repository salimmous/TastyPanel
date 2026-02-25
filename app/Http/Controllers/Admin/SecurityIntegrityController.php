<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityBaseline;
use App\Models\SecurityIntegrityCheck;
use App\Services\FileIntegrityService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class SecurityIntegrityController extends Controller
{
    public function baselines(Request $request)
    {
        $this->authorizeSuperadmin($request);

        return response()->json([
            'data' => SecurityBaseline::orderByDesc('id')->get(),
        ]);
    }

    public function createBaseline(Request $request, FileIntegrityService $integrity)
    {
        $this->authorizeSuperadmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'root_path' => ['nullable', 'string'],
            'paths' => ['nullable', 'array'],
        ]);

        $baseline = $integrity->createBaseline(
            $data['name'],
            $data['root_path'] ?? base_path(),
            $data['paths'] ?? [],
            $request->user()?->id
        );

        return response()->json([
            'data' => $baseline,
        ], 201);
    }

    public function runCheck(Request $request, SecurityBaseline $baseline, FileIntegrityService $integrity)
    {
        $this->authorizeSuperadmin($request);
        $check = $integrity->check($baseline, $request->user()?->id);

        return response()->json([
            'data' => $check,
        ], 201);
    }

    public function checks(Request $request, SecurityBaseline $baseline)
    {
        $this->authorizeSuperadmin($request);
        $checks = SecurityIntegrityCheck::where('security_baseline_id', $baseline->id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($checks);
    }

    private function authorizeSuperadmin(Request $request): void
    {
        if (! AdminPermissions::isSuperadmin($request->user())) {
            abort(403);
        }
    }
}
