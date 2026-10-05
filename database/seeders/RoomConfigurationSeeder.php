<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomType;
use App\Models\RoomConfiguration;

class RoomConfigurationSeeder extends Seeder
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
            return;
        }

        foreach ($roomTypes as $roomType) {
            
            // Generate 2 Bed Configurations per Room Type
            $bedConfigs = [
                [
                    'type'        => 'bed',
                    'name'        => '1 King Bed',
                    'meal_code'   => null,
                    'description' => 'A spacious King size bed for ultimate comfort.',
                    'extra_price' => 0,
                ],
                [
                    'type'        => 'bed',
                    'name'        => '2 Twin Beds',
                    'meal_code'   => null,
                    'description' => 'Two separate twin beds, ideal for sharing.',
                    'extra_price' => 200,
                ],
            ];

            // Generate 3 Meal Configurations per Room Type
            $mealConfigs = [
                [
                    'type'        => 'meal',
                    'name'        => 'Room Only',
                    'meal_code'   => 'RO',
                    'description' => 'No meals included in this basic plan.',
                    'extra_price' => 0,
                ],
                [
                    'type'        => 'meal',
                    'name'        => 'Breakfast Included',
                    'meal_code'   => 'BB',
                    'description' => 'Includes a daily continental breakfast buffet.',
                    'extra_price' => 800,
                ],
                [
                    'type'        => 'meal',
                    'name'        => 'Half Board',
                    'meal_code'   => 'HB',
                    'description' => 'Includes breakfast and a choice of lunch or dinner.',
                    'extra_price' => 1500,
                ],
            ];

            // Combine both configuration types
            $configs = array_merge($bedConfigs, $mealConfigs);

            foreach ($configs as $configData) {
                
                RoomConfiguration::firstOrCreate(
                    [
                        'room_type_id' => $roomType->id,
                        'type'         => $configData['type'],
                        'name'         => $configData['name'],
                    ],
                    [
                        'meal_code'   => $configData['meal_code'],
                        'description' => $configData['description'],
                        'extra_price' => $configData['extra_price'],
                        'status'      => 'Active',
                    ]
                );
                
            }
        }
    }
}
