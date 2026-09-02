<?php

namespace App\Contracts;

use App\Models\InventoryItem;

interface PublishingPlatformService
{
    /**
     * Platform unique identifier (e.g., autotech, onlyev, google_ads, facebook_ads)
     */
    public function getPlatformKey(): string;

    /**
     * Human readable name
     */
    public function getName(): string;

    /**
     * Execute vehicle publishing to this platform.
     *
     * @param InventoryItem $item
     * @param array $options format ('image' | 'video'), etc.
     * @return array [ 'success' => bool, 'external_id' => ?string, 'url' => ?string, 'message' => string ]
     */
    public function publish(InventoryItem $item, array $options = []): array;
}
