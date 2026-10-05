<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;
use App\Models\PropertyAmenity;
use App\Models\PropertyAmenityConfig;

class PropertyAmenityConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $properties = Property::all();
        $amenities = PropertyAmenity::all();

        if ($properties->isEmpty() || $amenities->isEmpty()) {
            return;
        }

        // To make it interesting, we'll assign the first 5 amenities 
        // to every property in the database.
        foreach ($properties as $property) {
            
            $selectedAmenities = $amenities->take(5);

            foreach ($selectedAmenities as $amenity) {
                
                PropertyAmenityConfig::firstOrCreate([
                    'property_id'         => $property->id,
                    'property_amenity_id' => $amenity->id,
                    'room_type_id'        => null,
                ]);
                
            }
        }
    }
}
