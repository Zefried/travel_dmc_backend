<?php
$users = \App\Models\User::where('role', 'vehicle_admin')->get(['id', 'name'])->toArray();
$vehicles = \App\Models\Vehicle::get(['id', 'vehicle_admin_id', 'name'])->toArray();

echo "--- Vehicle Admins ---\n";
print_r($users);
echo "\n--- Vehicles ---\n";
print_r($vehicles);
