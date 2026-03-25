<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'tenant_id' => \App\Models\Tenant::factory(),
            'process_id' => \App\Models\InventoryProcess::factory(),
            'status' => 'draft',
            'generated_data' => [
                'title' => $this->faker->sentence,
            ],
            'metadata' => [],
        ];
    }
}
