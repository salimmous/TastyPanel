<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        $user = $request->user();
        if (! AdminPermissions::isSuperadmin($user) && (int) $user?->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $query = AuditLog::with(['user:id,name,email'])
            ->where('tenant_id', $tenant->id);

        if ($request->has('search')) {
            $term = $request->search;
            $query->where('route', 'like', "%{$term}%")
                ->orWhere('action', 'like', "%{$term}%");
        }

        $logs = $query->orderByDesc('id')->paginate(25);

        return response()->json($logs);
    }
}
