<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Amenity\AmenityController;
use App\Http\Controllers\Auth\AuthController;
// use App\Http\Controllers\BookingController\BookingController;
use App\Http\Controllers\Location\CityController;
use App\Http\Controllers\Location\CountryController;
use App\Http\Controllers\Location\StateController;
use App\Http\Controllers\PropAmenityConfig\PropertyAmenityConfigController;
use App\Http\Controllers\Property\PropertyController;
use App\Http\Controllers\RoomConfiguration\RoomConfigurationController;
use App\Http\Controllers\Rooms\RoomController;
use App\Http\Controllers\RoomType\RoomTypeController;
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
        Route::get('/hotel-admins/list', [AdminUserController::class, 'hotelAdminList']);
        
        Route::post('/countries', [CountryController::class, 'store']);
        Route::patch('/countries/{id}', [CountryController::class, 'update']);
        Route::get('/countries', [CountryController::class, 'index']);

        Route::post('/states', [StateController::class, 'store']);
        Route::patch('/states/{id}', [StateController::class, 'update']);
        Route::get('/states', [StateController::class, 'index']);
        Route::get('/states/options', [StateController::class, 'options']);

        Route::post('/cities', [CityController::class, 'store']);
        Route::patch('/cities/{id}', [CityController::class, 'update']);
        Route::get('/cities', [CityController::class, 'index']);
        Route::get('/cities/options', [CityController::class, 'options']);


        // Route::post('/bookings', [BookingController::class, 'store']);
        // Route::patch('/bookings/{id}', [BookingController::class, 'update']);

        Route::post('/rooms', [RoomController::class, 'store']);
        Route::patch('/rooms/{id}', [RoomController::class, 'update']);
        Route::get('/room-types/for-rooms', [RoomController::class, 'roomTypesForRooms']);
    });


Route::prefix('hotel')
    ->middleware(['auth:sanctum', HotelAdminCheck::class])
    ->group(function () {

        Route::post('/properties', [PropertyController::class, 'store']);
        Route::patch('/properties/{id}', [PropertyController::class, 'update']);
        Route::get('/properties/options', [PropertyController::class, 'options']);
        Route::get('/properties/room-types', [PropertyController::class, 'propertiesForRoomType']);       
      
        Route::post('/room-types', [RoomTypeController::class, 'store']);
        Route::patch('/room-types/{id}', [RoomTypeController::class, 'update']);

        Route::post('/amenities', [AmenityController::class, 'store']);
        Route::patch('/amenities/{id}', [AmenityController::class, 'update']);
        Route::get('/amenities', [AmenityController::class, 'index']);

        Route::get('/properties/for-amenities', [PropertyController::class, 'propertiesForAmenities']);
        Route::get('/room-types/for-amenities', [PropertyController::class, 'roomTypesForAmenities']);

        Route::post('/amenity-configs', [PropertyAmenityConfigController::class, 'store']);
        Route::patch('/amenity-configs/{id}', [PropertyAmenityConfigController::class, 'update']);
        Route::get('/properties/{id}/amenities', [PropertyAmenityConfigController::class, 'existingPropertyAmenities']);
        // using the same controller to get existing amenities for a room type
        Route::get('/room-types/{id}/amenities',[PropertyAmenityConfigController::class, 'existingRoomTypeAmenities']);
        Route::delete('/amenity-configs/{id}', [PropertyAmenityConfigController::class, 'destroy']);

        Route::post('/room-configurations', [RoomConfigurationController::class, 'store']);
        Route::patch('/room-configurations/{id}', [RoomConfigurationController::class, 'update']);
        Route::get('/properties/for-room-configuration', [PropertyController::class, 'propertiesForRoomConfiguration']);
        Route::get('/properties/{id}/room-types/for-configuration',[RoomTypeController::class, 'roomTypesForConfiguration']);
        
    });