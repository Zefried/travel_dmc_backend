<?php
$user = \App\Models\User::find(9);
$request = new \Illuminate\Http\Request();
$request->setUserResolver(function () use ($user) { return $user; });
$controller = app()->make(\App\Http\Controllers\Vehicle\VehicleController::class);
$response = $controller->list($request);
echo json_encode($response->getData(true), JSON_PRETTY_PRINT);
