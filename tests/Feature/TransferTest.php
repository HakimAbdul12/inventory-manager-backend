<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Transfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_dealer_can_search_for_other_dealers()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create(['dealer_code' => 'DLR-RECV']);
        $recipient->assignRole('dealer');

        $response = $this->actingAs($sender)->postJson('/api/transfers/search', [
            'code' => 'DLR-RECV'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $recipient->id);
    }

    public function test_dealer_can_initiate_transfer()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $items = InventoryItem::factory()->count(3)->create();

        $response = $this->actingAs($sender)->postJson('/api/transfers', [
            'recipient_id' => $recipient->id,
            'inventory_ids' => $items->pluck('id')->toArray(),
            'type' => 'duplicate'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transfers', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'type' => 'duplicate',
            'status' => 'pending'
        ]);
    }

    public function test_recipient_can_accept_transfer()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $items = InventoryItem::factory()->count(1)->create(['user_id' => $sender->id]);

        $transfer = Transfer::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'inventory_ids' => $items->pluck('id'),
            'type' => 'move',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($recipient)->postJson("/api/transfers/{$transfer->id}/accept");

        $response->assertStatus(200);
        $this->assertDatabaseHas('transfers', ['id' => $transfer->id, 'status' => 'processing']);
    }
}
