<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->web(append: [
            // \App\Http\Middleware\PerformanceMonitor::class, // Disabled temporarily
        ]);
        $middleware->api(append: [
            // \App\Http\Middleware\PerformanceMonitor::class, // Disabled temporarily
        ]);

        $middleware->alias([
            'admin.ip' => \App\Http\Middleware\RestrictAdminIps::class,
            'tenant.throttle' => \App\Http\Middleware\ThrottleTenant::class,
            'tenant.security' => \App\Http\Middleware\TenantSecurityShield::class,
            'tenant.quota' => \App\Http\Middleware\EnforceTenantQuota::class,
            'admin.audit' => \App\Http\Middleware\AdminAuditLog::class,
            'admin.2fa' => \App\Http\Middleware\EnsureTwoFactorVerified::class,
            'api.key' => \App\Http\Middleware\ApiKeyAuth::class,
            'site.cache' => \App\Http\Middleware\SiteResponseCache::class,
            // \App\Http\Middleware\PerformanceMonitor::class, // Disabled temporarily
        ]);

        // Enable session for API routes (needed for admin authentication)
        $middleware->api(append: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        // Configure CORS to allow tastypanel.site
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $exception) {
            try {
                if (app()->runningInConsole()) {
                    return;
                }

                if (!config('monitoring.error_tracking.enabled', true)) {
                    return;
                }

                $isServerError = method_exists($exception, 'getStatusCode')
                    ? ((int) $exception->getStatusCode() >= 500)
                    : true;
                $level = $isServerError ? 'error' : 'warning';

                app(\App\Services\ErrorTrackerService::class)->logError($exception, $level, [
                    'source' => 'exception-handler',
                ]);
            } catch (\Throwable $inner) {
                // Never break request lifecycle on monitoring failures.
            }
        });
    })->create();
