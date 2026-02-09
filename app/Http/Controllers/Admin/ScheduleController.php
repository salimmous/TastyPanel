<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\ScheduledPublication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * List all schedules
     */
    public function index(Request $request): JsonResponse
    {
        $schedules = ScheduledPublication::with(['schedulable', 'user'])
            ->orderBy('scheduled_at')
            ->paginate(20);

        return response()->json($schedules);
    }

    /**
     * Create a schedule
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedulable_type' => ['required', 'string'],
            'schedulable_id' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:publish,unpublish,update'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'data' => ['nullable', 'array'],
        ]);

        $schedule = ScheduledPublication::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'schedule' => $schedule->load(['schedulable', 'user']),
            'message' => 'Schedule created successfully',
        ], 201);
    }

    /**
     * Update a schedule
     */
    public function update(Request $request, ScheduledPublication $schedule): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'action' => ['nullable', 'string', 'in:publish,unpublish,update'],
            'data' => ['nullable', 'array'],
        ]);

        if ($schedule->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot update a schedule that is not pending',
            ], 400);
        }

        $schedule->update($validated);

        return response()->json([
            'schedule' => $schedule->fresh(['schedulable', 'user']),
            'message' => 'Schedule updated successfully',
        ]);
    }

    /**
     * Cancel a schedule
     */
    public function destroy(ScheduledPublication $schedule): JsonResponse
    {
        if ($schedule->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot cancel a schedule that is not pending',
            ], 400);
        }

        $schedule->delete();

        return response()->json([
            'message' => 'Schedule cancelled successfully',
        ]);
    }

    /**
     * Get calendar view
     */
    public function calendar(Request $request): JsonResponse
    {
        $startDate = $request->input('start', now()->startOfMonth());
        $endDate = $request->input('end', now()->endOfMonth());

        $schedules = ScheduledPublication::with(['schedulable', 'user'])
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(function ($schedule) {
                return $schedule->scheduled_at->format('Y-m-d');
            });

        return response()->json($schedules);
    }
}
