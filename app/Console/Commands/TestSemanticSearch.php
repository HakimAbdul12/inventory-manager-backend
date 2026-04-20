<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Chat\InventorySearchService;
use App\Models\InventoryItem;

class TestSemanticSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:test-search {query} {--tenant= : Optional tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mock a semantic search request using the AI vector logic';

    protected InventorySearchService $searchService;

    public function __construct(InventorySearchService $searchService)
    {
        parent::__construct();
        $this->searchService = $searchService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->argument('query');
        $tenantId = $this->option('tenant') ?? InventoryItem::first()?->tenant_id;

        if (!$tenantId) {
            $this->error('No tenant ID found. Please create an inventory item first.');
            return 1;
        }

        $this->info("🔍 Searching for: \"{$query}\" (Tenant: {$tenantId})");
        $this->comment("Calling Hugging Face API & performing vector similarity match...");

        $start = microtime(true);
        $results = $this->searchService->searchFromMessage($query, $tenantId, 5);
        $duration = round((microtime(true) - $start) * 1000, 2);

        if (empty($results)) {
            $this->warn('No results found.');
            return 0;
        }

        $this->info("Found " . count($results) . " results in {$duration}ms:");

        $rows = [];
        foreach ($results as $item) {
            $rows[] = [
                $item['id'],
                $item['title'],
                $item['price'] ?? 'N/A',
                $item['mileage'] ?? 'N/A',
                $item['status']
            ];
        }

        $this->table(
            ['ID', 'Title', 'Price', 'Mileage', 'Status'],
            $rows
        );

        return 0;
    }
}
