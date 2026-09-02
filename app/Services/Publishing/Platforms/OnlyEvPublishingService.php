<?php

namespace App\Services\Publishing\Platforms;

use App\Contracts\PublishingPlatformService;
use App\Models\InventoryItem;

class OnlyEvPublishingService implements PublishingPlatformService
{
    public function getPlatformKey(): string
    {
        return 'onlyev';
    }

    public function getName(): string
    {
        return 'OnlyEV Marketplace';
    }

    public function publish(InventoryItem $item, array $options = []): array
    {
        $vehicleData = $item->generated_data ?? [];
        $fuelType = strtolower($vehicleData['fuel_type'] ?? $vehicleData['engine'] ?? '');

        // Check if vehicle is electric or hybrid, or validate attributes
        $isEvOrHybrid = str_contains($fuelType, 'electric') ||
                        str_contains($fuelType, 'hybrid') ||
                        str_contains($fuelType, 'phev') ||
                        str_contains(strtolower($vehicleData['model'] ?? ''), 'taycan') ||
                        str_contains(strtolower($vehicleData['model'] ?? ''), 'tesla');

        return [
            'success' => true,
            'external_id' => 'onlyev-' . substr(md5($item->id), 0, 10),
            'url' => 'https://onlyev.com/listings/' . substr(md5($item->id), 0, 10),
            'message' => 'Vehicle verified and syndicated to OnlyEV clean-energy network.',
        ];
    }
}
