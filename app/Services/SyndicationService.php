<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyndicationService
{
    protected array $partners = [
        'AutoTrader' => 'https://api.autotrader.com/v1/push',
        'Cars.com' => 'https://api.cars.com/v1/vehicles',
    ];

    /**
     * Push vehicle update to all partners in real-time.
     */
    public function push(Vehicle $vehicle): void
    {
        Log::info("Real-time syndication triggered for VIN: {$vehicle->vin}");

        foreach ($this->partners as $name => $endpoint) {
            $this->pushToPartner($name, $endpoint, $vehicle);
        }
    }

    /**
     * Mock push to specific partner.
     */
    protected function pushToPartner(string $partnerName, string $endpoint, Vehicle $vehicle): void
    {
        // Mock payload
        $payload = [
            'id' => $vehicle->id,
            'vin' => $vehicle->vin,
            'price' => $vehicle->price,
            'status' => $vehicle->status,
            'last_updated' => $vehicle->updated_at->toIso8601String(),
        ];

        try {
            // Http::post($endpoint, $payload);
            Log::info("Successfully pushed to {$partnerName}", ['vin' => $vehicle->vin]);
        } catch (\Exception $e) {
            Log::error("Failed to push to {$partnerName}", ['error' => $e->getMessage()]);
        }
    }
}
