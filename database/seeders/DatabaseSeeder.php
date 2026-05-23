<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('test123')
        ]);

        User::factory()->create([
            'name' => 'Mateja',
            'email' => 'mateja@gmail.com',
            'password' => bcrypt('mateja123')
        ]);

        $this->call([
            ArrangementSeeder::class,
            BookingSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
