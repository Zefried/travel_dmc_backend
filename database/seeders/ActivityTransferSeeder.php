<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;
use App\Models\ActivityTransfer;

class ActivityTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Fetch all previously seeded activities
        $activities = Activity::all();

        if ($activities->isEmpty()) {
            return;
        }

        foreach ($activities as $activity) {
            
            // Standardizing 2 transfers for each activity
            $transfers = [
                [
                    'name'                   => 'Private VIP Transfer',
                    'transfer_type'          => 'Private',
                    'transfer_duration'      => 60,
                    'transfer_duration_unit' => 'Minutes',
                    'transfer_price'         => 1500,
                    'pickup_type'            => 'Hotel Pickup',
                    'pickup_description'     => 'Direct door-to-door transfer from your hotel lobby in a private vehicle.',
                    'status'                 => 'Active',
                ],
                [
                    'name'                   => 'Shared Shuttle Bus',
                    'transfer_type'          => 'Shared',
                    'transfer_duration'      => 90,
                    'transfer_duration_unit' => 'Minutes',
                    'transfer_price'         => 500,
                    'pickup_type'            => 'Designated Point',
                    'pickup_description'     => 'Pick up from designated meeting points in the city center. Expect multiple stops.',
                    'status'                 => 'Active',
                ]
            ];

            foreach ($transfers as $transfer) {
                ActivityTransfer::firstOrCreate(
                    [
                        'activity_id' => $activity->id,
                        'name'        => $transfer['name'],
                    ],
                    $transfer
                );
            }
        }
    }
}
