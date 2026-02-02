<?php

namespace App\Services;

use App\Enums\ProcessStatus;
use App\Events\InventoryGenerationStarted;
use App\Events\InventoryProcessCompleted;
use App\Events\UserProcessUpdated;
use App\Jobs\GenerateInventoryTextJob;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryGenerationService
{
    protected ProcessTrackingService $trackingService;

    public function __construct(ProcessTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Start a new inventory generation process.
     */
    public function startGeneration(
        string $userId,
        Category $category,
        array $userInputs,
        ?string $customPrompt = null,
        array $options = []
    ): InventoryProcess {
        return DB::transaction(function () use ($userId, $category, $userInputs, $customPrompt, $options) {
            // Validate inputs against category schema
            $errors = $category->validateInputs($userInputs);
            if (!empty($errors)) {
                throw new \InvalidArgumentException('Validation failed: ' . json_encode($errors));
            }

            // Create the process
            $process = InventoryProcess::create([
                'user_id' => $userId,
                'category_id' => $category->id,
                'user_inputs' => $userInputs,
                'custom_prompt' => $customPrompt,
                'options' => array_merge([
                    'generateImages' => false,
                    'imageCount' => config('inventory.images.default_count', 3),
                ], $options),
                'status' => ProcessStatus::PENDING,
            ]);

            // Create process steps
            $this->trackingService->createProcessSteps($process);

            Log::info('Inventory generation process created', [
                'process_id' => $process->id,
                'category' => $category->slug,
                'user_id' => $userId,
            ]);

            // Mark as started and dispatch first job
            $process->markAsStarted();

            // Broadcast start event (to process channel)
            broadcast(new InventoryGenerationStarted($process))->toOthers();

            // Broadcast to user's processes list channel
            broadcast(new UserProcessUpdated($process, 'created'))->toOthers();

            // Dispatch the first job (text generation)
            GenerateInventoryTextJob::dispatch($process)
                ->onQueue(config('inventory.queue.name', 'inventory'));

            return $process;
        });
    }

    /**
     * Create the final inventory item from a completed process.
     */
    public function createInventoryItem(InventoryProcess $process, array $generatedData): InventoryItem
    {
        $item = InventoryItem::create([
            'process_id' => $process->id,
            'user_id' => $process->user_id,
            'category_id' => $process->category_id,
            'status' => 'draft',
            'generated_data' => $generatedData,
            'images' => [],
            'metadata' => [
                'generation_duration' => $process->total_duration,
                'category_slug' => $process->category->slug,
            ],
        ]);

        $process->update(['inventory_item_id' => $item->id]);

        Log::info('Inventory item created', [
            'item_id' => $item->id,
            'process_id' => $process->id,
        ]);

        return $item;
    }

    /**
     * Finalize a completed process.
     */
    public function finalizeProcess(InventoryProcess $process): void
    {
        $process->markAsCompleted();

        Log::info('Inventory process completed', [
            'process_id' => $process->id,
            'item_id' => $process->inventory_item_id,
            'total_duration' => $process->total_duration,
        ]);

        // Broadcast completion event (to process channel)
        broadcast(new InventoryProcessCompleted($process))->toOthers();

        // Broadcast to user's processes list channel
        broadcast(new UserProcessUpdated($process, 'completed'))->toOthers();
    }

    /**
     * Handle process failure.
     */
    public function failProcess(InventoryProcess $process, string $reason): void
    {
        $process->markAsFailed($reason);

        Log::error('Inventory process failed', [
            'process_id' => $process->id,
            'reason' => $reason,
        ]);

        // Broadcast failure to user's processes list channel
        broadcast(new UserProcessUpdated($process, 'failed'))->toOthers();
    }

    /**
     * Get a process by ID.
     */
    public function getProcess(string $processId): ?InventoryProcess
    {
        return InventoryProcess::with(['steps', 'category', 'inventoryItem'])
            ->find($processId);
    }

    /**
     * Get user's processes.
     */
    public function getUserProcesses(string $userId, int $limit = 10): array
    {
        return InventoryProcess::where('user_id', $userId)
            ->with(['category', 'inventoryItem'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
