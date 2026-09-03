<?php

namespace App\Jobs;

use App\Events\PublishingItemStatusUpdated;
use App\Models\InventoryItem;
use App\Models\InventoryPublishingStatus;
use App\Models\PublishingBatchItem;
use App\Services\Publishing\PublishingManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPublishingItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1; // We handle retries internally within the job flow for live socket streaming

    public function __construct(
        public string $batchItemId
    ) {}

    public function handle(PublishingManager $manager): void
    {
        $batchItem = PublishingBatchItem::with(['batch', 'inventoryItem'])->find($this->batchItemId);
        if (!$batchItem) {
            return;
        }

        $item = $batchItem->inventoryItem;
        $batch = $batchItem->batch;
        $platformKey = $batchItem->platform_key;
        $maxAttempts = $batchItem->max_attempts ?: 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            // Update attempt count and mark in_progress
            $batchItem->update([
                'status' => 'in_progress',
                'attempts' => $attempt,
                'last_attempted_at' => now(),
            ]);

            $batch->updateProgress();

            // Broadcast that publishing is now in progress for this platform/vehicle
            $this->broadcastUpdate($batchItem, [
                'message' => "Attempting upload to {$platformKey} (Attempt {$attempt} of {$maxAttempts})...",
            ]);

            // Simulated realistic API latency (5 seconds as requested)
            // If in PHPUnit test environment, skip sleep to keep tests fast
            if (!app()->environment('testing')) {
                sleep(5);
            }

            try {
                // Rare simulated transient failure on early attempt (1 in 50 chance, or hard failure test flag)
                $shouldFail = !app()->environment('testing') && rand(1, 100) === 42 && $attempt < $maxAttempts;

                if ($shouldFail) {
                    throw new \Exception("Temporary gateway rate-limit from {$platformKey} API.");
                }

                // Resolve platform service and execute
                $service = $manager->getService($platformKey);
                $result = $service->publish($item, ['format' => $batchItem->format]);

                if (!empty($result['skipped'])) {
                    // Platform skipped due to platform business rule (e.g., non-EV on OnlyEV)
                    $batchItem->update([
                        'status' => 'skipped',
                        'error_message' => $result['reason'] ?? $result['message'] ?? 'Skipped by platform rule.',
                    ]);

                    $batch->updateProgress();

                    $this->broadcastUpdate($batchItem, [
                        'message' => $result['message'] ?? 'Skipped by platform rule.',
                    ]);

                    // Trigger the next sequential platform for this vehicle
                    $this->dispatchNextItem($batchItem);

                    return; // Done with skipped
                }

                if (!empty($result['success'])) {
                    // Success!
                    $batchItem->update([
                        'status' => 'published',
                        'error_message' => null,
                    ]);

                    // Ensure InventoryItem status is published
                    $item->update(['status' => InventoryItem::STATUS_PUBLISHED]);

                    // Sync with legacy InventoryPublishingStatus table for VDP tab
                    InventoryPublishingStatus::updateOrCreate(
                        [
                            'tenant_id' => $batch->tenant_id,
                            'inventory_item_id' => $item->id,
                            'platform_name' => $platformKey,
                        ],
                        [
                            'status' => 'success',
                            'last_sync_at' => now(),
                            'error_message' => null,
                        ]
                    );

                    $batch->updateProgress();

                    $this->broadcastUpdate($batchItem, [
                        'message' => $result['message'] ?? 'Published successfully.',
                        'url' => $result['url'] ?? null,
                    ]);

                    // Trigger the next sequential platform for this vehicle
                    $this->dispatchNextItem($batchItem);

                    return; // Done
                }

                throw new \Exception($result['message'] ?? "Platform {$platformKey} returned failure.");
            } catch (\Throwable $e) {
                Log::warning("Publishing task {$batchItem->id} failed attempt {$attempt}: " . $e->getMessage());

                if ($attempt < $maxAttempts) {
                    // Brief delay before retry
                    if (!app()->environment('testing')) {
                        sleep(2);
                    }
                    continue; // Try again
                }

                // All attempts exhausted
                $batchItem->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                InventoryPublishingStatus::updateOrCreate(
                    [
                        'tenant_id' => $batch->tenant_id,
                        'inventory_item_id' => $item->id,
                        'platform_name' => $platformKey,
                    ],
                    [
                        'status' => 'failed',
                        'last_sync_at' => now(),
                        'error_message' => $e->getMessage(),
                    ]
                );

                $batch->updateProgress();

                $this->broadcastUpdate($batchItem, [
                    'message' => "Failed after {$maxAttempts} attempts: " . $e->getMessage(),
                ]);

                // Continue to the next platform even if this one failed
                $this->dispatchNextItem($batchItem);
            }
        }
    }

    /**
     * Dispatch the next sequential platform item for the same vehicle in this batch.
     */
    protected function dispatchNextItem(PublishingBatchItem $currentItem): void
    {
        $nextItem = PublishingBatchItem::where('batch_id', $currentItem->batch_id)
            ->where('inventory_item_id', $currentItem->inventory_item_id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($nextItem) {
            $nextItem->update([
                'status' => 'in_progress',
                'attempts' => 1,
                'last_attempted_at' => now(),
            ]);

            $this->broadcastUpdate($nextItem, [
                'message' => "Publishing to {$nextItem->platform_key}...",
            ]);

            ProcessPublishingItemJob::dispatch($nextItem->id);
        }
    }

    /**
     * Safely dispatch real-time broadcast without crashing the queue worker if Reverb is offline
     */
    protected function broadcastUpdate(PublishingBatchItem $batchItem, array $extra = []): void
    {
        rescue(function () use ($batchItem, $extra) {
            event(new PublishingItemStatusUpdated($batchItem, $extra));
        }, report: false);
    }
}
