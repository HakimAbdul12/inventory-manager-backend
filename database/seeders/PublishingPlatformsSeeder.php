<?php

namespace Database\Seeders;

use App\Models\PublishingPlatform;
use Illuminate\Database\Seeder;

class PublishingPlatformsSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            [
                'key' => 'autotech',
                'name' => 'Autotech',
                'description' => 'Official direct dealership marketplace & public showroom',
                'icon_url' => '/assets/media/app/internal/logos/autotech.png',
                'color' => 'bg-zinc-900 text-white',
                'supported_types' => ['image', 'video'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'onlyev',
                'name' => 'OnlyEV',
                'description' => 'Specialized electric & hybrid vehicle marketplace',
                'icon_url' => '/onlyev.svg',
                'color' => 'bg-emerald-600 text-white',
                'supported_types' => ['image'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'google_ads',
                'name' => 'Google Ads',
                'description' => 'Google Vehicle Ads & Smart Performance Max campaigns',
                'icon_url' => '/google-ads.svg',
                'color' => 'bg-blue-600 text-white',
                'supported_types' => ['image'],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'key' => 'facebook_ads',
                'name' => 'Facebook Ads',
                'description' => 'Meta Automotive Inventory Ads with retargeting',
                'icon_url' => '/marketplace-facebook.svg',
                'color' => 'bg-blue-700 text-white',
                'supported_types' => ['image', 'video'],
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'tiktok',
                'name' => 'TikTok',
                'description' => 'Short-form vehicle showcases & photo mode',
                'icon_url' => '/tiktok.svg',
                'color' => 'bg-black text-white',
                'supported_types' => ['video', 'image'],
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'key' => 'instagram',
                'name' => 'Instagram',
                'description' => 'Reels, Stories, and Carousels showcase',
                'icon_url' => '/instagram.svg',
                'color' => 'bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500 text-white',
                'supported_types' => ['video', 'image'],
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'key' => 'facebook',
                'name' => 'Facebook Marketplace',
                'description' => 'Local buyer marketplace vehicle listings',
                'icon_url' => '/marketplace-facebook.svg',
                'color' => 'bg-blue-600 text-white',
                'supported_types' => ['image'],
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'key' => 'autotrader',
                'name' => 'AutoTrader',
                'description' => 'Nationwide consumer vehicle listings network',
                'icon_url' => '/autotrader.svg',
                'color' => 'bg-orange-600 text-white',
                'supported_types' => ['image'],
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'key' => 'carguru',
                'name' => 'CarGurus',
                'description' => 'Price analysis and buyer deal rating listings',
                'icon_url' => '/carguru.svg',
                'color' => 'bg-orange-600 text-white',
                'supported_types' => ['image'],
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'key' => 'car.com',
                'name' => 'Cars.com',
                'description' => 'High-intent car shopper inventory syndication',
                'icon_url' => '/car.png',
                'color' => 'bg-purple-600 text-white',
                'supported_types' => ['image'],
                'is_active' => true,
                'sort_order' => 10,
            ],
        ];

        foreach ($platforms as $platform) {
            PublishingPlatform::updateOrCreate(
                ['key' => $platform['key']],
                $platform
            );
        }
    }
}
