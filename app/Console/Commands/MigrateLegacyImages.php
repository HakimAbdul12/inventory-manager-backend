<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateLegacyImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-legacy-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $items = \App\Models\InventoryItem::all();
        $bar = $this->output->createProgressBar($items->count());

        $this->info("Migrating images for {$items->count()} items...");

        foreach ($items as $item) {
            // Get raw images data to bypass potential accessor/relationship conflicts
            $rawImages = $item->getRawOriginal('images');
            $images = $rawImages ? json_decode($rawImages, true) : [];

            // Also check generated_data just in case
            $generatedData = $item->generated_data ?? [];
            if (empty($images) && isset($generatedData['images'])) {
                $images = $generatedData['images'];
            }

            if (!empty($images) && is_array($images)) {
                foreach ($images as $index => $image) {
                    // Skip if valid URL check fails or if not meaningful data
                    if (!isset($image['url'])) continue;

                    // Check if already exists to avoid duplicates
                    $exists = \App\Models\InventoryImage::where('inventory_item_id', $item->id)
                        ->where('path', $image['storagePath'] ?? $image['url'])
                        ->exists();

                    if ($exists) continue;

                    // Create record
                    $inventoryImage = \App\Models\InventoryImage::create([
                        'inventory_item_id' => $item->id,
                        'path' => $image['storagePath'] ?? $image['url'], // Prefer storage path if available
                        'prompt' => $image['prompt'] ?? null,
                        'generated_by' => $image['generatedBy'] ?? 'legacy',
                        'alt' => $image['alt'] ?? null,
                        'is_primary' => $image['isPrimary'] ?? ($index === 0),
                        'processing_status' => \App\Models\InventoryImage::STATUS_PENDING,
                        'sizes' => null, // Will be generated
                    ]);

                    // Dispatch processing job if it has a local path
                    if (isset($image['storagePath'])) {
                        \App\Jobs\ProcessInventoryImageJob::dispatch($inventoryImage)
                            ->onQueue(config('inventory.queue.name', 'inventory'));
                    }
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Migration completed.');
    }
}
