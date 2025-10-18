<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SpaceType;

/** @extends Factory<\App\Models\SpaceType> */
class SpaceTypeFactory extends Factory
{
    protected $model = SpaceType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['PRIVATE SPACE','DRAFTING TABLE','CONFERENCE ROOM','SHARED SPACE','EXCLUSIVE SPACE']).' '.strtoupper($this->faker->lexify('?')).$this->faker->randomDigit(),
            'default_price' => $this->faker->randomFloat(2, 20, 500),
            'hourly_rate' => $this->faker->randomFloat(2, 20, 500),
            'default_discount_hours' => $this->faker->optional()->numberBetween(2, 8),
            'default_discount_percentage' => $this->faker->optional()->randomFloat(2, 5, 30),
            'total_slots' => $this->faker->numberBetween(1, 20),
            'available_slots' => $this->faker->numberBetween(0, 20),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
