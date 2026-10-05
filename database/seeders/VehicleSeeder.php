<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Fetch existing vehicle admins
        $vehicleAdmins = User::where('role', 'vehicle_admin')->get();
        
        // Ensure we have at least 3 vehicle admins
        $needed = 3 - $vehicleAdmins->count();
        if ($needed > 0) {
            for ($i = 1; $i <= $needed; $i++) {
                $uniqueSuffix = time() . $i; // Ensure completely unique to avoid constraint issues
                User::firstOrCreate(
                    ['email' => 'vehicleadmin' . $uniqueSuffix . '@test.com'],
                    [
                        'name' => 'Vehicle Admin ' . $i,
                        'phone' => '91' . str_pad($uniqueSuffix, 8, '0', STR_PAD_LEFT),
                        'password' => bcrypt('secret123'),
                        'role' => 'vehicle_admin',
                    ]
                );
            }
            // Re-fetch after creation
            $vehicleAdmins = User::where('role', 'vehicle_admin')->get();
        }

        // Add 3 vehicles for each admin
        foreach ($vehicleAdmins as $index => $admin) {
            for ($v = 1; $v <= 3; $v++) {
                // Generate a reliable unique registration number (e.g. AS01AB1234)
                $regNo = 'AS' . str_pad($index + 1, 2, '0', STR_PAD_LEFT) . 'AB' . str_pad($v . $admin->id, 4, '0', STR_PAD_LEFT);
                
                Vehicle::firstOrCreate(
                    ['registration_no' => $regNo],
                    [
                        'vehicle_admin_id' => $admin->id,
                        'type'             => $v % 2 == 0 ? 'Sedan' : 'SUV',
                        'name'             => $v % 2 == 0 ? 'City Transfer' : 'Airport Shuttle',
                        'model'            => $v % 2 == 0 ? 'Honda City' : 'Toyota Innova',
                        'seating_capacity' => $v % 2 == 0 ? 4 : 7,
                        'color'            => $v == 3 ? 'Black' : 'White',
                        'driver_name'      => 'Driver ' . $admin->id . '-' . $v,
                        'driver_phone'     => '98' . str_pad($admin->id, 4, '0', STR_PAD_LEFT) . str_pad($v, 4, '0', STR_PAD_LEFT),
                        'status'           => 'active',
                    ]
                );
            }
        }
    }
}
