<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\Vehicle;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillVehicles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-vehicles 
                            {--force : Overwrite existing vehicle records}
                            {--clear : Truncate the vehicles table before starting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Vehicle records for existing InventoryItems in the cars category';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('clear')) {
            $this->warn('Truncating vehicles table...');
            Vehicle::truncate();
            $this->info('Vehicles table truncated.');
        }

        $category = Category::where('slug', 'cars')->first();

        if (!$category) {
            $this->error('Cars category not found. Please ensure categories are seeded.');
            return 1;
        }

        $items = InventoryItem::where('category_id', $category->id)->get();

        if ($items->isEmpty()) {
            $this->info('No inventory items found in the cars category.');
            return 0;
        }

        $this->info("Found {$items->count()} items to process.");
        $bar = $this->output->createProgressBar($items->count());

        $adminUser = \App\Models\User::where('email', 'admin@example.com')->first()
            ?? \App\Models\User::first();

        if (!$adminUser) {
            $this->error('No users found in the system. Cannot attribute vehicles.');
            return 1;
        }

        foreach ($items as $item) {
            $exists = Vehicle::where('inventory_item_id', $item->id)
                ->orWhere('id', $item->id)
                ->first();

            if ($exists && !$this->option('force')) {
                $bar->advance();
                continue;
            }

            $data = $item->generated_data ?? [];
            $userId = is_numeric($item->user_id) ? $item->user_id : $adminUser->id;
            $vin = $data['vin'] ?? 'UNKNOWN-' . strtoupper(Str::random(10));

            // Handle VIN collisions
            if (Vehicle::where('vin', $vin)->where('inventory_item_id', '!=', $item->id)->exists()) {
                $vin .= '-' . strtoupper(Str::random(4));
            }

            Vehicle::updateOrCreate(
                ['inventory_item_id' => $item->id],
                [
                    'id' => $item->id, // Share ID for seamless VDP integration
                    'user_id' => $userId,
                    'vin' => $vin,
                    'make' => $data['make'] ?? 'Unknown',
                    'model' => $data['model'] ?? 'Unknown',
                    'year' => $data['year'] ?? date('Y'),
                    'price' => $item->metadata['price'] ?? 0,
                    'mileage' => $data['mileage'] ?? 0,
                    'market_average' => 30000,
                    'velocity_score' => 5.0,
                    'carrying_cost' => 15.00,
                    'status' => 'active',
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill completed successfully.');

        return 0;
    }
}
