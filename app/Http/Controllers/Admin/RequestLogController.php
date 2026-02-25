<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestLog;
use App\Services\RequestLogService;
use Illuminate\Http\Request;

class RequestLogController extends Controller
{
    public function __construct(
        protected RequestLogService $logService
    ) {}

    /**
     * Get request logs with filters
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'method',
            'status',
            'path',
            'user_id',
            'tenant_id',
            'ip',
            'slow',
            'from',
            'to',
        ]);

        $perPage = min(100, (int) $request->get('per_page', 50));

        return response()->json(
            $this->logService->getLogs($filters, $perPage)
        );
    }

    /**
     * Get single log entry
     */
    public function show(RequestLog $requestLog)
    {
        return response()->json([
            'data' => $requestLog->load(['user:id,name,email', 'tenant:id,name']),
        ]);
    }

    /**
     * Get performance statistics
     */
    public function performance(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $days = max(1, min(30, $days));

        return response()->json([
            'data' => $this->logService->getPerformanceStats($days),
        ]);
    }

    /**
     * Get recent errors
     */
    public function errors(Request $request)
    {
        $limit = (int) $request->get('limit', 20);
        $limit = max(10, min(100, $limit));

        return response()->json([
            'data' => $this->logService->getRecentErrors($limit),
        ]);
    }

    /**
     * Get daily statistics chart
     */
    public function daily(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $days = max(7, min(90, $days));

        return response()->json([
            'data' => $this->logService->getDailyStats($days),
        ]);
    }

    /**
     * Cleanup old logs
     */
    public function cleanup(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $days = max(7, $days);

        $deleted = $this->logService->cleanup($days);

        return response()->json([
            'message' => "Deleted {$deleted} old log entries",
            'deleted' => $deleted,
        ]);
    }
}
