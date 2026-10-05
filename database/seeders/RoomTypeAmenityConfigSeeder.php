<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomType;
use App\Models\PropertyAmenity;
use App\Models\PropertyAmenityConfig;

class RoomTypeAmenityConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roomTypes = RoomType::all();
        
        // Let's specifically fetch the amenities categorized under "Room" 
        // to assign to the Room Types.
        $roomAmenities = PropertyAmenity::where('category', 'Room')->get();

        if ($roomTypes->isEmpty() || $roomAmenities->isEmpty()) {
            return;
        }

        foreach ($roomTypes as $roomType) {
            
            foreach ($roomAmenities as $amenity) {
                
                PropertyAmenityConfig::firstOrCreate([
                    'room_type_id'        => $roomType->id,
                    'property_amenity_id' => $amenity->id,
                ], [
                    'property_id'         => null,
                ]);
                
            }
        }
    }
}
