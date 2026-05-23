<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Arrangement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $arrangement = Arrangement::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();

        $people = $this->faker->numberBetween(1,5);
        $price = $arrangement->price;
        if($arrangement->discount_percent > 0){
            $price -= $price * ($arrangement->discount_percent / 100);
        }
        $total = ceil($price*$people);

        return [
            'user_id' => $user->id,
            'arrangement_id' => $arrangement->id,
            'number_of_people' => $people,
            'total_price' => $total,
            'travel_date' => $this->faker->dateTimeBetween('+1 days', '+30 days'),
        ];
    }
}
