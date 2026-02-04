<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\InventoryItem;
use App\Models\InventoryImage;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTransferJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Transfer $transfer)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->transfer->status !== 'processing') {
            return;
        }

        try {
            DB::transaction(function () {
                $inventoryIds = $this->transfer->inventory_ids;
                $newItems = [];

                if ($this->transfer->type === 'move') {
                    // Move: Update user_id
                    InventoryItem::whereIn('id', $inventoryIds)
                        ->update(['user_id' => $this->transfer->recipient_id]);
                } else {
                    // Duplicate: Create copy
                    $items = InventoryItem::whereIn('id', $inventoryIds)->with('images')->get();

                    foreach ($items as $item) {
                        $newItem = $item->replicate();
                        $newItem->user_id = $this->transfer->recipient_id;
                        $newItem->status = 'draft'; // Reset status for new owner
                        $newItem->save();

                        // Duplicate images
                        foreach ($item->images as $image) {
                            $newImage = $image->replicate();
                            $newImage->inventory_item_id = $newItem->id;
                            $newImage->save();
                        }
                    }
                }

                $this->transfer->update(['status' => 'completed']);
            });
        } catch (\Exception $e) {
            Log::error('Transfer failed: ' . $e->getMessage());
            $this->transfer->update(['status' => 'failed']);
            throw $e;
        }
    }
}
