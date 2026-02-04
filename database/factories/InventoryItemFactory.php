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
            'title' => $this->faker->sentence,
            'status' => 'draft',
            'vin' => $this->faker->uuid,
            'stock_number' => $this->faker->bothify('STK-####'),
            'type' => 'Vehicle',
            'category' => 'Cars',
            'year' => $this->faker->year,
            'make' => $this->faker->word,
            'model' => $this->faker->word,
            'price' => $this->faker->numberBetween(5000, 50000),
            'mileage' => $this->faker->numberBetween(1000, 100000),
            'description' => $this->faker->paragraph,
            'color' => $this->faker->colorName,
        ];
    }
}
