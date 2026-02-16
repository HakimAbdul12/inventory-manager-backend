<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\PricingHistory;

class PricingEngineService
{
    /**
     * Calculate the PFP (Profit-First Pricing) for a vehicle.
     * Formula: Price = MarketAverage + (VelocityPremium * ConfidenceScore) - CarryingCost_daily
     */
    public function calculatePFP(Vehicle $vehicle): float
    {
        $marketAverage = $vehicle->market_average ?? 0;
        $velocityPremium = $this->getVelocityPremium($vehicle->velocity_score);
        $confidenceScore = ($vehicle->inventoryItem->confidence_score ?? 0) / 100;
        $carryingCostDaily = $vehicle->carrying_cost ?? 0;
        $daysOnLot = $vehicle->dol ?? 0;

        $price = $marketAverage + ($velocityPremium * $confidenceScore) - ($carryingCostDaily * $daysOnLot);

        return round(max($price, $marketAverage * 0.8), 2); // Floor at 80% of market average
    }

    /**
     * Get velocity premium based on score.
     */
    protected function getVelocityPremium(float $velocityScore): float
    {
        // Simple logic: higher velocity = higher premium
        return $velocityScore * 500; // e.g., velocity 5 gives $2500 premium
    }

    /**
     * Update vehicle price and log history.
     */
    public function updatePrice(Vehicle $vehicle, float $newPrice, string $reason, $userId = null): void
    {
        $oldPrice = $vehicle->price;

        PricingHistory::create([
            'vehicle_id' => $vehicle->id,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'market_average_at_time' => $vehicle->market_average,
            'reason' => $reason,
            'changed_by' => $userId,
        ]);

        $vehicle->update(['price' => $newPrice]);
    }
}
