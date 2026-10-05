<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Property;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hotelAdmin = User::where('role', 'hotel_admin')->first();
        if (!$hotelAdmin) return;

        // Helper to safely fetch or create location hierarchy
        $getLocation = function($countryName, $stateName, $cityName) {
            $country = Country::firstOrCreate(
                ['name' => $countryName], 
                ['code' => strtoupper(substr($countryName, 0, 2)), 'status' => 1]
            );
            $state = State::firstOrCreate(
                ['country_id' => $country->id, 'name' => $stateName], 
                ['code' => strtoupper(substr($stateName, 0, 2)), 'status' => 1]
            );
            $city = City::firstOrCreate(
                ['state_id' => $state->id, 'name' => $cityName], 
                ['code' => strtoupper(substr($cityName, 0, 3)), 'status' => 1]
            );
            return ['country_id' => $country->id, 'state_id' => $state->id, 'city_id' => $city->id];
        };

        $properties = [
            [
                'name'              => 'Avani Sukhumvit Bangkok',
                'type'              => 'Hotel',
                'star_rating'       => 5,
                'location'          => ['Thailand', 'Bangkok', 'Phra Nakhon'],
                'description'       => 'A luxury hotel providing premium services.',
                'address'           => 'Sukhumvit Road',
                'postal_code'       => '10260',
                'latitude'          => 13.6907,
                'longitude'         => 100.6125,
                'phone'             => '9876543210',
                'alternative_phone' => '8765432109',
                'email'             => 'reservations@example.com',
                'website'           => 'avani.example.com',
            ],
            [
                'name'              => 'Radisson Blu Hotel',
                'type'              => 'Hotel',
                'star_rating'       => 4,
                'location'          => ['India', 'Assam', 'Guwahati'],
                'description'       => 'Experience absolute comfort in the heart of Guwahati.',
                'address'           => 'NH37, Gotanagar',
                'postal_code'       => '781033',
                'latitude'          => 26.1420,
                'longitude'         => 91.6601,
                'phone'             => '9123456781',
                'alternative_phone' => '9123456782',
                'email'             => 'radisson@example.com',
                'website'           => 'radisson.example.com',
            ],
            [
                'name'              => 'The Taj Mahal Palace',
                'type'              => 'Resort',
                'star_rating'       => 5,
                'location'          => ['India', 'Maharashtra', 'Mumbai'],
                'description'       => 'Iconic sea-facing luxury hotel in Mumbai.',
                'address'           => 'Apollo Bunder',
                'postal_code'       => '400001',
                'latitude'          => 18.9217,
                'longitude'         => 72.8332,
                'phone'             => '9123456783',
                'alternative_phone' => '9123456784',
                'email'             => 'tajmahal@example.com',
                'website'           => 'taj.example.com',
            ],
            [
                'name'              => 'JW Marriott Hotel',
                'type'              => 'Hotel',
                'star_rating'       => 5,
                'location'          => ['Vietnam', 'Hanoi', 'Ba Dinh'],
                'description'       => 'A striking 5-star hotel featuring modern architecture.',
                'address'           => 'Do Duc Duc Road',
                'postal_code'       => '100000',
                'latitude'          => 21.0089,
                'longitude'         => 105.7876,
                'phone'             => '9123456785',
                'alternative_phone' => '9123456786',
                'email'             => 'jwmarriott@example.com',
                'website'           => 'marriott.example.com',
            ],
            [
                'name'              => 'Shangri-La',
                'type'              => 'Hotel',
                'star_rating'       => 4,
                'location'          => ['Malaysia', 'Kuala Lumpur', 'Bukit Bintang'],
                'description'       => 'Your tranquil oasis in the bustling city center.',
                'address'           => 'Jalan Sultan Ismail',
                'postal_code'       => '50250',
                'latitude'          => 3.1537,
                'longitude'         => 101.7061,
                'phone'             => '9123456787',
                'alternative_phone' => '9123456788',
                'email'             => 'shangrila@example.com',
                'website'           => 'shangri-la.example.com',
            ],
        ];

        foreach ($properties as $propData) {
            $loc = $getLocation($propData['location'][0], $propData['location'][1], $propData['location'][2]);

            Property::firstOrCreate(
                ['email' => $propData['email']],
                [
                    'hotel_admin_id'    => $hotelAdmin->id,
                    'name'              => $propData['name'],
                    'type'              => $propData['type'],
                    'star_rating'       => $propData['star_rating'],
                    'country_id'        => $loc['country_id'],
                    'state_id'          => $loc['state_id'],
                    'city_id'           => $loc['city_id'],
                    'description'       => $propData['description'],
                    'address'           => $propData['address'],
                    'postal_code'       => $propData['postal_code'],
                    'latitude'          => $propData['latitude'],
                    'longitude'         => $propData['longitude'],
                    'phone'             => $propData['phone'],
                    'alternative_phone' => $propData['alternative_phone'],
                    'website'           => $propData['website'],
                    'status'            => 'Active',
                ]
            );
        }
    }
}
