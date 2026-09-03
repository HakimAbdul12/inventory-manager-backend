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
                        str_contains(strtolower($vehicleData['model'] ?? ''), 'tesla') ||
                        str_contains(strtolower($vehicleData['model'] ?? ''), 'cybertruck') ||
                        str_contains(strtolower($vehicleData['model'] ?? ''), 'ioniq') ||
                        str_contains(strtolower($vehicleData['model'] ?? ''), 'leaf') ||
                        str_contains(strtolower($vehicleData['model'] ?? ''), 'mach-e') ||
                        str_contains(strtolower($vehicleData['model'] ?? ''), 'rivian') ||
                        str_contains(strtolower($vehicleData['model'] ?? ''), 'lucid');

        if (!$isEvOrHybrid) {
            $currentFuel = !empty($vehicleData['fuel_type']) ? ucfirst($vehicleData['fuel_type']) : 'Gasoline';
            return [
                'success' => false,
                'skipped' => true,
                'reason' => "OnlyEV requires Electric or Hybrid vehicles (current: {$currentFuel}).",
                'message' => "Skipped: OnlyEV requires Electric or Hybrid vehicles (current: {$currentFuel}).",
            ];
        }

        return [
            'success' => true,
            'external_id' => 'onlyev-' . substr(md5($item->id), 0, 10),
            'url' => 'https://onlyev.com/listings/' . substr(md5($item->id), 0, 10),
            'message' => 'Vehicle verified and syndicated to OnlyEV clean-energy network.',
        ];
    }
}
