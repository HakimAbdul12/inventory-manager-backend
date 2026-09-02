<?php

namespace App\Jobs;

use App\Models\InventoryItem;
use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateInventoryEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public InventoryItem $inventoryItem
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EmbeddingService $embeddingService): void
    {
        // Vector string generated and saved by observer
        $textToEmbed = $this->inventoryItem->vector_string;

        if (empty($textToEmbed)) {
            Log::warning("No vector_string available for generating embedding on InventoryItem {$this->inventoryItem->id}");
            return;
        }

        try {
            $embeddingArray = $embeddingService->generateEmbedding($textToEmbed);

            if ($embeddingArray) {
                // Laravel's DB query builder can save vectors directly by formatting it as '[0.1, 0.2, ...]'
                $vectorString = '[' . implode(',', $embeddingArray) . ']';
                
                $isPgsql = DB::getDriverName() === 'pgsql';
                DB::table('inventory_items')
                    ->where('id', $this->inventoryItem->id)
                    ->update([
                        'embedding' => $isPgsql ? DB::raw("'" . $vectorString . "'::vector") : $vectorString
                    ]);

                Log::info("Successfully generated and saved embedding for InventoryItem {$this->inventoryItem->id}");
            } else {
                Log::error("Embedding generation returned null for InventoryItem {$this->inventoryItem->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to generate embedding for InventoryItem {$this->inventoryItem->id}: " . $e->getMessage());
            throw $e; // Retry if failed
        }
    }
}
