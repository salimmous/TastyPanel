<?php

namespace App\Services;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ErrorTrackerService
{
    /**
     * Log an error
     */
    public function logError(
        \Throwable $exception,
        string $level = 'error',
        array $context = []
    ): ErrorLog {
        $request = request();
        $url = $request ? $request->fullUrl() : null;
        $method = $request ? $request->method() : null;
        $ip = $request ? $request->ip() : null;
        $userAgent = $request ? $request->userAgent() : null;

        $errorLog = ErrorLog::create([
            'tenant_id' => $this->getTenantId(),
            'user_id' => auth()->id(),
            'level' => $level,
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'stack_trace' => $exception->getTraceAsString(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'url' => $url,
            'method' => $method,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'context' => $context,
            'environment' => app()->environment(),
        ]);

        // Log to Laravel log as well
        Log::channel('daily')->{$level}($exception->getMessage(), [
            'exception' => $exception,
            'context' => $context,
        ]);

        // Send alert for critical errors
        if ($level === 'critical' || $this->isHighErrorRate()) {
            $this->sendAlert($errorLog);
        }

        return $errorLog;
    }

    /**
     * Check if error rate is high
     */
    protected function isHighErrorRate(): bool
    {
        $recentErrors = ErrorLog::where('created_at', '>', now()->subMinutes(5))
            ->where('level', 'error')
            ->count();

        return $recentErrors >= 5;
    }

    /**
     * Send error alert
     */
    protected function sendAlert(ErrorLog $errorLog): void
    {
        try {
            $adminEmail = config('mail.admin_email', config('mail.from.address'));

            if (! $adminEmail) {
                return;
            }

            Mail::raw(
                "Critical Error Detected\n\n".
                "Type: {$errorLog->type}\n".
                "Message: {$errorLog->message}\n".
                "File: {$errorLog->file}:{$errorLog->line}\n".
                "URL: {$errorLog->url}\n".
                "Time: {$errorLog->created_at}\n\n".
                'Please check the error logs immediately.',
                function ($message) use ($adminEmail, $errorLog) {
                    $message->to($adminEmail)
                        ->subject("[TastyPanel] Critical Error Alert - {$errorLog->type}");
                }
            );
        } catch (\Exception $e) {
            // Don't let alert failure crash the app
            Log::error('Failed to send error alert: '.$e->getMessage());
        }
    }

    /**
     * Get recent errors
     */
    public function getRecentErrors(int $limit = 50): \Illuminate\Support\Collection
    {
        return ErrorLog::with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get error stats
     */
    public function getStats(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        return [
            'total' => ErrorLog::where('created_at', '>', $since)->count(),
            'critical' => ErrorLog::where('created_at', '>', $since)->where('level', 'critical')->count(),
            'errors' => ErrorLog::where('created_at', '>', $since)->where('level', 'error')->count(),
            'warnings' => ErrorLog::where('created_at', '>', $since)->where('level', 'warning')->count(),
            'unresolved' => ErrorLog::where('created_at', '>', $since)->where('is_resolved', false)->count(),
        ];
    }

    /**
     * Mark error as resolved
     */
    public function resolve(int $errorId): bool
    {
        $error = ErrorLog::find($errorId);

        if (! $error) {
            return false;
        }

        return $error->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);
    }

    /**
     * Get current tenant ID
     */
    protected function getTenantId(): ?int
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }

        if (class_exists('\App\Support\TenantContext')) {
            return \App\Support\TenantContext::id();
        }

        return null;
    }
}
