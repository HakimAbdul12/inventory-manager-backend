<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'is_super_admin' => true, // Super admin bypasses all RBAC for full tool access
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Apex Motors',
            'slug' => 'apex-motors',
            'owner_id' => $this->user->id,
        ]);

        $this->tenant->addMember($this->user, 'owner');
        $this->user->update(['current_tenant_id' => $this->tenant->id]);
    }

    public function test_mcp_initialize_handshake()
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-03-26',
                'clientInfo' => [
                    'name' => 'claude-desktop',
                    'version' => '1.0.0',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $payload);

        $response->assertStatus(200)
            ->assertHeader('Mcp-Session-Id')
            ->assertJsonPath('jsonrpc', '2.0')
            ->assertJsonPath('id', 1)
            ->assertJsonPath('result.protocolVersion', '2025-03-26')
            ->assertJsonStructure([
                'result' => [
                    'protocolVersion',
                    'capabilities' => ['tools', 'resources'],
                    'serverInfo' => ['name', 'version'],
                    'instructions',
                ],
            ]);
    }

    public function test_mcp_tools_list()
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('jsonrpc', '2.0')
            ->assertJsonPath('id', 2)
            ->assertJsonStructure([
                'result' => [
                    'tools' => [
                        '*' => ['name', 'description', 'inputSchema'],
                    ],
                ],
            ]);

        $tools = collect($response->json('result.tools'))->pluck('name')->toArray();
        $this->assertContains('search_inventory', $tools);
        $this->assertContains('list_categories', $tools);
        $this->assertContains('create_lead', $tools);
        $this->assertContains('book_test_drive', $tools);
    }

    public function test_mcp_tools_call_list_categories()
    {
        Category::create([
            'name' => 'Electric Vehicles',
            'slug' => 'evs',
            'fields' => [],
        ]);

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_categories',
                'arguments' => [],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('jsonrpc', '2.0')
            ->assertJsonPath('id', 3)
            ->assertJsonPath('result.isError', false);

        $text = $response->json('result.content.0.text');
        $this->assertStringContainsString('Electric Vehicles', $text);
    }

    public function test_mcp_tools_call_search_inventory()
    {
        $category = Category::create([
            'name' => 'Sedans',
            'slug' => 'sedans',
            'fields' => [],
        ]);

        InventoryItem::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'generated_data' => [
                'make' => 'Porsche',
                'model' => 'Taycan',
                'year' => 2024,
                'price' => 89000,
            ],
        ]);

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => [
                'name' => 'search_inventory',
                'arguments' => ['query' => 'Taycan'],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('result.isError', false);

        $text = $response->json('result.content.0.text');
        $this->assertStringContainsString('Porsche', $text);
        $this->assertStringContainsString('Taycan', $text);
    }

    public function test_mcp_tools_call_create_inventory_item()
    {
        Category::create([
            'name' => 'Wagons',
            'slug' => 'wagons',
            'fields' => [],
        ]);

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 5,
            'method' => 'tools/call',
            'params' => [
                'name' => 'create_inventory_item',
                'arguments' => [
                    'make' => 'Audi',
                    'model' => 'RS6 Avant',
                    'year' => 2024,
                    'price' => 125000,
                    'status' => 'published',
                    'body_type' => 'Wagon',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('result.isError', false);

        $text = $response->json('result.content.0.text');
        $this->assertStringContainsString('Audi RS6 Avant', $text);
        $this->assertStringContainsString('published', $text);
    }

    public function test_mcp_tools_call_publish_inventory_item()
    {
        \App\Models\PublishingPlatform::create([
            'name' => 'OnlyEV',
            'key' => 'onlyev',
            'is_active' => true,
        ]);
        \App\Models\PublishingPlatform::create([
            'name' => 'CarGurus',
            'key' => 'cargurus',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Sedans',
            'slug' => 'sedans',
            'fields' => [],
        ]);

        InventoryItem::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'status' => 'draft',
            'generated_data' => [
                'title' => '2024 Porsche Taycan 4S',
                'make' => 'Porsche',
                'model' => 'Taycan',
                'year' => 2024,
                'fuel_type' => 'Electric',
                'price' => 110000,
            ],
        ]);

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 6,
            'method' => 'tools/call',
            'params' => [
                'name' => 'publish_inventory_item',
                'arguments' => [
                    'query' => 'Taycan',
                    'platforms' => ['onlyev', 'cargurus'],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('result.isError', false);

        $text = $response->json('result.content.0.text');
        $this->assertStringContainsString('Taycan', $text);
        $this->assertStringContainsString('Publishing batch initiated', $text);
    }

    public function test_mcp_resources_list_and_read()
    {
        $listPayload = [
            'jsonrpc' => '2.0',
            'id' => 5,
            'method' => 'resources/list',
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $listPayload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'result' => [
                    'resources' => [
                        '*' => ['uri', 'name', 'description', 'mimeType'],
                    ],
                ],
            ]);

        // Read permissions resource
        $readPayload = [
            'jsonrpc' => '2.0',
            'id' => 6,
            'method' => 'resources/read',
            'params' => ['uri' => 'inventory://tenant/config'],
        ];

        $readResponse = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $readPayload);

        $readResponse->assertStatus(200);
        $content = $readResponse->json('result.contents.0.text');
        $this->assertStringContainsString('Apex Motors', $content);
    }

    public function test_mcp_dashboard_tools_metadata()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/mcp/tools');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tools' => [
                    '*' => ['name', 'description', 'category', 'required_permission', 'has_access', 'input_schema'],
                ],
                'grouped',
                'total_count',
            ]);
    }

    public function test_mcp_dashboard_connection_info()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/mcp/connection-info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'endpoint',
                'transport',
                'authentication',
                'example_config',
            ]);
    }

    public function test_mcp_create_lead_and_update_status()
    {
        // 1. Create lead via MCP tool
        $createPayload = [
            'jsonrpc' => '2.0',
            'id' => 10,
            'method' => 'tools/call',
            'params' => [
                'name' => 'create_lead',
                'arguments' => [
                    'first_name' => 'Michael',
                    'last_name' => 'Scott',
                    'email' => 'michael@dundermifflin.com',
                    'phone' => '+15551234567',
                    'notes' => 'Looking for a convertible',
                ],
            ],
        ];

        $createRes = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $createPayload);

        $createRes->assertStatus(200);
        $this->assertDatabaseHas('leads', [
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Michael',
            'email' => 'michael@dundermifflin.com',
            'status' => 'new',
        ]);

        $lead = \App\Models\Lead::where('email', 'michael@dundermifflin.com')->first();
        $this->assertNotNull($lead);

        // 2. Update lead status via MCP tool
        $updatePayload = [
            'jsonrpc' => '2.0',
            'id' => 11,
            'method' => 'tools/call',
            'params' => [
                'name' => 'update_lead_status',
                'arguments' => [
                    'id' => $lead->id,
                    'status' => 'contacted',
                    'notes' => 'Spoke on the phone about convertibles',
                ],
            ],
        ];

        $updateRes = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $updatePayload);

        $updateRes->assertStatus(200);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'contacted',
        ]);

        $this->assertDatabaseHas('lead_status_history', [
            'lead_id' => $lead->id,
            'from_status' => 'new',
            'to_status' => 'contacted',
        ]);
    }

    public function test_mcp_permission_denied_for_unauthorized_user()
    {
        // Create viewer user with no permissions
        $viewer = User::factory()->create(['is_super_admin' => false]);
        $this->tenant->addMember($viewer, 'viewer');
        $viewer->update(['current_tenant_id' => $this->tenant->id]);

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 20,
            'method' => 'tools/call',
            'params' => [
                'name' => 'create_inventory_item',
                'arguments' => [
                    'make' => 'Ferrari',
                    'model' => 'Roma',
                    'year' => 2024,
                ],
            ],
        ];

        $response = $this->actingAs($viewer)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $payload);

        $response->assertStatus(200);
        $this->assertTrue($response->json('result.isError'));
        $this->assertStringContainsString('Permission denied', $response->json('result.content.0.text'));
    }

    public function test_mcp_tools_call_create_inventory_item_bulk()
    {
        $category = Category::create([
            'name' => 'Sports Cars',
            'slug' => 'sports-cars',
            'fields' => [],
        ]);

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 30,
            'method' => 'tools/call',
            'params' => [
                'name' => 'create_inventory_item',
                'arguments' => [
                    'items' => [
                        [
                            'make' => 'Ferrari',
                            'model' => '296 GTB',
                            'year' => 2024,
                            'price' => 340000,
                            'fuel_type' => 'Hybrid',
                            'category_id' => $category->id,
                        ],
                        [
                            'make' => 'McLaren',
                            'model' => '750S',
                            'year' => 2024,
                            'price' => 330000,
                            'fuel_type' => 'Gasoline',
                            'category_id' => $category->id,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('result.isError', false);

        $data = json_decode($response->json('result.content.0.text'), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(2, $data['count']);
        $this->assertCount(2, $data['items']);
        $this->assertCount(2, $data['inventory_ids']);

        $this->assertDatabaseHas('inventory_items', [
            'tenant_id' => $this->tenant->id,
            'generated_data->make' => 'Ferrari',
            'generated_data->model' => '296 GTB',
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'tenant_id' => $this->tenant->id,
            'generated_data->make' => 'McLaren',
            'generated_data->model' => '750S',
        ]);
    }

    public function test_mcp_tools_call_publish_inventory_item_multi_vehicle_single_batch()
    {
        \App\Models\PublishingPlatform::create([
            'name' => 'CarGurus',
            'key' => 'cargurus',
            'is_active' => true,
        ]);
        \App\Models\PublishingPlatform::create([
            'name' => 'AutoTech',
            'key' => 'autotech',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Luxury',
            'slug' => 'luxury',
            'fields' => [],
        ]);

        $car1 = InventoryItem::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'status' => 'draft',
            'generated_data' => [
                'title' => '2024 Aston Martin DB12',
                'make' => 'Aston Martin',
                'model' => 'DB12',
                'year' => 2024,
                'fuel_type' => 'Gasoline',
            ],
        ]);

        $car2 = InventoryItem::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'status' => 'draft',
            'generated_data' => [
                'title' => '2024 Bentley Continental GT',
                'make' => 'Bentley',
                'model' => 'Continental GT',
                'year' => 2024,
                'fuel_type' => 'Gasoline',
            ],
        ]);

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 31,
            'method' => 'tools/call',
            'params' => [
                'name' => 'publish_inventory_item',
                'arguments' => [
                    'inventory_ids' => [$car1->id, $car2->id],
                    'platforms' => ['cargurus', 'autotech'],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/mcp', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('result.isError', false);

        $data = json_decode($response->json('result.content.0.text'), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(2, $data['batch']['vehicles_count']);
        // 2 vehicles * 2 platforms = 4 total tasks
        $this->assertEquals(4, $data['batch']['total_tasks']);

        // Assert exactly ONE single batch was created
        $batchId = $data['batch']['id'];
        $batch = \App\Models\PublishingBatch::find($batchId);
        $this->assertNotNull($batch);
        $this->assertEquals(4, $batch->total_items);
        $this->assertEquals(2, $batch->metadata['vehicles_count']);

        // Assert all vehicles marked published
        $this->assertEquals('published', $car1->fresh()->status);
        $this->assertEquals('published', $car2->fresh()->status);
    }
}


