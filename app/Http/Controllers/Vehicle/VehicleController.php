<?php

namespace App\Http\Controllers\Vehicle;

use App\Http\Controllers\Controller;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class VehicleController extends Controller
{
    protected function vehicleRules(): array
    {
        return [
            'vehicle_admin_id' =>
                'required|integer|exists:users,id',

            'type' =>
                'nullable|string|max:255',

            'name' =>
                'nullable|string|max:255',

            'model' =>
                'nullable|string|max:255',

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
            'type' =>
                'sometimes|nullable|string|max:255',

            'name' =>
                'sometimes|nullable|string|max:255',

            'model' =>
                'sometimes|nullable|string|max:255',

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

            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only the main admin can register vehicles.',
                ], 403);
            }

            $validated = $request->validate(
                array_merge($this->vehicleRules(), [
                    'vehicle_admin_id' => 'required|integer|exists:users,id,role,vehicle_admin',
                ])
            );

            $validated['status'] = $validated['status'] ?? 'active';

            if (!empty($validated['registration_no'])) {

                $existingVehicle = Vehicle::where(
                    'vehicle_admin_id',
                    $validated['vehicle_admin_id']
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
                            'This registration number already exists for this vehicle admin.',
                    ], 409);
                }
            }


            $vehicle = Vehicle::create(
                $validated
            );


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


            if ($request->user()->role === 'vehicle_admin'
                && $vehicle->vehicle_admin_id !== $request->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not authorized to manage this vehicle.',
                ], 403);
            }

            $validated = $request->validate(
                $this->vehicleUpdateRules()
            );

            $vehicleAdminId = $vehicle->vehicle_admin_id;


            $registrationNo =
                $validated['registration_no']
                ?? $vehicle->registration_no;


            if ($registrationNo) {

                $existingVehicle = Vehicle::where(
                    'vehicle_admin_id',
                    $vehicleAdminId
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
                            'This registration number already exists for this vehicle admin.',
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


    public function list(Request $request)
    {
        try {

            $query = Vehicle::query();

            if ($request->user()->role === 'vehicle_admin') {
                $query->where('vehicle_admin_id', $request->user()->id);
            }


            if ($request->filled('vehicle_admin_id')) {

                $query->where(
                    'vehicle_admin_id',
                    $request->integer('vehicle_admin_id')
                );
            }


            $perPage = max(1, min($request->integer('per_page', 5), 100));
            $vehicles = $query
                ->latest()
                ->paginate($perPage);


            return response()->json([
                'status' => true,
                'data' => $vehicles,
            ], 200);


        } catch (Throwable $e) {

            Log::error('Failed to fetch vehicle list', [
                'error' => $e->getMessage(),
            ]);


            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch vehicle list.',
            ], 500);
        }
    }

    public function availability(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'vehicle_admin_id' => 'nullable|integer|exists:users,id',
            ]);

            $startDate = $validated['start_date'];
            $endDate = $validated['end_date'];
            $vehicleAdminId = $validated['vehicle_admin_id'] ?? null;

            $query = Vehicle::query()->with(['vehicleAdmin:id,name', 'busySchedules' => function ($q) use ($startDate, $endDate) {
                $q->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                });
            }]);

            if ($vehicleAdminId) {
                $query->where('vehicle_admin_id', $vehicleAdminId);
            }

            $vehicles = $query->get()->map(function ($vehicle) {
                $isBusy = $vehicle->busySchedules->isNotEmpty();
                $vehicle->is_available = !$isBusy;
                
                if ($isBusy) {
                    // Get the overlapping schedules to show to frontend
                    $vehicle->overlapping_schedules = $vehicle->busySchedules->map(function ($schedule) {
                        return [
                            'start_date' => $schedule->start_date,
                            'end_date' => $schedule->end_date,
                            'reason' => $schedule->reason,
                        ];
                    });
                }
                
                // Remove the busySchedules relationship from output to keep it clean
                unset($vehicle->busySchedules);
                
                return $vehicle;
            });

            return response()->json([
                'status' => true,
                'data' => $vehicles
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            Log::error('Fetch vehicle availability failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }
}
