<?php

namespace Tests\Feature;

use App\Jobs\ProcessPublishingItemJob;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryPublishingStatus;
use App\Models\PublishingBatch;
use App\Models\PublishingBatchItem;
use App\Models\PublishingPlatform;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Publishing\PublishingManager;
use Database\Seeders\PublishingPlatformsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PublishingPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $dealerUser;
    protected Tenant $tenant;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed platforms
        $this->seed(PublishingPlatformsSeeder::class);

        // Create super admin
        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        // Create dealer user
        $this->dealerUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Create tenant
        $this->tenant = Tenant::create([
            'name' => 'Apex Motors',
            'slug' => 'apex-motors',
            'owner_id' => $this->dealerUser->id,
        ]);

        $this->tenant->addMember($this->dealerUser, 'owner');
        $this->tenant->addMember($this->superAdmin, 'owner');

        $this->dealerUser->update(['current_tenant_id' => $this->tenant->id]);
        $this->superAdmin->update(['current_tenant_id' => $this->tenant->id]);

        $this->category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sedan',
            'slug' => 'sedan',
            'fields' => [],
        ]);
    }

    protected function createVehicle(string $title = '2024 Tesla Model 3'): InventoryItem
    {
        return InventoryItem::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->dealerUser->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'generated_data' => [
                'title' => $title,
                'year' => 2024,
                'make' => 'Tesla',
                'model' => 'Model 3',
                'price' => 45000,
                'fuel_type' => 'Electric',
                'vin' => '5YJ3E1EB8PF123456',
            ],
            'metadata' => [],
        ]);
    }

    public function test_list_publishing_platforms()
    {
        // Deactivate one platform
        PublishingPlatform::where('key', 'tiktok')->update(['is_active' => false]);

        // Dealer should not see inactive platform
        $response = $this->actingAs($this->dealerUser)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/publishing/platforms');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_super_admin', false);

        $keys = collect($response->json('data'))->pluck('key')->toArray();
        $this->assertContains('autotech', $keys);
        $this->assertContains('onlyev', $keys);
        $this->assertContains('google_ads', $keys);
        $this->assertNotContains('tiktok', $keys);

        // Super admin sees all platforms
        $adminRes = $this->actingAs($this->superAdmin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/publishing/platforms');

        $adminRes->assertStatus(200)
            ->assertJsonPath('is_super_admin', true);
        $adminKeys = collect($adminRes->json('data'))->pluck('key')->toArray();
        $this->assertContains('tiktok', $adminKeys);
    }

    public function test_super_admin_can_toggle_platform()
    {
        $response = $this->actingAs($this->superAdmin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->patchJson('/publishing/platforms/onlyev/toggle', ['is_active' => false]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse(PublishingPlatform::where('key', 'onlyev')->value('is_active'));
    }

    public function test_non_super_admin_cannot_toggle_platform()
    {
        $response = $this->actingAs($this->dealerUser)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->patchJson('/publishing/platforms/onlyev/toggle', ['is_active' => false]);

        $response->assertStatus(403);
    }

    public function test_create_publishing_batch_and_dispatch_jobs()
    {
        Queue::fake();

        $v1 = $this->createVehicle('2024 Porsche Taycan');
        $v2 = $this->createVehicle('2023 BMW i4');

        $payload = [
            'inventory_ids' => [$v1->id, $v2->id],
            'platforms' => [
                ['id' => 'autotech', 'format' => 'image'],
                ['id' => 'onlyev', 'format' => 'image'],
                ['id' => 'google_ads', 'format' => 'image'],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/publishing/batches', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_items', 6);

        $batchId = $response->json('data.batch_id');
        $this->assertDatabaseHas('publishing_batches', [
            'id' => $batchId,
            'total_items' => 6,
        ]);

        $this->assertEquals(6, PublishingBatchItem::where('batch_id', $batchId)->count());

        // Initially dispatches 1 job per vehicle (sequential execution)
        Queue::assertPushed(ProcessPublishingItemJob::class, 2);
    }

    public function test_job_processes_and_updates_status()
    {
        $v1 = $this->createVehicle();

        $batch = PublishingBatch::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->dealerUser->id,
            'status' => 'in_progress',
            'total_items' => 1,
            'successful_items' => 0,
            'failed_items' => 0,
        ]);

        $batchItem = PublishingBatchItem::create([
            'batch_id' => $batch->id,
            'inventory_item_id' => $v1->id,
            'platform_key' => 'autotech',
            'format' => 'image',
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
        ]);

        $job = new ProcessPublishingItemJob($batchItem->id);
        $job->handle(app(PublishingManager::class));

        $batchItem->refresh();
        $batch->refresh();

        $this->assertEquals('published', $batchItem->status);
        $this->assertEquals(1, $batch->successful_items);
        $this->assertEquals('completed', $batch->status);

        // Verify sync to InventoryPublishingStatus
        $this->assertDatabaseHas('inventory_publishing_statuses', [
            'tenant_id' => $this->tenant->id,
            'inventory_item_id' => $v1->id,
            'platform_name' => 'autotech',
            'status' => 'success',
        ]);
    }

    public function test_show_batch_detail_with_vehicle_pipeline()
    {
        $v1 = $this->createVehicle('2024 Audi e-tron GT');

        $batch = PublishingBatch::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->dealerUser->id,
            'status' => 'in_progress',
            'total_items' => 2,
            'successful_items' => 1,
            'failed_items' => 0,
        ]);

        PublishingBatchItem::create([
            'batch_id' => $batch->id,
            'inventory_item_id' => $v1->id,
            'platform_key' => 'autotech',
            'format' => 'image',
            'status' => 'published',
            'attempts' => 1,
            'max_attempts' => 3,
        ]);

        PublishingBatchItem::create([
            'batch_id' => $batch->id,
            'inventory_item_id' => $v1->id,
            'platform_key' => 'google_ads',
            'format' => 'image',
            'status' => 'in_progress',
            'attempts' => 1,
            'max_attempts' => 3,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/publishing/batches/{$batch->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.batch.id', $batch->id)
            ->assertJsonPath('data.progress_percent', 50)
            ->assertJsonCount(1, 'data.vehicles')
            ->assertJsonPath('data.vehicles.0.title', '2024 Audi e-tron GT')
            ->assertJsonCount(2, 'data.vehicles.0.platforms');
    }

    public function test_batch_skips_ineligible_platform_rules_onlyev()
    {
        // Gasoline vehicle
        $gasVehicle = InventoryItem::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->dealerUser->id,
            'category_id' => $this->category->id,
            'status' => 'draft',
            'generated_data' => [
                'title' => '2024 Ford Mustang GT',
                'make' => 'Ford',
                'model' => 'Mustang',
                'year' => 2024,
                'fuel_type' => 'Gasoline',
                'price' => 55000,
            ],
            'metadata' => [],
        ]);

        $payload = [
            'inventory_ids' => [$gasVehicle->id],
            'platforms' => [
                ['id' => 'onlyev', 'format' => 'image'],
                ['id' => 'autotech', 'format' => 'image'],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/publishing/batches', $payload);

        $response->assertStatus(201);
        $batchId = $response->json('data.batch_id');

        $onlyEvItem = PublishingBatchItem::where('batch_id', $batchId)
            ->where('platform_key', 'onlyev')
            ->first();

        $this->assertNotNull($onlyEvItem);
        $this->assertEquals('skipped', $onlyEvItem->status);
        $this->assertStringContainsString('OnlyEV requires Electric or Hybrid vehicles', $onlyEvItem->error_message);
    }

    public function test_batch_deduplication_reuses_previous_publishing()
    {
        $v = $this->createVehicle('2024 Rivian R1T');

        // Batch 1 publishes on autotech
        $batch1 = PublishingBatch::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->dealerUser->id,
            'status' => 'completed',
            'total_items' => 1,
            'successful_items' => 1,
            'failed_items' => 0,
        ]);

        PublishingBatchItem::create([
            'batch_id' => $batch1->id,
            'inventory_item_id' => $v->id,
            'platform_key' => 'autotech',
            'format' => 'image',
            'status' => 'published',
            'attempts' => 1,
            'max_attempts' => 3,
        ]);

        // Batch 2 targets autotech and carguru
        $payload = [
            'inventory_ids' => [$v->id],
            'platforms' => [
                ['id' => 'autotech', 'format' => 'image'],
                ['id' => 'carguru', 'format' => 'image'],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/publishing/batches', $payload);

        $response->assertStatus(201);
        $batch2Id = $response->json('data.batch_id');

        $autotechItem = PublishingBatchItem::where('batch_id', $batch2Id)
            ->where('platform_key', 'autotech')
            ->first();

        $this->assertNotNull($autotechItem);
        $this->assertEquals('published', $autotechItem->status);
        $this->assertStringContainsString('Already published', $autotechItem->error_message);
    }

    public function test_get_active_publishing_batch()
    {
        $v = $this->createVehicle('2024 Lucid Air');

        $batch = PublishingBatch::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->dealerUser->id,
            'status' => 'in_progress',
            'total_items' => 2,
            'successful_items' => 1,
            'failed_items' => 0,
        ]);

        PublishingBatchItem::create([
            'batch_id' => $batch->id,
            'inventory_item_id' => $v->id,
            'platform_key' => 'onlyev',
            'format' => 'image',
            'status' => 'in_progress',
            'attempts' => 1,
            'max_attempts' => 3,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/publishing/batches/active');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.batch_id', $batch->id)
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.current_platform', 'onlyev')
            ->assertJsonPath('data.current_vehicle_title', '2024 Lucid Air');
    }
}
