<?php

namespace App\Http\Controllers\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class VehicleController extends Controller
{
    
    protected function vehicleRules(): array
    {
        return [
            'type' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',

            'registration_no' =>
                'nullable|string|max:255',

            'seating_capacity' =>
                'nullable|integer|min:1',

            'color' =>
                'nullable|string|max:100',

            'driver_name' =>
                'nullable|string|max:255',

            'driver_phone' =>
                'nullable|string|max:50',

            'status' =>
                'nullable|in:active,inactive',
        ];
    }


    protected function vehicleUpdateRules(): array
    {
        return [
            'type' => 'sometimes|nullable|string|max:255',
            'name' => 'sometimes|nullable|string|max:255',
            'model' => 'sometimes|nullable|string|max:255',

            'registration_no' =>
                'sometimes|nullable|string|max:255',

            'seating_capacity' =>
                'sometimes|nullable|integer|min:1',

            'color' =>
                'sometimes|nullable|string|max:100',

            'driver_name' =>
                'sometimes|nullable|string|max:255',

            'driver_phone' =>
                'sometimes|nullable|string|max:50',

            'status' =>
                'sometimes|nullable|in:active,inactive',
        ];
    }


    public function store(Request $request)
    {
        try {

            $validated = $request->validate(
                $this->vehicleRules()
            );


            $hotelAdminId = Auth::id();


            if (
                !empty($validated['registration_no'])
            ) {

                $existingVehicle = Vehicle::where(
                    'hotel_admin_id',
                    $hotelAdminId
                )
                    ->where(
                        'registration_no',
                        $validated['registration_no']
                    )
                    ->first();


                if ($existingVehicle) {

                    return response()->json([
                        'status' => false,
                        'message' =>
                            'This registration number already exists for this hotel admin.',
                    ], 409);
                }
            }


            $vehicle = Vehicle::create([
                'hotel_admin_id' => $hotelAdminId,
                ...$validated,
            ]);


            return response()->json([
                'status' => true,
                'message' => 'Vehicle created successfully.',
                'data' => $vehicle,
            ], 201);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (Throwable $e) {

            Log::error('Failed to create vehicle', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create vehicle.',
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {

            $vehicle = Vehicle::find($id);


            if (!$vehicle) {

                return response()->json([
                    'status' => false,
                    'message' => 'Vehicle not found.',
                ], 404);
            }


            $validated = $request->validate(
                $this->vehicleUpdateRules()
            );


            $registrationNo =
                $validated['registration_no']
                ?? $vehicle->registration_no;


            if ($registrationNo) {

                $existingVehicle = Vehicle::where(
                    'hotel_admin_id',
                    $vehicle->hotel_admin_id
                )
                    ->where(
                        'registration_no',
                        $registrationNo
                    )
                    ->where(
                        'id',
                        '!=',
                        $vehicle->id
                    )
                    ->first();


                if ($existingVehicle) {

                    return response()->json([
                        'status' => false,
                        'message' =>
                            'This registration number already exists for this hotel admin.',
                    ], 409);
                }
            }


            $vehicle->update($validated);


            return response()->json([
                'status' => true,
                'message' => 'Vehicle updated successfully.',
                'data' => $vehicle->fresh(),
            ], 200);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (Throwable $e) {

            Log::error('Failed to update vehicle', [
                'vehicle_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update vehicle.',
            ], 500);
        }
    }
}