<?php

namespace App\Jobs;

use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Import $import
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ImportService $importService): void
    {
        Log::info('Starting import processing', [
            'import_id' => $this->import->id,
            'format' => $this->import->format,
        ]);

        try {
            match ($this->import->format) {
                'csv' => $importService->processCSV($this->import),
                'json' => $importService->processJSON($this->import),
                'wordpress' => $importService->processWordPress($this->import),
                default => throw new \Exception("Unsupported format: {$this->import->format}"),
            };

            Log::info('Import completed', [
                'import_id' => $this->import->id,
                'success_count' => $this->import->success_count,
                'error_count' => $this->import->error_count,
            ]);

        } catch (\Exception $e) {
            Log::error('Import job failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->import->update([
                'status' => 'failed',
                'errors' => [
                    ['error' => $e->getMessage()],
                ],
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
        $this->import->update([
            'status' => 'failed',
            'errors' => [
                ['error' => $exception->getMessage()],
            ],
            'completed_at' => now(),
        ]);
    }
}
