<?php

namespace App\Http\Controllers\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\VehicleCalendar;
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


            $vehicles = $query
                ->latest()
                ->paginate(5);


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
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'vehicle_admin_id' => 'nullable|integer|exists:users,id',
                'status' => 'nullable|in:active,inactive',
                'availability' => 'nullable|in:available,unavailable',
                'search' => 'nullable|string|max:100',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $startDate = $validated['start_date'] ?? now()->toDateString();
            $endDate = $validated['end_date'] ?? $startDate;

            $query = Vehicle::with([
                'vehicleAdmin:id,name,email,phone,is_active',
                'calendars' => function ($calendarQuery) use ($startDate, $endDate) {
                    $calendarQuery
                        ->where('is_active', true)
                        ->whereIn('status', ['busy', 'maintenance'])
                        ->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate)
                        ->orderBy('start_date');
                },
            ]);

            if ($request->user()->role === 'vehicle_admin') {
                $query->where('vehicle_admin_id', $request->user()->id);
            }

            if (!empty($validated['vehicle_admin_id'])) {
                $query->where('vehicle_admin_id', $validated['vehicle_admin_id']);
            }

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('registration_no', 'like', "%{$search}%")
                        ->orWhere('driver_name', 'like', "%{$search}%");
                });
            }

            if (!empty($validated['availability'])) {
                $calendarScope = function ($calendarQuery) use ($startDate, $endDate) {
                    $calendarQuery
                        ->where('is_active', true)
                        ->whereIn('status', ['busy', 'maintenance'])
                        ->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                };

                if ($validated['availability'] === 'available') {
                    $query->where('status', '!=', 'inactive')
                        ->whereDoesntHave('calendars', $calendarScope);
                } else {
                    $query->where(function ($availabilityQuery) use ($calendarScope) {
                        $availabilityQuery
                            ->where('status', 'inactive')
                            ->orWhereHas('calendars', $calendarScope);
                    });
                }
            }

            $vehicles = $query
                ->latest()
                ->paginate($validated['per_page'] ?? 10)
                ->through(function (Vehicle $vehicle) {
                    $isUnavailable = $vehicle->status === 'inactive'
                        || $vehicle->calendars->isNotEmpty();

                    return array_merge($vehicle->toArray(), [
                        'availability' => $isUnavailable ? 'unavailable' : 'available',
                        'availability_reason' => $vehicle->status === 'inactive'
                            ? 'Vehicle is inactive'
                            : ($vehicle->calendars->isNotEmpty() ? 'Busy or under maintenance' : null),
                    ]);
                });

            return response()->json([
                'status' => true,
                'data' => $vehicles,
                'range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Failed to fetch vehicle availability', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch vehicle availability.',
            ], 500);
        }
    }
}