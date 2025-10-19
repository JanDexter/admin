<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Space;
use App\Models\SpaceType;

/** @extends Factory<\App\Models\Space> */
class SpaceFactory extends Factory
{
    protected $model = Space::class;

    public function definition(): array
    {
        return [
            'space_type_id' => SpaceType::factory(),
            'name' => 'SPACE ' . strtoupper($this->faker->lexify('??')) . $this->faker->numberBetween(1, 99),
            'status' => $this->faker->randomElement(['available', 'occupied']),
            'hourly_rate' => $this->faker->randomFloat(2, 20, 500),
            'discount_hours' => $this->faker->optional()->numberBetween(2, 8),
            'discount_percentage' => $this->faker->optional()->randomFloat(2, 5, 30),
        ];
    }
}
