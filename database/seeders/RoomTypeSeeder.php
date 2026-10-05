<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;
use App\Models\RoomType;

class RoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $properties = Property::all();

        if ($properties->isEmpty()) {
            return; // Safety check in case properties aren't seeded
        }

        // Define our standard room types
        $roomTypes = [
            [
                'name'                 => 'Deluxe 2 BHK',
                'type'                 => 'Deluxe',
                'bedroom'              => 2,
                'size'                 => 1389,
                'size_unit'            => 'sq_ft',
                'max_adults'           => 2,
                'max_children'         => 0,
                'max_occupancy'        => 4,
                'description'          => 'A spacious deluxe 2 BHK room with premium amenities.',
                'view'                 => 'City Skyline',
                'default_bed_type'     => 'King',
                'default_bed_quantity' => 1,
                'status'               => 'Active',
                'base_price'           => 5000,
            ],
            [
                'name'                 => 'Executive Suite',
                'type'                 => 'Suite',
                'bedroom'              => 1,
                'size'                 => 850,
                'size_unit'            => 'sq_ft',
                'max_adults'           => 2,
                'max_children'         => 1,
                'max_occupancy'        => 3,
                'description'          => 'An elegant suite tailored for business travelers.',
                'view'                 => 'Ocean View',
                'default_bed_type'     => 'Queen',
                'default_bed_quantity' => 1,
                'status'               => 'Active',
                'base_price'           => 7500,
            ],
            [
                'name'                 => 'Standard Family Room',
                'type'                 => 'Standard',
                'bedroom'              => 2,
                'size'                 => 1100,
                'size_unit'            => 'sq_ft',
                'max_adults'           => 4,
                'max_children'         => 2,
                'max_occupancy'        => 6,
                'description'          => 'A comfortable room perfect for family stays.',
                'view'                 => 'Garden',
                'default_bed_type'     => 'Double',
                'default_bed_quantity' => 2,
                'status'               => 'Active',
                'base_price'           => 4000,
            ],
        ];

        // Assign these room types to every property
        foreach ($properties as $property) {
            foreach ($roomTypes as $roomTypeData) {
                RoomType::firstOrCreate(
                    [
                        'property_id' => $property->id,
                        'name'        => $roomTypeData['name'],
                    ],
                    $roomTypeData
                );
            }
        }
    }
}
