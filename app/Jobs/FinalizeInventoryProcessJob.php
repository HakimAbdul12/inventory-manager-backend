<?php

namespace App\Jobs;

use App\Models\InventoryProcess;
use App\Services\InventoryGenerationService;
use App\Services\ProcessTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FinalizeInventoryProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public int $backoff = 3;

    protected InventoryProcess $process;

    public function __construct(InventoryProcess $process)
    {
        $this->process = $process;
    }

    public function handle(
        ProcessTrackingService $trackingService,
        InventoryGenerationService $generationService
    ): void {
        $process = $this->process->fresh(['steps', 'inventoryItem']);
        $step = $process->steps()->where('step_name', 'finalization')->first();

        if (!$step) {
            Log::error('Finalization step not found', ['process_id' => $process->id]);
            return;
        }

        try {
            $trackingService->startStep($step);

            // Verify all required steps completed successfully
            $failedSteps = $process->steps()
                ->where('step_name', '!=', 'finalization')
                ->where('status', 'failed')
                ->get();

            if ($failedSteps->isNotEmpty()) {
                $failedNames = $failedSteps->pluck('step_name')->join(', ');
                throw new \RuntimeException("Cannot finalize: steps failed - {$failedNames}");
            }

            // Ensure inventory item exists
            $inventoryItem = $process->inventoryItem;
            if (!$inventoryItem) {
                throw new \RuntimeException('No inventory item found for this process');
            }

            // Update inventory item metadata
            $inventoryItem->update([
                'metadata' => array_merge($inventoryItem->metadata ?? [], [
                    'generation_completed_at' => now()->toIso8601String(),
                    'total_steps' => $process->steps()->count(),
                    'process_duration' => $process->total_duration,
                ]),
            ]);

            // Mark step as completed
            $trackingService->completeStep($step, [
                'inventory_item_id' => $inventoryItem->id,
            ]);

            // Finalize the process
            $generationService->finalizeProcess($process);

            Log::info('Inventory process finalized', [
                'process_id' => $process->id,
                'inventory_item_id' => $inventoryItem->id,
                'total_duration' => $process->total_duration,
            ]);
        } catch (\Exception $e) {
            Log::error('Finalization failed', [
                'process_id' => $process->id,
                'error' => $e->getMessage(),
            ]);

            $trackingService->failStep($step, $e->getMessage());

            if ($this->attempts() >= $this->tries) {
                $generationService->failProcess($process, 'Finalization failed: ' . $e->getMessage());
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Finalization job failed permanently', [
            'process_id' => $this->process->id,
            'error' => $exception->getMessage(),
        ]);

        app(InventoryGenerationService::class)->failProcess(
            $this->process,
            'Finalization failed: ' . $exception->getMessage()
        );
    }
}
