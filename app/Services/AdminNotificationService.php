<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AdminNotificationService
{
    /**
     * Send notification to admins
     */
    public function notify(
        string $category,
        string $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): void {
        // Get all admin users
        $admins = User::where('is_admin', true)->get();

        foreach ($admins as $admin) {
            // Check user preferences
            $setting = NotificationSetting::where('user_id', $admin->id)
                ->where('category', $category)
                ->first();

            // Create in-app notification if enabled
            if (!$setting || $setting->in_app_enabled) {
                AdminNotification::create([
                    'user_id' => $admin->id,
                    'type' => $type,
                    'category' => $category,
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                    'action_url' => $actionUrl,
                ]);
            }

            // Send email if enabled
            if ($setting && $setting->email_enabled) {
                $this->sendEmail($admin, $type, $title, $message, $actionUrl);
            }
        }
    }

    /**
     * Notify about system error
     */
    public function notifySystemError(string $errorMessage, ?\Throwable $exception = null): void
    {
        $data = $exception ? [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ] : null;

        $this->notify(
            category: 'system',
            type: 'error',
            title: 'System Error Occurred',
            message: $errorMessage,
            data: $data,
            actionUrl: route('admin.logs.index')
        );
    }

    /**
     * Notify about security alert
     */
    public function notifySecurityAlert(string $title, string $message, ?array $data = null): void
    {
        $this->notify(
            category: 'security',
            type: 'security',
            title: $title,
            message: $message,
            data: $data,
            actionUrl: route('admin.security.index')
        );
    }

    /**
     * Notify about content event
     */
    public function notifyContentEvent(string $title, string $message, ?string $actionUrl = null): void
    {
        $this->notify(
            category: 'content',
            type: 'info',
            title: $title,
            message: $message,
            actionUrl: $actionUrl
        );
    }

    /**
     * Notify about webhook event
     */
    public function notifyWebhookEvent(string $title, string $message, ?array $data = null): void
    {
        $this->notify(
            category: 'webhook',
            type: 'warning',
            title: $title,
            message: $message,
            data: $data,
            actionUrl: route('admin.webhooks.index')
        );
    }

    /**
     * Notify about job failure
     */
    public function notifyJobFailure(string $jobName, string $errorMessage): void
    {
        $this->notify(
            category: 'job',
            type: 'error',
            title: "Job Failed: {$jobName}",
            message: $errorMessage,
            actionUrl: route('admin.failed-jobs.index')
        );
    }

    /**
     * Notify about performance issue
     */
    public function notifyPerformanceIssue(string $title, string $message, ?array $data = null): void
    {
        $this->notify(
            category: 'performance',
            type: 'warning',
            title: $title,
            message: $message,
            data: $data,
            actionUrl: route('admin.monitoring.index')
        );
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        return AdminNotification::where('user_id', $userId)
            ->unread()
            ->count();
    }

    /**
     * Mark all as read for user
     */
    public function markAllAsRead(int $userId): void
    {
        AdminNotification::where('user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Send email notification
     */
    protected function sendEmail(User $user, string $type, string $title, string $message, ?string $actionUrl): void
    {
        try {
            Mail::raw(
                "{$title}\n\n{$message}" .
                ($actionUrl ? "\n\nView: {$actionUrl}" : ''),
                function ($mail) use ($user, $title, $type) {
                    $mail->to($user->email)
                        ->subject("[TastyPanel] {$title}");
                }
            );
        } catch (\Exception $e) {
            // Don't let email failure break the notification
            \Log::error('Failed to send notification email: ' . $e->getMessage());
        }
    }
}
