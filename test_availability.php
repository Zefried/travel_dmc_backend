<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/admin/vehicles/availability', 'GET', [
    'start_date' => '2026-10-20',
    'end_date' => '2026-10-25'
]);

$controller = new App\Http\Controllers\Vehicle\VehicleController();
$response = $controller->availability($request);

echo $response->getContent();
