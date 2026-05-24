<?php

namespace Database\Factories;

use App\Models\Arrangement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Arrangement>
 */
class ArrangementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->randomElement(['Letovanje', 'Zimovanje', 'Putovanje', 'Leto za mlade']),
            'destination' => $this->faker->randomElement(['Cairo', 'Rome', 'Istanbul', 'Athens']),
            'price' => $this->faker->randomElement([300,500,700,800,900,1000]),
            'duration_days' => $this->faker->numberBetween(3,14),
            'discount_percent' => $this->faker->randomElement([0,10,15,20]),
            'is_last_minute' => $this->faker->boolean(),
        ];
    }
}
