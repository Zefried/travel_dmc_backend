<?php

namespace App\Http\Controllers\VehicleCalendar;

use App\Http\Controllers\Controller;
use App\Models\VehicleCalendar;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class VehicleCalendarController extends Controller
{
    

    protected function vehicleCalendarRules(): array
    {
        return [
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'title' => 'nullable|string|max:255',
            'status' => 'required|in:busy,maintenance,available',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function vehicleCalendarUpdateRules(): array
    {
        return [
            'title' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|in:busy,maintenance,available',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'notes' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    protected function canManageVehicle(Request $request, Vehicle $vehicle): bool
    {
        return $request->user()->role === 'admin'
            || $vehicle->vehicle_admin_id === $request->user()->id;
    }

    protected function canManageCalendar(Request $request, VehicleCalendar $calendarEntry): bool
    {
        return $this->canManageVehicle($request, $calendarEntry->vehicle);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->vehicleCalendarRules());

            $vehicle = Vehicle::find($validated['vehicle_id']);

            if (! $vehicle || ! $this->canManageVehicle($request, $vehicle)) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not authorized to manage this vehicle.',
                ], 403);
            }

            if (! $vehicle->vehicle_admin_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'This vehicle must be assigned to a vehicle admin first.',
                ], 422);
            }

            $validated['vehicle_admin_id'] = $vehicle->vehicle_admin_id;

            $startDate = $validated['start_date'];
            $endDate = $validated['end_date'];

            $hasOverlap = VehicleCalendar::where('vehicle_id', $validated['vehicle_id'])
                ->where('is_active', true)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->exists();

            if ($hasOverlap) {
                return response()->json([
                    'status' => false,
                    'message' => 'This vehicle already has a calendar block for the selected date range.',
                ], 409);
            }

            $item = VehicleCalendar::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Vehicle calendar entry created successfully.',
                'data' => $item->load(['vehicle', 'vehicleAdmin']),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Failed to create vehicle calendar entry', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create vehicle calendar entry.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $calendarEntry = VehicleCalendar::find($id);

            if (! $calendarEntry) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vehicle calendar entry not found.',
                ], 404);
            }

            $validated = $request->validate($this->vehicleCalendarUpdateRules());

            $calendarEntry->load('vehicle');

            if (! $this->canManageCalendar($request, $calendarEntry)) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not authorized to update this calendar entry.',
                ], 403);
            }

            $startDate = $validated['start_date'] ?? $calendarEntry->start_date;
            $endDate = $validated['end_date'] ?? $calendarEntry->end_date;

            $hasOverlap = VehicleCalendar::where('vehicle_id', $calendarEntry->vehicle_id)
                ->where('is_active', true)
                ->where('id', '!=', $calendarEntry->id)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                })
                ->exists();

            if ($hasOverlap) {
                return response()->json([
                    'status' => false,
                    'message' => 'This vehicle already has a calendar block for the selected date range.',
                ], 409);
            }

            $calendarEntry->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Vehicle calendar entry updated successfully.',
                'data' => $calendarEntry->fresh()->load(['vehicle', 'vehicleAdmin']),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Failed to update vehicle calendar entry', [
                'calendar_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update vehicle calendar entry.',
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $calendarEntry = VehicleCalendar::find($id);

            if (! $calendarEntry) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vehicle calendar entry not found.',
                ], 404);
            }

            $calendarEntry->load('vehicle');

            if (! $this->canManageCalendar($request, $calendarEntry)) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not authorized to remove this calendar entry.',
                ], 403);
            }

            $calendarEntry->delete();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle calendar entry removed successfully.',
            ], 200);
        } catch (Throwable $e) {
            Log::error('Failed to remove vehicle calendar entry', [
                'calendar_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to remove vehicle calendar entry.',
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $query = VehicleCalendar::with(['vehicle', 'vehicleAdmin'])->orderBy('start_date', 'asc');

            if ($request->filled('vehicle_id')) {
                $query->where('vehicle_id', $request->vehicle_id);
            }

            if ($request->filled('vehicle_admin_id')) {
                $query->where('vehicle_admin_id', $request->vehicle_admin_id);
            }

            if ($request->user()->role === 'vehicle_admin') {
                $query->whereHas('vehicle', function ($vehicleQuery) use ($request) {
                    $vehicleQuery->where('vehicle_admin_id', $request->user()->id);
                });
            }

            return response()->json([
                'status' => true,
                'data' => $query->get(),
            ], 200);
        } catch (Throwable $e) {
            Log::error('Failed to fetch vehicle calendar entries', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch vehicle calendar entries.',
            ], 500);
        }
    }
}
