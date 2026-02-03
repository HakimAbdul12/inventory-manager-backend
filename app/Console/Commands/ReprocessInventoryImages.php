<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReprocessInventoryImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reprocess-inventory-images';

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
        $this->info('Starting image reprocessing...');

        $images = \App\Models\InventoryImage::all();
        $count = $images->count();
        $bar = $this->output->createProgressBar($count);

        $this->info("Found {$count} images to process.");

        foreach ($images as $image) {
            // Reset status to pending so job picks it up as new attempt
            $image->update([
                'processing_status' => \App\Models\InventoryImage::STATUS_PENDING,
            ]);

            // Check if file exists locally before dispatching to warn immediately
            if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($image->path)) {
                $this->error("File missing for Image ID {$image->id}: {$image->path}");
                // We still dispatch the job so it logs the failure consistently in its own flow
                // or we could skip it. Dispatching ensures logic remains in one place.
            }

            \App\Jobs\ProcessInventoryImageJob::dispatch($image)
                ->onQueue(config('inventory.queue.name', 'inventory'));

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All jobs dispatched. Check logs for details.');
    }
}
