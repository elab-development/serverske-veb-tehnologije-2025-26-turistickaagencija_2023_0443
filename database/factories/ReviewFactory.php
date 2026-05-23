<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\User;
use App\Models\Arrangement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first();
        $arrangement = Arrangement::inRandomOrder()->first();
        
        return [
            'user_id' => $user->id,
            'arrangement_id' => $arrangement->id,
            'rating' => $this->faker->numberBetween(1,5),
            'comment' => $this->faker->sentence()    
        ];
    }
}
