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
        // 1. Ensure Country exists
        $country = \App\Models\Country::firstOrCreate(
            ['name' => 'India'],
            ['code' => 'IN', 'status' => 1]
        );

        // 2. Ensure State exists
        $state = \App\Models\State::firstOrCreate(
            ['name' => 'Assam', 'country_id' => $country->id],
            ['code' => 'AS', 'status' => 1]
        );

        // 3. Ensure Cities exist and seed Assam Activities
        $citiesData = [
            'Guwahati' => [
                [
                    'name'          => 'Kamakhya Temple Heritage Walk',
                    'category'      => 'Cultural & Spiritual',
                    'description'   => 'A guided spiritual and historical walk around the famous Kamakhya Temple on Nilachal Hill.',
                    'duration'      => 3,
                    'duration_unit' => 'hours',
                    'start_time'    => '07:00',
                    'end_time'      => '10:00',
                    'base_price'    => 1200,
                ],
                [
                    'name'          => 'Brahmaputra Sunset River Cruise',
                    'category'      => 'Leisure',
                    'description'   => 'Enjoy a serene evening cruise on the mighty Brahmaputra river with live music and dinner.',
                    'duration'      => 2,
                    'duration_unit' => 'hours',
                    'start_time'    => '16:30',
                    'end_time'      => '18:30',
                    'base_price'    => 2500,
                ],
            ],
            'Kaziranga' => [
                [
                    'name'          => 'Kaziranga Elephant Safari',
                    'category'      => 'Wildlife & Adventure',
                    'description'   => 'Early morning elephant safari in the central range of Kaziranga National Park to spot one-horned rhinos.',
                    'duration'      => 2,
                    'duration_unit' => 'hours',
                    'start_time'    => '05:30',
                    'end_time'      => '07:30',
                    'base_price'    => 3500,
                ],
                [
                    'name'          => 'Jeep Safari (Western Range)',
                    'category'      => 'Wildlife & Adventure',
                    'description'   => 'Afternoon jeep safari exploring the dense forests and wetlands for tigers and rhinos.',
                    'duration'      => 3,
                    'duration_unit' => 'hours',
                    'start_time'    => '14:00',
                    'end_time'      => '17:00',
                    'base_price'    => 4000,
                ],
            ],
            'Jorhat' => [
                [
                    'name'          => 'Assam Tea Garden & Factory Tour',
                    'category'      => 'Cultural & Experience',
                    'description'   => 'Experience the rich tea heritage of Assam with a guided walk through lush tea estates and a tasting session.',
                    'duration'      => 4,
                    'duration_unit' => 'hours',
                    'start_time'    => '09:00',
                    'end_time'      => '13:00',
                    'base_price'    => 1500,
                ],
                [
                    'name'          => 'Majuli Island Day Trip',
                    'category'      => 'Sightseeing',
                    'description'   => 'Ferry ride and guided tour of Majuli, the world\'s largest river island, known for its Vaishnavite Satras.',
                    'duration'      => 1,
                    'duration_unit' => 'days',
                    'start_time'    => '08:00',
                    'end_time'      => null,
                    'base_price'    => 3000,
                ],
            ]
        ];

        foreach ($citiesData as $cityName => $activities) {
            $city = City::firstOrCreate(
                ['name' => $cityName, 'state_id' => $state->id],
                ['code' => strtoupper(substr(str_replace(' ', '', $cityName), 0, 3)), 'status' => 1]
            );

            foreach ($activities as $actData) {
                Activity::firstOrCreate(
                    [
                        'city_id' => $city->id,
                        'name'    => $actData['name'],
                    ],
                    array_merge($actData, [
                        'country_id' => $country->id,
                        'state_id'   => $state->id,
                        'status'     => 'active',
                    ])
                );
            }
        }

        // 4. Restore the original logic for all OTHER cities
        $allCities = City::with('state')->get();
        foreach ($allCities as $city) {
            // Skip Assam cities because we just seeded specific data for them
            if ($city->state_id == $state->id) {
                continue;
            }

            $stateId = $city->state_id;
            $countryId = $city->state ? $city->state->country_id : null;

            if (!$countryId) {
                continue;
            }

            $activities = [
                [
                    'name'          => 'Historical City Tour - ' . $city->name,
                    'category'      => 'Sightseeing',
                    'description'   => 'A comprehensive guided tour covering the major historical landmarks of ' . $city->name . '.',
                    'duration'      => 4,
                    'duration_unit' => 'hours',
                    'start_time'    => '09:00',
                    'end_time'      => '13:00',
                    'base_price'    => 1500,
                ],
                [
                    'name'          => 'Adventure & Theme Park',
                    'category'      => 'Adventure',
                    'description'   => 'A full day of thrilling rides, extreme sports, and family-friendly adventure activities.',
                    'duration'      => 1,
                    'duration_unit' => 'days',
                    'start_time'    => '10:00',
                    'end_time'      => '18:00',
                    'base_price'    => 4500,
                ],
                [
                    'name'          => 'Authentic Cultural Food Walk',
                    'category'      => 'Food & Drink',
                    'description'   => 'Taste the best local delicacies and street food while walking through the cultural hubs.',
                    'duration'      => 3,
                    'duration_unit' => 'hours',
                    'start_time'    => '17:00',
                    'end_time'      => '20:00',
                    'base_price'    => 2000,
                ]
            ];

            foreach ($activities as $actData) {
                Activity::firstOrCreate(
                    [
                        'city_id' => $city->id,
                        'name'    => $actData['name'],
                    ],
                    array_merge($actData, [
                        'country_id' => $countryId,
                        'state_id'   => $stateId,
                        'status'     => 'active',
                    ])
                );
            }
        }
    }
}
