<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryItem;
use App\Models\Category;

class PopulateNewInventoryFields extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carCategory = Category::where('slug', 'cars')->first();
        if (!$carCategory) {
            $this->command->error('Cars category not found.');
            return;
        }

        $items = InventoryItem::where('category_id', $carCategory->id)->get();

        foreach ($items as $item) {
            $generatedData = $item->generated_data ?? [];

            // Skip if already populated to avoid overwriting user data
            if (isset($generatedData['isOneTimePaymentAvailable'])) {
                continue;
            }

            $generatedData['isOneTimePaymentAvailable'] = true;
            $generatedData['isNegotiable'] = true;
            $generatedData['isLeaseAvailable'] = rand(0, 1) === 1;

            if ($generatedData['isLeaseAvailable']) {
                $generatedData['leaseMonthsRemaining'] = rand(12, 36);
                $generatedData['leaseTerms'] = [
                    'Maintenance included',
                    '20,000 km per year allowance',
                    'Option to purchase at end of term'
                ];
            } else {
                $generatedData['leaseMonthsRemaining'] = null;
                $generatedData['leaseTerms'] = [];
            }

            $generatedData['isFinancingAvailable'] = true;
            $generatedData['financingTerms'] = 'Up to 60 months with 4.9% APR. Subject to credit approval.';

            $item->generated_data = $generatedData;
            $item->save();
        }

        $this->command->info('Populated ' . $items->count() . ' items with new fields.');
    }
}
