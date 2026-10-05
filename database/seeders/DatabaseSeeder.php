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

        User::firstOrCreate(
            ['email' => 'zeffali7@gmai.com'],
            [
                'name' => 'Zeff Ali',
                'password' => bcrypt('secret123'),
                'role' => 'admin',
                'phone' => '9999999999',
            ]
        );

        $this->call([
            LocationSeeder::class,
            TeamSeeder::class,
            PropertySeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
            AmenitySeeder::class,
            PropertyAmenityConfigSeeder::class,
            RoomTypeAmenityConfigSeeder::class,
            RoomConfigurationSeeder::class,
            VehicleSeeder::class,
            ActivitySeeder::class,
            ActivityTransferSeeder::class,
            ImageSeeder::class,
        ]);
    }
}
