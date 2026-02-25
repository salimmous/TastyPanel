<?php

namespace App\Jobs;

use App\Services\ImageOptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OptimizeImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $imagePath,
        public array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImageOptimizationService $optimizer): void
    {
        Log::info('Starting image optimization', ['path' => $this->imagePath]);

        $result = $optimizer->optimize($this->imagePath, $this->options);

        if (isset($result['error'])) {
            Log::error('Image optimization failed', [
                'path' => $this->imagePath,
                'error' => $result['error'],
            ]);

            return;
        }

        $savings = $optimizer->calculateSavings($result);

        Log::info('Image optimization completed', [
            'path' => $this->imagePath,
            'savings' => $savings,
            'formats' => array_keys($result),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Image optimization job failed permanently', [
            'path' => $this->imagePath,
            'error' => $exception->getMessage(),
        ]);
    }
}
