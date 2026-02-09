<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\NotificationSetting;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected AdminNotificationService $notificationService
    ) {
    }

    /**
     * Get user's notifications
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = AdminNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Get unread count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount(auth()->id());

        return response()->json(['count' => $count]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, AdminNotification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead(auth()->id());

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, AdminNotification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    /**
     * Get notification settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $settings = NotificationSetting::where('user_id', auth()->id())->get();

        // Initialize if empty
        if ($settings->isEmpty()) {
            NotificationSetting::initializeForUser(auth()->id());
            $settings = NotificationSetting::where('user_id', auth()->id())->get();
        }

        return response()->json($settings);
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.category' => ['required', 'string'],
            'settings.*.email_enabled' => ['required', 'boolean'],
            'settings.*.in_app_enabled' => ['required', 'boolean'],
        ]);

        foreach ($validated['settings'] as $setting) {
            NotificationSetting::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'category' => $setting['category'],
                ],
                [
                    'email_enabled' => $setting['email_enabled'],
                    'in_app_enabled' => $setting['in_app_enabled'],
                ]
            );
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }
}
