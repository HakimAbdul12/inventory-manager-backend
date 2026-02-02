<?php

namespace App\Jobs;

use App\Events\TextGenerationCompleted;
use App\Models\InventoryProcess;
use App\Services\AIContentService;
use App\Services\InventoryGenerationService;
use App\Services\ProcessTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateInventoryTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 5;

    protected InventoryProcess $process;

    public function __construct(InventoryProcess $process)
    {
        $this->process = $process;
    }

    public function handle(
        AIContentService $aiService,
        ProcessTrackingService $trackingService,
        InventoryGenerationService $generationService
    ): void {
        $process = $this->process->fresh(['category', 'steps']);
        $step = $process->steps()->where('step_name', 'text_generation')->first();

        if (!$step) {
            Log::error('Text generation step not found', ['process_id' => $process->id]);
            return;
        }

        try {
            // Mark step as in progress
            $trackingService->startStep($step);

            // Generate content using AI
            $generatedData = $aiService->generateInventoryContent(
                $process->category,
                $process->user_inputs ?? [],
                $process->custom_prompt
            );

            // Store the result
            $trackingService->completeStep($step, ['generated_data' => $generatedData]);

            // Create the inventory item with generated data
            $generationService->createInventoryItem($process, $generatedData);

            // Broadcast completion
            $preview = [
                'make' => $generatedData['make'] ?? null,
                'model' => $generatedData['model'] ?? null,
                'year' => $generatedData['year'] ?? null,
                'description' => substr($generatedData['description'] ?? '', 0, 200) . '...',
            ];
            broadcast(new TextGenerationCompleted($step->fresh(), $preview))->toOthers();

            Log::info('Text generation completed', [
                'process_id' => $process->id,
                'fields_generated' => count($generatedData),
            ]);

            // Dispatch next job
            $this->dispatchNextJob($process->fresh());
        } catch (\Exception $e) {
            Log::error('Text generation failed', [
                'process_id' => $process->id,
                'error' => $e->getMessage(),
            ]);

            $trackingService->failStep($step, $e->getMessage());

            // If we have retries left, the job will be retried automatically
            // Otherwise, fail the entire process
            if ($this->attempts() >= $this->tries) {
                $generationService->failProcess($process, 'Text generation failed: ' . $e->getMessage());
            }

            throw $e;
        }
    }

    protected function dispatchNextJob(InventoryProcess $process): void
    {
        // Check if image generation step exists and should be run
        $imageStep = $process->steps()->where('step_name', 'image_generation')->first();

        if ($imageStep) {
            if ($process->shouldGenerateImages()) {
                GenerateInventoryImagesJob::dispatch($process)
                    ->onQueue(config('inventory.queue.name', 'inventory'));
            } else {
                // Skip image generation and go to finalization
                app(ProcessTrackingService::class)->skipStep($imageStep, 'User opted out of image generation');
                FinalizeInventoryProcessJob::dispatch($process)
                    ->onQueue(config('inventory.queue.name', 'inventory'));
            }
        } else {
            // No image step, go directly to finalization
            FinalizeInventoryProcessJob::dispatch($process)
                ->onQueue(config('inventory.queue.name', 'inventory'));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Text generation job failed permanently', [
            'process_id' => $this->process->id,
            'error' => $exception->getMessage(),
        ]);

        app(InventoryGenerationService::class)->failProcess(
            $this->process,
            'Text generation failed: ' . $exception->getMessage()
        );
    }
}
