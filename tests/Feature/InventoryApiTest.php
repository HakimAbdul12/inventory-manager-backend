<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\InventoryItem;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_list_includes_metadata_and_confidence_score()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'Cars',
            'slug' => 'cars',
            'is_active' => true,
        ]);

        $item = InventoryItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Item',
            'status' => 'draft',
            'metadata' => [
                'generation_duration' => 123,
                'generation_completed_at' => now()->toIso8601String(),
            ],
            'confidence_score' => 95.5,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/inventory');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.metadata.generation_duration', 123)
            ->assertJsonPath('data.0.confidence_score', 95.5);
    }
}
