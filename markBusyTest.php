<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBusySchedule;

function testPrint($name, $status, $details = '') {
    $color = $status ? "\e[32m" : "\e[31m";
    $mark = $status ? "✓" : "✗";
    $reset = "\e[0m";
    echo "{$color}{$mark} {$name}{$reset}" . ($details ? " - $details" : "") . "\n";
}

function apiRequest($method, $uri, $token = null, $data = []) {
    // Clear previous auth state
    auth()->guard('sanctum')->forgetUser();
    
    $req = Illuminate\Http\Request::create($uri, $method, $data);
    $req->headers->set('Accept', 'application/json');
    if ($token) {
        $req->headers->set('Authorization', "Bearer $token");
    }
    
    // Create fresh app instance to avoid container pollution if necessary, 
    // or just handle it. Forgetting user is usually enough.
    $response = app()->handle($req);
    return [
        'code' => $response->getStatusCode(),
        'body' => json_decode($response->getContent(), true)
    ];
}

// Ensure users exist
$vAdmin = User::firstOrCreate(
    ['email' => 'test_vehicle_admin@test.com'],
    ['name' => 'Test VAdmin', 'password' => bcrypt('secret123'), 'role' => 'vehicle_admin', 'phone' => '8000000001', 'status' => 'active']
);

$otherAdmin = User::firstOrCreate(
    ['email' => 'other_vadmin@test.com'],
    ['name' => 'Other Admin', 'password' => bcrypt('secret123'), 'role' => 'vehicle_admin', 'phone' => '8000000002', 'status' => 'active']
);

$masterAdmin = User::firstOrCreate(
    ['email' => 'test_master@test.com'],
    ['name' => 'Test Master', 'password' => bcrypt('secret123'), 'role' => 'admin', 'phone' => '8000000003', 'status' => 'active']
);

// Get tokens
$loginVAdmin = apiRequest('POST', '/api/login', null, ['email' => $vAdmin->email, 'password' => 'secret123']);
$vToken = $loginVAdmin['body']['data']['token'] ?? null;
testPrint("Login as Vehicle Admin", $vToken !== null, json_encode($loginVAdmin['body']));

$loginAdmin = apiRequest('POST', '/api/login', null, ['email' => $masterAdmin->email, 'password' => 'secret123']);
$adminToken = $loginAdmin['body']['data']['token'] ?? null;
testPrint("Login as Master Admin", $adminToken !== null, json_encode($loginAdmin['body']));

// Create vehicles
$myVehicle = Vehicle::updateOrCreate(
    ['registration_no' => 'TEST01'],
    ['vehicle_admin_id' => $vAdmin->id, 'type' => 'Car', 'name' => 'My Active Car', 'status' => 'active']
);
$inactiveVehicle = Vehicle::updateOrCreate(
    ['registration_no' => 'TEST02'],
    ['vehicle_admin_id' => $vAdmin->id, 'type' => 'Car', 'name' => 'My Inactive Car', 'status' => 'inactive']
);
$otherVehicle = Vehicle::updateOrCreate(
    ['registration_no' => 'TEST03'],
    ['vehicle_admin_id' => $otherAdmin->id, 'type' => 'Car', 'name' => 'Other Car', 'status' => 'active']
);

echo "\n--- DEBUG IDs ---\n";
echo "vAdmin ID: {$vAdmin->id}, myVehicle Admin ID: {$myVehicle->vehicle_admin_id}\n";
echo "-----------------\n";

// Clear schedules
VehicleBusySchedule::truncate();

echo "\n--- ACCESS & OWNERSHIP ---\n";
$res = apiRequest('POST', '/api/vehicle/mark-busy', $adminToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'reason' => 'Maintenance']);
testPrint("Admin cannot use API", $res['code'] === 403 || $res['code'] === 401, "Expected 403/401, got {$res['code']}");

$res = apiRequest('POST', '/api/vehicle/mark-busy', null, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'reason' => 'Maintenance']);
testPrint("Unauthenticated rejected", $res['code'] === 401);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $otherVehicle->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'reason' => 'Maintenance']);
testPrint("Cannot mark other admin's vehicle", $res['code'] === 403);

echo "\n--- VEHICLE VALIDATION ---\n";
$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => 999999, 'start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'reason' => 'Maintenance']);
testPrint("Non-existent vehicle rejected", $res['code'] === 422);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $inactiveVehicle->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'reason' => 'Maintenance']);
testPrint("Inactive vehicle rejected", $res['code'] === 422, $res['body']['message'] ?? '');

echo "\n--- DATE VALIDATION ---\n";
$today = date('Y-m-d');
$past = date('Y-m-d', strtotime('-1 day'));
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'end_date' => $tomorrow, 'reason' => 'Maintenance']);
testPrint("Missing start date rejected", $res['code'] === 422);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => $today, 'reason' => 'Maintenance']);
testPrint("Missing end date rejected", $res['code'] === 422);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => $past, 'end_date' => $tomorrow, 'reason' => 'Maintenance']);
testPrint("Past start date rejected", $res['code'] === 422);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-05', 'end_date' => '2026-12-01', 'reason' => 'Maintenance']);
testPrint("End date before start date rejected", $res['code'] === 422);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-10', 'end_date' => '2026-12-10', 'reason' => 'Maintenance']);
testPrint("Same start/end date allowed", $res['code'] === 201, json_encode($res['body']));
VehicleBusySchedule::truncate();

echo "\n--- REASON & NOTE ---\n";
$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-02']);
testPrint("Missing reason rejected", $res['code'] === 422);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'reason' => 'Other']);
testPrint("Other without note rejected", $res['code'] === 422);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'reason' => 'Other', 'note' => 'Broken']);
testPrint("Other with note allowed", $res['code'] === 201);

echo "\n--- OVERLAP VALIDATION (Existing: Dec 1 to Dec 2) ---\n";
apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'reason' => 'Maintenance']);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'reason' => 'Maintenance']);
testPrint("Exact same range rejected", $res['code'] === 409);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-02', 'end_date' => '2026-12-05', 'reason' => 'Maintenance']);
testPrint("Starts inside existing rejected", $res['code'] === 409);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-11-28', 'end_date' => '2026-12-01', 'reason' => 'Maintenance']);
testPrint("Ends inside existing rejected", $res['code'] === 409);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-11-28', 'end_date' => '2026-12-05', 'reason' => 'Maintenance']);
testPrint("Contains existing rejected", $res['code'] === 409);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-11-20', 'end_date' => '2026-11-25', 'reason' => 'Maintenance']);
testPrint("Completely before allowed", $res['code'] === 201);

$res = apiRequest('POST', '/api/vehicle/mark-busy', $vToken, ['vehicle_id' => $myVehicle->id, 'start_date' => '2026-12-05', 'end_date' => '2026-12-10', 'reason' => 'Maintenance']);
testPrint("Completely after allowed", $res['code'] === 201);

// Cleanup
$myVehicle->delete();
$inactiveVehicle->delete();
$otherVehicle->delete();
$vAdmin->delete();
$otherAdmin->delete();
$masterAdmin->delete();
VehicleBusySchedule::truncate();

echo "\n--- DONE ---\n";
