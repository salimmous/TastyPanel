<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, SearchService $search)
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:200'],
            'types' => ['nullable', 'string'],
        ]);

        $tenantId = TenantContext::id();
        $environment = TenantContext::environment();
        $types = ! empty($data['types']) ? array_map('trim', explode(',', $data['types'])) : [];

        try {
            $results = $search->search($data['query'], $tenantId, $environment, $types);

            return response()->json($results);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
