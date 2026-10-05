<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TeamSeeder extends Seeder
{
    public function run()
    {
        // 1. The Main Master Admin
        User::firstOrCreate(
            ['email' => 'zeffali7@gmai.com'],
            [
                'name'     => 'Master Admin',
                'password' => Hash::make('secret123'),
                'role'     => 'admin',
                'phone'    => '9000000001',
            ]
        );

        // 2. The Known Hotel Admin (used in previous property tests)
        User::firstOrCreate(
            ['email' => 'vishal@123'],
            [
                'name'     => 'Vishal (Hotel Admin)',
                'password' => Hash::make('secret123'),
                'role'     => 'hotel_admin',
                'phone'    => '8800828701',
            ]
        );

        // 3. Other required roles (matching Routes/index.tsx protections)
        $otherRoles = [
            'vehicle_admin' => 'Vehicle Admin',
            'subadmin'      => 'Sub Admin',
            'agent'         => 'System Agent',
            'user'          => 'Regular User',
            'department'    => 'Department Admin',
        ];

        $phoneCounter = 9000000002;

        foreach ($otherRoles as $roleKey => $roleName) {
            User::firstOrCreate(
                ['email' => $roleKey . '@example.com'],
                [
                    'name'     => $roleName,
                    'password' => Hash::make('secret123'),
                    'role'     => $roleKey,
                    'phone'    => (string)$phoneCounter,
                ]
            );
            $phoneCounter++;
        }
    }
}
