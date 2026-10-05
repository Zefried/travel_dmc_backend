<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $locations = [
            'India' => [
                'code' => 'IN',
                'states' => [
                    'Maharashtra' => ['code' => 'MH', 'cities' => ['Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Aurangabad']],
                    'Karnataka' => ['code' => 'KA', 'cities' => ['Bengaluru', 'Mysuru', 'Mangaluru', 'Hubballi', 'Belagavi']],
                    'Delhi' => ['code' => 'DL', 'cities' => ['New Delhi', 'North Delhi', 'South Delhi', 'East Delhi', 'West Delhi']],
                    'Tamil Nadu' => ['code' => 'TN', 'cities' => ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem']],
                    'Gujarat' => ['code' => 'GJ', 'cities' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar']],
                ],
            ],
            'Thailand' => [
                'code' => 'TH',
                'states' => [
                    'Bangkok' => ['code' => 'BKK', 'cities' => ['Phra Nakhon', 'Dusit', 'Bang Rak', 'Pathum Wan', 'Chatuchak']],
                    'Chiang Mai' => ['code' => 'CMI', 'cities' => ['Mueang Chiang Mai', 'Fang', 'Mae Rim', 'San Kamphaeng', 'Hang Dong']],
                    'Phuket' => ['code' => 'PKT', 'cities' => ['Phuket Town', 'Patong', 'Karon', 'Kata', 'Thalang']],
                    'Chon Buri' => ['code' => 'CBI', 'cities' => ['Pattaya', 'Si Racha', 'Sattahip', 'Ban Bueng', 'Phan Thong']],
                    'Surat Thani' => ['code' => 'SNI', 'cities' => ['Koh Samui', 'Koh Phangan', 'Mueang Surat Thani', 'Chaiya', 'Don Sak']],
                ],
            ],
            'Vietnam' => [
                'code' => 'VN',
                'states' => [
                    'Hanoi' => ['code' => 'HN', 'cities' => ['Ba Dinh', 'Hoan Kiem', 'Tay Ho', 'Dong Da', 'Hai Ba Trung']],
                    'Ho Chi Minh City' => ['code' => 'SG', 'cities' => ['District 1', 'District 3', 'District 5', 'District 7', 'Tan Binh']],
                    'Da Nang' => ['code' => 'DN', 'cities' => ['Hai Chau', 'Thanh Khe', 'Son Tra', 'Ngu Hanh Son', 'Lien Chieu']],
                    'Quang Nam' => ['code' => 'QN', 'cities' => ['Hoi An', 'Tam Ky', 'Dien Ban', 'Duy Xuyen', 'Nui Thanh']],
                    'Khanh Hoa' => ['code' => 'KH', 'cities' => ['Nha Trang', 'Cam Ranh', 'Ninh Hoa', 'Dien Khanh', 'Van Ninh']],
                ],
            ],
            'Malaysia' => [
                'code' => 'MY',
                'states' => [
                    'Kuala Lumpur' => ['code' => 'KL', 'cities' => ['Bukit Bintang', 'Cheras', 'Kepong', 'Setapak', 'Wangsa Maju']],
                    'Selangor' => ['code' => 'SGR', 'cities' => ['Petaling Jaya', 'Subang Jaya', 'Shah Alam', 'Klang', 'Sepang']],
                    'Penang' => ['code' => 'PNG', 'cities' => ['George Town', 'Butterworth', 'Bayan Lepas', 'Balik Pulau', 'Nibong Tebal']],
                    'Johor' => ['code' => 'JHR', 'cities' => ['Johor Bahru', 'Batu Pahat', 'Muar', 'Kluang', 'Kota Tinggi']],
                    'Sabah' => ['code' => 'SBH', 'cities' => ['Kota Kinabalu', 'Sandakan', 'Tawau', 'Lahad Datu', 'Keningau']],
                ],
            ],
        ];

        foreach ($locations as $countryName => $countryData) {
            $country = Country::firstOrCreate(
                ['name' => $countryName],
                ['code' => $countryData['code'], 'status' => 1]
            );

            foreach ($countryData['states'] as $stateName => $stateData) {
                $state = State::firstOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $stateName,
                    ],
                    [
                        'code' => $stateData['code'],
                        'status' => 1,
                    ]
                );

                foreach ($stateData['cities'] as $cityName) {
                    City::firstOrCreate(
                        [
                            'state_id' => $state->id,
                            'name' => $cityName,
                        ],
                        [
                            'code' => strtoupper(substr(str_replace(' ', '', $cityName), 0, 3)),
                            'status' => 1,
                        ]
                    );
                }
            }
        }
    }
}
