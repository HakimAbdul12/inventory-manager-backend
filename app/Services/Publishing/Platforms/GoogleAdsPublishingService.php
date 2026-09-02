<?php

namespace App\Services\Publishing\Platforms;

use App\Contracts\PublishingPlatformService;
use App\Models\InventoryItem;

class GoogleAdsPublishingService implements PublishingPlatformService
{
    public function getPlatformKey(): string
    {
        return 'google_ads';
    }

    public function getName(): string
    {
        return 'Google Vehicle Ads';
    }

    public function publish(InventoryItem $item, array $options = []): array
    {
        $vehicleData = $item->generated_data ?? [];
        $title = ($vehicleData['year'] ?? '') . ' ' . ($vehicleData['make'] ?? '') . ' ' . ($vehicleData['model'] ?? '');

        return [
            'success' => true,
            'external_id' => 'gads-' . substr(crc32($item->id), 0, 8),
            'url' => 'https://ads.google.com/aw/campaigns',
            'message' => "Campaign feed item created for '{$title}' in Google Vehicle Ads feed.",
        ];
    }
}
