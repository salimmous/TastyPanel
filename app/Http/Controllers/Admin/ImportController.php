<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Services\ImportService;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(
        protected ImportService $importService
    ) {}

    /**
     * Display a listing of imports
     */
    public function index(Request $request)
    {
        $tenantId = TenantContext::id();

        $imports = Import::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->with(['user'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($imports);
    }

    /**
     * Store a newly created import
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:51200', // 50MB
            'format' => 'required|in:csv,json,wordpress,excel',
            'type' => 'nullable|in:recipe,article,category',
            'mapping' => 'nullable|array',
            'options' => 'nullable|array',
            'options.update_existing' => 'nullable|boolean',
            'options.skip_duplicates' => 'nullable|boolean',
        ]);

        $tenantId = TenantContext::id();
        $tenant = \App\Models\Tenant::findOrFail($tenantId);

        $import = $this->importService->create(
            $tenant,
            $request->file('file'),
            $validated['format'],
            [
                'type' => $validated['type'] ?? 'recipe',
                'update_existing' => $validated['options']['update_existing'] ?? false,
                'skip_duplicates' => $validated['options']['skip_duplicates'] ?? true,
                'mapping' => $validated['mapping'] ?? null,
            ]
        );

        // Dispatch job to process import
        ProcessImportJob::dispatch($import);

        return response()->json([
            'success' => true,
            'message' => 'Import started successfully',
            'import' => $import,
        ], 201);
    }

    /**
     * Display the specified import
     */
    public function show(string $id)
    {
        $tenantId = TenantContext::id();

        $import = Import::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->with(['user'])
            ->findOrFail($id);

        return response()->json($import);
    }

    /**
     * Remove the specified import
     */
    public function destroy(string $id)
    {
        $tenantId = TenantContext::id();

        $import = Import::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        // Delete associated file
        if ($import->file_path) {
            \Storage::delete($import->file_path);
        }

        $import->delete();

        return response()->json([
            'success' => true,
            'message' => 'Import deleted successfully',
        ]);
    }

    /**
     * Get import progress
     */
    public function progress(string $id)
    {
        $tenantId = TenantContext::id();

        $import = Import::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        return response()->json([
            'status' => $import->status,
            'progress' => $import->progress_percentage,
            'total_items' => $import->total_items,
            'processed_items' => $import->processed_items,
            'success_count' => $import->success_count,
            'error_count' => $import->error_count,
            'errors' => $import->errors,
        ]);
    }
}
