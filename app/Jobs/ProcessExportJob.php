<?php

namespace App\Jobs;

use App\Models\Export;
use App\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Export $export
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ExportService $exportService): void
    {
        Log::info('Starting export processing', [
            'export_id' => $this->export->id,
            'format' => $this->export->format,
        ]);

        try {
            match ($this->export->format) {
                'csv' => $exportService->generateCSV($this->export),
                'json' => $exportService->generateJSON($this->export),
                'wordpress' => $exportService->generateWordPress($this->export),
                default => throw new \Exception("Unsupported format: {$this->export->format}"),
            };

            Log::info('Export completed', [
                'export_id' => $this->export->id,
                'items' => $this->export->total_items,
                'file_size' => $this->export->file_size,
            ]);

        } catch (\Exception $e) {
            Log::error('Export job failed', [
                'export_id' => $this->export->id,
                'error' => $e->getMessage(),
            ]);

            $this->export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->export->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);
    }
}
