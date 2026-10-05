<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomType;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roomTypes = RoomType::all();

        if ($roomTypes->isEmpty()) {
            return; // Safety check in case RoomTypes aren't seeded
        }

        foreach ($roomTypes as $roomType) {
            
            // Generate a sensible prefix based on the RoomType name (e.g., 'D' for Deluxe)
            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $roomType->name), 0, 1)) ?: 'R';
            
            // Create 3 physical rooms for this specific room type
            for ($i = 1; $i <= 3; $i++) {
                
                // Generates room numbers like D101, E201, S301, etc.
                $roomNo = sprintf("%s%d%02d", $prefix, $roomType->id, $i);

                Room::firstOrCreate(
                    [
                        'room_type_id' => $roomType->id,
                        'room_no'      => $roomNo,
                    ],
                    [
                        'status'       => 'Active',
                    ]
                );
            }
        }
    }
}
