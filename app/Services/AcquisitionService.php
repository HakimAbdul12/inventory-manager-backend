<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcquisitionService
{
    /**
     * Analyze dealer's historical turn rate and recommend inventory to buy.
     */
    public function getRecommendations(): array
    {
        // 1. Calculate Average DOL and Margin by Make/Model
        $performance = DB::table('vehicles')
            ->select(
                'make',
                'model',
                'year',
                DB::raw('AVG(dol) as avg_dol'),
                DB::raw('AVG(price - market_average) as avg_margin_gap')
            )
            ->where('status', 'sold')
            ->groupBy('make', 'model', 'year')
            ->having('avg_dol', '<', 15) // Sold in under 15 days
            ->get();

        Log::info("Generating predictive acquisition recommendations based on " . count($performance) . " historical high-performers.");

        // 2. Mock scan of integrated auction feeds (e.g. Manheim, Adesa)
        $recommendations = [];
        foreach ($performance as $item) {
            $recommendations[] = [
                'make' => $item->make,
                'model' => $item->model,
                'year' => $item->year,
                'priority' => 'High',
                'reason' => "Sells in average {$item->avg_dol} days with positive margin trajectory.",
                'auction_source' => 'Manheim OVE',
                'target_msrp' => round($item->avg_margin_gap > 0 ? 30000 : 25000), // Mock logic
            ];
        }

        // Fallback if no history
        if (empty($recommendations)) {
            $recommendations[] = [
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2024,
                'priority' => 'Recommended',
                'reason' => "Consistent high market velocity score (8.5/10) in your region.",
                'auction_source' => 'Integrated Feed',
                'target_msrp' => 28000,
            ];
        }

        return $recommendations;
    }

    /**
     * Handle "One-Click Exit" logic.
     */
    public function proposeExit(Vehicle $vehicle): array
    {
        $exitStrategies = [];

        // D2D Internal Transfer
        $exitStrategies[] = [
            'type' => 'D2D Transfer',
            'channel' => 'Internal Dealer Network',
            'suggested_price' => $vehicle->price * 0.95,
            'logistics_quote' => 450.00, // Integrated Central Dispatch mock
        ];

        // Wholesale Liquidity
        $exitStrategies[] = [
            'type' => 'Wholesale Push',
            'channel' => 'Local Wholesale Buyers',
            'suggested_price' => $vehicle->market_average * 0.85,
            'potential_exit_time' => '24-48 Hours',
        ];

        return $exitStrategies;
    }
}
