<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryPublishingStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedPublishingStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:seed-publishing {item_id? : The ID of the inventory item}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed publishing status for inventory items for testing purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $itemId = $this->argument('item_id');

        if ($itemId) {
            $item = InventoryItem::find($itemId);
            if (!$item) {
                $this->error("Inventory item not found.");
                return 1;
            }
            $this->seedForItem($item);
        } else {
            $items = InventoryItem::all();
            foreach ($items as $item) {
                $this->seedForItem($item);
            }
        }

        $this->info("Successfully seeded publishing statuses.");
        return 0;
    }

    protected function seedForItem(InventoryItem $item)
    {
        if (!$item->tenant_id) {
            $this->warn("Skipping item {$item->id} because it has no tenant_id.");
            return;
        }

        $platforms = [
            ['name' => 'Facebook Marketplace', 'slug' => 'facebook'],
            ['name' => 'Instagram', 'slug' => 'instagram'],
            ['name' => 'TikTok', 'slug' => 'tiktok'],
            ['name' => 'AutoTrader', 'slug' => 'autotrader'],
            ['name' => 'CarGurus', 'slug' => 'cargurus'],
            ['name' => 'Cars.com', 'slug' => 'cars_com'],
        ];

        $statuses = ['synced', 'pending', 'error', 'removed'];

        // Assign to a random subset of platforms
        $count = rand(2, 5);
        $selectedPlatforms = array_rand($platforms, $count);
        if (!is_array($selectedPlatforms)) $selectedPlatforms = [$selectedPlatforms];

        foreach ($selectedPlatforms as $index) {
            $platform = $platforms[$index];
            $status = $statuses[array_rand($statuses)];
            
            InventoryPublishingStatus::updateOrCreate(
                [
                    'inventory_item_id' => $item->id,
                    'platform_name' => $platform['slug'],
                ],
                [
                    'tenant_id' => $item->tenant_id,
                    'status' => $status,
                    'last_sync_at' => $status === 'synced' ? now()->subHours(rand(1, 48)) : null,
                    'error_message' => $status === 'error' ? 'Connection timeout with platform API.' : null,
                ]
            );
        }
    }
}
