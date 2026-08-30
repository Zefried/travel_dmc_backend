<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Amenity\AmenityController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookingController\BookingController;
use App\Http\Controllers\Location\CityController;
use App\Http\Controllers\Location\CountryController;
use App\Http\Controllers\Location\StateController;
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
        Route::get('/hotel-admins/search', [AdminUserController::class, 'searchHotelAdmins']);
        
        Route::post('/countries', [CountryController::class, 'store']);
        Route::patch('/countries/{id}', [CountryController::class, 'update']);
        Route::get('/countries', [CountryController::class, 'index']);

        Route::post('/states', [StateController::class, 'store']);
        Route::patch('/states/{id}', [StateController::class, 'update']);
        Route::get('/states', [StateController::class, 'index']);

        Route::post('/cities', [CityController::class, 'store']);
        Route::patch('/cities/{id}', [CityController::class, 'update']);
        Route::get('/cities', [CityController::class, 'index']);


        Route::post('/bookings', [BookingController::class, 'store']);
        Route::patch('/bookings/{id}', [BookingController::class, 'update']);

        Route::post('/rooms', [RoomController::class, 'store']);
        Route::patch('/rooms/{id}', [RoomController::class, 'update']);
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