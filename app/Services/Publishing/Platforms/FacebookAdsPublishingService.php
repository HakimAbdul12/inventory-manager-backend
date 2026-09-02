<?php

namespace App\Services\Publishing\Platforms;

use App\Contracts\PublishingPlatformService;
use App\Models\InventoryItem;

class FacebookAdsPublishingService implements PublishingPlatformService
{
    public function getPlatformKey(): string
    {
        return 'facebook_ads';
    }

    public function getName(): string
    {
        return 'Facebook Automotive Inventory Ads';
    }

    public function publish(InventoryItem $item, array $options = []): array
    {
        $format = $options['format'] ?? 'image';

        return [
            'success' => true,
            'external_id' => 'fb-aia-' . substr(md5($item->id . '-fb'), 0, 12),
            'url' => 'https://business.facebook.com/commerce_manager',
            'message' => "Automotive catalog updated in Meta Business Suite ({$format} creative).",
        ];
    }
}
