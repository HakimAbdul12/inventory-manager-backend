<?php

namespace App\Console\Commands;

use App\Jobs\GenerateInventoryEmbeddingJob;
use App\Models\InventoryItem;
use Illuminate\Console\Command;

class GenerateInventoryEmbeddingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:generate-embeddings {--force : Re-generate all embeddings even if they exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate vector embeddings for inventory items that missing them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = InventoryItem::withoutGlobalScope('tenant');

        if (!$this->option('force')) {
            $query->whereNull('embedding');
        }

        $items = $query->get();
        $count = $items->count();

        if ($count === 0) {
            $this->info('No inventory items require embedding generation.');
            return;
        }

        $this->info("Found {$count} inventory items to process. Dispatching jobs...");

        $bar = $this->output->createProgressBar($count);

        foreach ($items as $item) {
            // First we need to make sure the vector_string is populated before generating
            // Triggering the save event will trigger the Observer which handles vector_string mapping
            // But if we just want to dispatch manually:
            if (empty($item->vector_string)) {
                // If it's empty, saving it triggers the observer which sets it and dispatches the job
                $item->save();
            } else {
                // Already has vector string, dispatch directly
                GenerateInventoryEmbeddingJob::dispatch($item);
            }
            $bar->advance();
        }

        $bar->finish();
        
        $this->newLine();
        $this->info('All jobs dispatched successfully!');
    }
}
