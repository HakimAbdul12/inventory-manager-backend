<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'inventory_item_id' => InventoryItem::factory(),
            'user_id' => User::factory(),
            'vin' => $this->faker->unique()->bothify('VIN################'),
            'make' => $this->faker->randomElement(['Toyota', 'Honda', 'Ford', 'Chevrolet']),
            'model' => $this->faker->word,
            'year' => $this->faker->year,
            'price' => $this->faker->numberBetween(10000, 50000),
            'mileage' => $this->faker->numberBetween(100, 100000),
            'status' => 'available',
        ];
    }
}
