<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Amenity\AmenityController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PropAmenityConfig\PropertyAmenityConfigController;
use App\Http\Controllers\Property\PropertyController;
use App\Http\Controllers\RoomConfiguration\RoomConfigurationController;
use App\Http\Controllers\RoomType\RoomController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\HotelAdminCheck;
use Illuminate\Support\Facades\Route;











Route::post('/register',[AuthController::class, 'register']);
Route::post('/login',[AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


Route::prefix('admin')
    ->middleware(['auth:sanctum', CheckAdmin::class])
    ->group(function () {
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
    });


Route::prefix('hotel')
    ->middleware(['auth:sanctum', HotelAdminCheck::class])
    ->group(function () {

        Route::post('/properties', [PropertyController::class, 'store']);
        Route::patch('/properties/{id}', [PropertyController::class, 'update']);
        Route::get('/properties', [PropertyController::class, 'index']);
        Route::get('/properties/{id}', [PropertyController::class, 'show']);
      
        Route::post('/room-types', [RoomController::class, 'store']);
        Route::patch('/room-types/{id}', [RoomController::class, 'update']);

        Route::post('/room-configurations', [RoomConfigurationController::class, 'store']);
        Route::patch('/room-configurations/{id}', [RoomConfigurationController::class, 'update']);

        Route::post('/amenities', [AmenityController::class, 'store']);
        Route::patch('/amenities/{id}', [AmenityController::class, 'update']);

        Route::post('/amenity-configs', [PropertyAmenityConfigController::class, 'store']);
        Route::patch('/amenity-configs/{id}', [PropertyAmenityConfigController::class, 'update']);
    });