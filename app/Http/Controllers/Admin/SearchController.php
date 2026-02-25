<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use App\Support\AdminEnvironmentResolver;
use App\Support\AdminTenantResolver;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function status(SearchService $search)
    {
        return response()->json($search->status());
    }

    public function test(Request $request, SearchService $search)
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:200'],
            'types' => ['nullable', 'string'],
        ]);

        $tenantId = AdminTenantResolver::resolveId($request);
        $environment = AdminEnvironmentResolver::resolve($request);
        $types = ! empty($data['types']) ? array_map('trim', explode(',', $data['types'])) : [];

        try {
            $results = $search->search($data['query'], $tenantId, $environment, $types);

            return response()->json($results);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reindex(Request $request, SearchService $search)
    {
        $tenantId = AdminTenantResolver::resolveId($request);
        $environment = AdminEnvironmentResolver::resolve($request);

        try {
            $result = $search->reindex($tenantId, $environment);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
