<?php

namespace App\Console\Commands;

use App\Models\ScheduledPublication;
use App\Services\AdminNotificationService;
use Illuminate\Console\Command;

class ProcessScheduledPublications extends Command
{
    protected $signature = 'schedule:publish';
    protected $description = 'Process scheduled publications';

    public function handle(AdminNotificationService $notificationService): int
    {
        $schedules = ScheduledPublication::due()->get();

        if ($schedules->isEmpty()) {
            $this->info('No scheduled publications to process');
            return self::SUCCESS;
        }

        $this->info("Processing {$schedules->count()} scheduled publication(s)...");

        $successCount = 0;
        $failureCount = 0;

        foreach ($schedules as $schedule) {
            $this->line("Processing: {$schedule->schedulable_type} #{$schedule->schedulable_id}");

            if ($schedule->execute()) {
                $successCount++;
                $this->info("✓ Success: {$schedule->action}");

                // Notify admin
                $notificationService->notifyContentEvent(
                    title: 'Scheduled Publication Completed',
                    message: "{$schedule->schedulable_type} #{$schedule->schedulable_id} has been {$schedule->action}ed",
                    actionUrl: url("/admin/recipes/{$schedule->schedulable_id}")
                );
            } else {
                $failureCount++;
                $this->error("✗ Failed: {$schedule->error_message}");

                // Notify admin of failure
                $notificationService->notify(
                    category: 'content',
                    type: 'error',
                    title: 'Scheduled Publication Failed',
                    message: "Failed to {$schedule->action} {$schedule->schedulable_type} #{$schedule->schedulable_id}: {$schedule->error_message}"
                );
            }
        }

        $this->newLine();
        $this->info("Completed: {$successCount} success, {$failureCount} failed");

        return self::SUCCESS;
    }
}
