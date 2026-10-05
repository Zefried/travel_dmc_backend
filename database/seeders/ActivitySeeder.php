<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;
use App\Models\Activity;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Fetch all cities, pre-loading state so we can access country_id easily
        $cities = City::with('state')->get();

        if ($cities->isEmpty()) {
            return;
        }

        foreach ($cities as $city) {
            $stateId = $city->state_id;
            $countryId = $city->state ? $city->state->country_id : null;

            if (!$countryId) {
                continue; // Skip if location hierarchy is somehow broken
            }

            // Define some standard realistic activities for each city
            $activities = [
                [
                    'name'          => 'Historical City Tour - ' . $city->name,
                    'category'      => 'Sightseeing',
                    'description'   => 'A comprehensive guided tour covering the major historical landmarks of ' . $city->name . '.',
                    'duration'      => 4,
                    'duration_unit' => 'hours',
                    'base_price'    => 1500,
                ],
                [
                    'name'          => 'Adventure & Theme Park',
                    'category'      => 'Adventure',
                    'description'   => 'A full day of thrilling rides, extreme sports, and family-friendly adventure activities.',
                    'duration'      => 1,
                    'duration_unit' => 'days',
                    'base_price'    => 4500,
                ],
                [
                    'name'          => 'Authentic Cultural Food Walk',
                    'category'      => 'Food & Drink',
                    'description'   => 'Taste the best local delicacies and street food while walking through the cultural hubs.',
                    'duration'      => 3,
                    'duration_unit' => 'hours',
                    'base_price'    => 2000,
                ]
            ];

            // Safely insert activities
            foreach ($activities as $actData) {
                Activity::firstOrCreate(
                    [
                        'city_id' => $city->id,
                        'name'    => $actData['name'],
                    ],
                    array_merge($actData, [
                        'country_id' => $countryId,
                        'state_id'   => $stateId,
                        'status'     => 'Active',
                    ])
                );
            }
        }
    }
}
