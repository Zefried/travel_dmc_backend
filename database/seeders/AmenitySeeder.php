<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyAmenity;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $amenities = [
            // Wellness
            ['category' => 'Wellness', 'name' => 'Finnish Sauna', 'description' => 'A traditional Finnish dry heat sauna.', 'status' => 'Active'],
            ['category' => 'Wellness', 'name' => 'Spa', 'description' => 'Full-service spa offering massages and facials.', 'status' => 'Active'],
            ['category' => 'Wellness', 'name' => 'Fitness Center', 'description' => 'State-of-the-art gym equipment.', 'status' => 'Active'],
            
            // General
            ['category' => 'General', 'name' => 'Free WiFi', 'description' => 'High-speed wireless internet access.', 'status' => 'Active'],
            ['category' => 'General', 'name' => 'Parking', 'description' => 'On-site secure parking facilities.', 'status' => 'Active'],
            ['category' => 'General', 'name' => 'Swimming Pool', 'description' => 'Outdoor temperature controlled pool.', 'status' => 'Active'],
            
            // Room
            ['category' => 'Room', 'name' => 'Air Conditioning', 'description' => 'Individual climate control.', 'status' => 'Active'],
            ['category' => 'Room', 'name' => 'Mini Bar', 'description' => 'Fully stocked mini bar with premium beverages.', 'status' => 'Active'],
            ['category' => 'Room', 'name' => 'Room Service', 'description' => '24/7 in-room dining service.', 'status' => 'Active'],
            ['category' => 'Room', 'name' => 'Safe Deposit Box', 'description' => 'In-room electronic safe for valuables.', 'status' => 'Active'],
        ];

        foreach ($amenities as $amenity) {
            PropertyAmenity::firstOrCreate(
                ['name' => $amenity['name']],
                $amenity
            );
        }
    }
}
