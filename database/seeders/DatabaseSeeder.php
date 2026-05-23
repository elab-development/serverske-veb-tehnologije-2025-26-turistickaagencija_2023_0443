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
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin123'),
            'role' => 'admin'
        ]);

        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@gmail.com',
            'password' => bcrypt('manager123'),
            'role' => 'manager'
        ]);

        User::factory()->create([
            'name' => 'User',
            'email' => 'user@gmail.com',
            'password' => bcrypt('user123'),
            'role' => 'user'
        ]);

        $this->call([
            ArrangementSeeder::class,
            BookingSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
