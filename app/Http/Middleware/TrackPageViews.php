<?php

namespace App\Http\Middleware;

use App\Services\PageViewTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageViews
{
    protected PageViewTracker $tracker;

    public function __construct(PageViewTracker $tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful GET requests
        if ($request->isMethod('GET') && $response->isSuccessful()) {
            // Get tenant from request (assumes tenant is resolved in earlier middleware)
            $tenant = $request->attributes->get('tenant');

            if ($tenant) {
                // Track asynchronously to not slow down response
                dispatch(function () use ($tenant, $request) {
                    $this->tracker->track($tenant, $request);
                })->afterResponse();
            }
        }

        return $response;
    }
}
