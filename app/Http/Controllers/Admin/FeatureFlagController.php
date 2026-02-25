<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\Tenant;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! AdminPermissions::isSuperadmin($user)) {
            abort(403);
        }

        $flags = FeatureFlag::with('tenant:id,name')
            ->orderBy('tenant_id')
            ->orderBy('key')
            ->get();

        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'data' => $flags,
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! AdminPermissions::isSuperadmin($user)) {
            abort(403);
        }

        $data = $this->validated($request);
        $data['created_by'] = $user?->id;

        $flag = FeatureFlag::create($data);

        return response()->json($flag, 201);
    }

    public function update(Request $request, FeatureFlag $featureFlag)
    {
        $user = $request->user();
        if (! AdminPermissions::isSuperadmin($user)) {
            abort(403);
        }

        $data = $this->validated($request, false);
        $featureFlag->update($data);

        return response()->json($featureFlag);
    }

    public function destroy(Request $request, FeatureFlag $featureFlag)
    {
        $user = $request->user();
        if (! AdminPermissions::isSuperadmin($user)) {
            abort(403);
        }

        $featureFlag->delete();

        return response()->json(['status' => 'deleted']);
    }

    private function validated(Request $request, bool $create = true): array
    {
        $rules = [
            'key' => ($create ? 'required' : 'sometimes').'|string|max:191',
            'name' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'enabled' => ($create ? 'required' : 'sometimes').'|boolean',
            'rollout_percentage' => 'nullable|integer|min:0|max:100',
            'environment' => 'nullable|string|max:64',
            'tenant_id' => 'nullable|exists:tenants,id',
        ];

        return $request->validate($rules);
    }
}
