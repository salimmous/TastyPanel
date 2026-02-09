<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Export;
use App\Services\ExportService;
use App\Jobs\ProcessExportJob;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function __construct(
        protected ExportService $exportService
    ) {
    }

    /**
     * Display a listing of exports
     */
    public function index(Request $request)
    {
        $tenantId = TenantContext::id();

        $exports = Export::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->with(['user'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($exports);
    }

    /**
     * Store a newly created export
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:csv,json,wordpress,pdf,excel',
            'type' => 'nullable|in:recipe,article,category,all',
            'filters' => 'nullable|array',
            'filters.category_id' => 'nullable|exists:categories,id',
            'filters.status' => 'nullable|in:draft,published',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date',
        ]);

        $tenantId = TenantContext::id();
        $tenant = \App\Models\Tenant::findOrFail($tenantId);

        $export = $this->exportService->create(
            $tenant,
            $validated['format'],
            $validated['type'] ?? 'recipe',
            $validated['filters'] ?? []
        );

        // Dispatch job to process export
        ProcessExportJob::dispatch($export);

        return response()->json([
            'success' => true,
            'message' => 'Export started successfully',
            'export' => $export,
        ], 201);
    }

    /**
     * Display the specified export
     */
    public function show(string $id)
    {
        $tenantId = TenantContext::id();

        $export = Export::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->with(['user'])
            ->findOrFail($id);

        return response()->json($export);
    }

    /**
     * Download export file
     */
    public function download(string $id)
    {
        $tenantId = TenantContext::id();

        $export = Export::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        if (!$export->isReady()) {
            return response()->json([
                'success' => false,
                'message' => 'Export is not ready yet',
            ], 400);
        }

        if ($export->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Export file has expired',
            ], 410);
        }

        // Record download
        $this->exportService->recordDownload($export);

        return Storage::download($export->file_path, $export->filename);
    }

    /**
     * Remove the specified export
     */
    public function destroy(string $id)
    {
        $tenantId = TenantContext::id();

        $export = Export::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        // Delete associated file
        if ($export->file_path) {
            Storage::delete($export->file_path);
        }

        $export->delete();

        return response()->json([
            'success' => true,
            'message' => 'Export deleted successfully',
        ]);
    }

    /**
     * Get export progress
     */
    public function progress(string $id)
    {
        $tenantId = TenantContext::id();

        $export = Export::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        return response()->json([
            'status' => $export->status,
            'progress' => $export->progress_percentage,
            'total_items' => $export->total_items,
            'processed_items' => $export->processed_items,
            'is_ready' => $export->isReady(),
            'download_url' => $export->download_url,
        ]);
    }

    /**
     * Cleanup expired exports
     */
    public function cleanup()
    {
        $count = $this->exportService->cleanupExpired();

        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$count} expired exports",
            'count' => $count,
        ]);
    }
}
