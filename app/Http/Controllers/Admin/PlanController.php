<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Plan::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(AdminPermissions::isSuperadmin($request->user()), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'unique:plans,slug'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'interval' => ['nullable', 'string', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'is_active' => ['nullable', 'boolean'],
            'limits' => ['nullable', 'array'],
        ]);

        $plan = Plan::create($data);

        return response()->json([
            'data' => $plan,
        ], 201);
    }

    public function update(Request $request, Plan $plan)
    {
        abort_unless(AdminPermissions::isSuperadmin($request->user()), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:120', Rule::unique('plans', 'slug')->ignore($plan->id)],
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'interval' => ['sometimes', 'string', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'is_active' => ['sometimes', 'boolean'],
            'limits' => ['sometimes', 'array'],
        ]);

        $plan->fill($data);
        $plan->save();

        return response()->json([
            'data' => $plan,
        ]);
    }

    public function destroy(Plan $plan)
    {
        abort_unless(AdminPermissions::isSuperadmin(request()->user()), 403);
        $plan->delete();

        return response()->json([
            'message' => 'Plan deleted.',
        ]);
    }
}
