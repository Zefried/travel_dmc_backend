<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleBusySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class VehicleBusyScheduleController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->user()->role !== 'vehicle_admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only Vehicle Admins can view busy schedules.'
                ], 403);
            }

            $adminId = $request->user()->id;

            $schedules = VehicleBusySchedule::with('vehicle:id,name,registration_no')
                ->where('vehicle_admin_id', $adminId)
                ->orderBy('start_date', 'asc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => true,
                'data' => $schedules
            ]);
        } catch (Throwable $e) {
            Log::error('List busy schedules failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'vehicle_id' => 'required|integer|exists:vehicles,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'reason'     => 'required|string|max:255',
                'note'       => 'nullable|string|max:1000',
            ]);

            $vehicleId = $validated['vehicle_id'];
            $startDate = $validated['start_date'];
            $endDate = $validated['end_date'];
            $reason = $validated['reason'];
            $note = $validated['note'] ?? null;
            
            if ($request->user()->role !== 'vehicle_admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only Vehicle Admins can mark vehicles as busy.'
                ], 403);
            }
            
            $adminId = $request->user()->id;

            // 1. Verify ownership and active status
            $vehicle = Vehicle::find($vehicleId);
            
            if ($vehicle->vehicle_admin_id != $adminId) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not own this vehicle.'
                ], 403);
            }

            if (strtolower($vehicle->status) !== 'active') {
                return response()->json([
                    'status' => false,
                    'message' => 'This vehicle is inactive or deleted.'
                ], 422);
            }

            // 2. Validate "Other" reason requires a note
            if ($reason === 'Other' && empty(trim($note))) {
                return response()->json([
                    'status' => false,
                    'message' => 'A note is required when reason is Other.'
                ], 422);
            }

            // 3. Prevent overlap with existing schedules
            $overlap = VehicleBusySchedule::where('vehicle_id', $vehicleId)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })->exists();

            if ($overlap) {
                return response()->json([
                    'status' => false,
                    'message' => 'The selected dates overlap with an existing busy schedule.'
                ], 409);
            }

            // 4. Create the record
            $schedule = VehicleBusySchedule::create([
                'vehicle_id' => $vehicleId,
                'vehicle_admin_id' => $adminId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $reason,
                'note' => $note,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Vehicle successfully marked as busy.',
                'data' => $schedule
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            Log::error('Mark busy failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'vehicle_admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only Vehicle Admins can edit busy schedules.'
                ], 403);
            }

            $validated = $request->validate([
                'vehicle_id' => 'required|integer|exists:vehicles,id',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'reason'     => 'required|string|max:255',
                'note'       => 'nullable|string|max:1000',
            ]);

            $schedule = VehicleBusySchedule::where('vehicle_admin_id', $request->user()->id)
                ->find($id);

            if (!$schedule) {
                return response()->json([
                    'status' => false,
                    'message' => 'Busy schedule not found.'
                ], 404);
            }

            $vehicle = Vehicle::find($validated['vehicle_id']);
            if ($vehicle->vehicle_admin_id != $request->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not own this vehicle.'
                ], 403);
            }

            if (strtolower($vehicle->status) !== 'active') {
                return response()->json([
                    'status' => false,
                    'message' => 'This vehicle is inactive or deleted.'
                ], 422);
            }

            if ($validated['reason'] === 'Other' && empty(trim($validated['note'] ?? ''))) {
                return response()->json([
                    'status' => false,
                    'message' => 'A note is required when reason is Other.'
                ], 422);
            }

            $overlap = VehicleBusySchedule::where('vehicle_id', $validated['vehicle_id'])
                ->where('id', '!=', $schedule->id)
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('start_date', '<=', $validated['start_date'])
                                ->where('end_date', '>=', $validated['end_date']);
                        });
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'status' => false,
                    'message' => 'The selected dates overlap with another busy schedule.'
                ], 409);
            }

            $schedule->update([
                'vehicle_id' => $validated['vehicle_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'],
                'note' => $validated['note'] ?? null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Busy schedule updated successfully.',
                'data' => $schedule->load('vehicle:id,name,registration_no')
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            Log::error('Update busy schedule failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'vehicle_admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only Vehicle Admins can delete busy schedules.'
                ], 403);
            }

            $schedule = VehicleBusySchedule::where('vehicle_admin_id', $request->user()->id)
                ->find($id);

            if (!$schedule) {
                return response()->json([
                    'status' => false,
                    'message' => 'Busy schedule not found.'
                ], 404);
            }

            $schedule->delete();

            return response()->json([
                'status' => true,
                'message' => 'Busy schedule deleted successfully.'
            ]);
        } catch (Throwable $e) {
            Log::error('Delete busy schedule failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }
}
