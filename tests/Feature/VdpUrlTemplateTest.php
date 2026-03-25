<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Vehicle;
use App\Models\WorkspaceChatConfig;
use App\Services\Chat\InventorySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VdpUrlTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_vdp_url_placeholder_replacement()
    {
        $user = \App\Models\User::factory()->create();
        $tenant = \App\Models\Tenant::factory()->create(['owner_id' => $user->id]);
        $tenantId = $tenant->id;
        
        // Create config with VDP URL template
        WorkspaceChatConfig::create([
            'tenant_id' => $tenantId,
            'bot_name' => 'Test Bot',
            'bot_personality' => 'professional',
            'greeting_message' => 'Hi',
            'ai_aggressiveness' => 'balanced',
            'widget_settings' => [
                'vdp_url_template' => 'https://dealer.com/vehicle/{{make}}/{{model}}/{{vin}}'
            ],
            'is_active' => true,
        ]);

        // Create process
        $process = \App\Models\InventoryProcess::factory()->create(['tenant_id' => $tenantId]);
        
        // Create inventory item first
        $item = InventoryItem::factory()->create([
            'tenant_id' => $tenantId,
            'process_id' => $process->id,
            'status' => InventoryItem::STATUS_PUBLISHED,
            'generated_data' => [
                'color' => 'Red'
            ]
        ]);

        // Create vehicle associated with inventory item
        Vehicle::factory()->create([
            'inventory_item_id' => $item->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'vin' => 'VIN123456789'
        ]);

        $service = new InventorySearchService();
        $results = $service->search($tenantId, [], 1);

        $this->assertNotEmpty($results);
        $this->assertEquals('https://dealer.com/vehicle/Toyota/Camry/VIN123456789', $results[0]['vdp_url']);
        
        // Check CTA
        $viewDetailsCta = collect($results[0]['cta'])->firstWhere('action', 'view_details');
        $this->assertEquals('https://dealer.com/vehicle/Toyota/Camry/VIN123456789', $viewDetailsCta['url']);
    }

    public function test_vdp_url_works_with_generated_data()
    {
        $user = \App\Models\User::factory()->create();
        $tenant = \App\Models\Tenant::factory()->create(['owner_id' => $user->id]);
        $tenantId = $tenant->id;
        
        WorkspaceChatConfig::create([
            'tenant_id' => $tenantId,
            'bot_name' => 'Test Bot',
            'bot_personality' => 'professional',
            'greeting_message' => 'Hi',
            'ai_aggressiveness' => 'balanced',
            'widget_settings' => [
                'vdp_url_template' => 'https://dealer.com/{{color}}/{{vin}}'
            ],
            'is_active' => true,
        ]);

        // Create process
        $process = \App\Models\InventoryProcess::factory()->create(['tenant_id' => $tenantId]);
        
        // Create inventory item first
        $item = InventoryItem::factory()->create([
            'tenant_id' => $tenantId,
            'process_id' => $process->id,
            'status' => InventoryItem::STATUS_PUBLISHED,
            'generated_data' => [
                'color' => 'Blue'
            ]
        ]);

        // Create vehicle associated with inventory item
        Vehicle::factory()->create([
            'inventory_item_id' => $item->id,
            'vin' => 'VIN987654321'
        ]);

        $service = new InventorySearchService();
        $results = $service->search($tenantId, [], 1);

        $this->assertEquals('https://dealer.com/Blue/VIN987654321', $results[0]['vdp_url']);
    }
}
