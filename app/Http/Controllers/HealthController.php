<?php

namespace App\Http\Controllers;

use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function __construct(
        protected HealthCheckService $healthCheck
    ) {
    }

    /**
     * Overall health check
     */
    public function index(): JsonResponse
    {
        $health = $this->healthCheck->check();

        $statusCode = match ($health['status']) {
            'healthy' => 200,
            'degraded' => 503,
            'down' => 503,
            default => 503,
        };

        return response()->json($health, $statusCode);
    }

    /**
     * Database health check
     */
    public function database(): JsonResponse
    {
        $check = $this->healthCheck->checkDatabase();

        return response()->json([
            'service' => 'database',
            ...$check,
        ], $check['status'] === 'up' ? 200 : 503);
    }

    /**
     * Redis health check
     */
    public function redis(): JsonResponse
    {
        $check = $this->healthCheck->checkRedis();

        return response()->json([
            'service' => 'redis',
            ...$check,
        ], $check['status'] === 'up' ? 200 : 503);
    }

    /**
     * Storage health check
     */
    public function storage(): JsonResponse
    {
        $check = $this->healthCheck->checkStorage();

        return response()->json([
            'service' => 'storage',
            ...$check,
        ], $check['status'] === 'up' ? 200 : 503);
    }

    /**
     * Queue health check
     */
    public function queue(): JsonResponse
    {
        $check = $this->healthCheck->checkQueue();

        return response()->json([
            'service' => 'queue',
            ...$check,
        ], $check['status'] === 'up' ? 200 : 503);
    }
}
