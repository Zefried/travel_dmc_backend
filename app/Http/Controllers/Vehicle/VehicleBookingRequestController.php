<?php

namespace App\Http\Controllers\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleBookingRequest;
use App\Models\VehicleBusySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Notifications\BookingRequestCreatedNotification;
use App\Notifications\BookingRequestStatusUpdatedNotification;
use Throwable;

class VehicleBookingRequestController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Main Admin only
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only the main admin can request vehicle bookings.',
                ], 403);
            }

            $validated = $request->validate([
                'vehicle_id' => 'required|integer|exists:vehicles,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'passenger_count' => 'required|integer|min:1',
                'requested_price' => 'nullable|numeric|min:0',
                'note' => 'nullable|string|max:1000',
            ]);

            $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

            // Validate seating capacity
            if ($validated['passenger_count'] > $vehicle->seating_capacity) {
                return response()->json([
                    'status' => false,
                    'message' => 'Passenger count exceeds vehicle seating capacity of ' . $vehicle->seating_capacity . '.',
                ], 422);
            }

            // Ensure vehicle belongs to a vehicle admin
            if (!$vehicle->vehicle_admin_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'This vehicle does not have an assigned owner.',
                ], 422);
            }

            // Live Availability Re-check
            $startDate = $validated['start_date'];
            $endDate = $validated['end_date'];

            $isBusy = VehicleBusySchedule::where('vehicle_id', $vehicle->id)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })->exists();

            if ($isBusy) {
                return response()->json([
                    'status' => false,
                    'message' => 'The selected vehicle is no longer available for these dates.',
                ], 409); // Conflict
            }

            // Create the pending booking request
            $bookingRequest = VehicleBookingRequest::create([
                'vehicle_id' => $vehicle->id,
                'main_admin_id' => $request->user()->id,
                'vehicle_admin_id' => $vehicle->vehicle_admin_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'passenger_count' => $validated['passenger_count'],
                'requested_price' => $validated['requested_price'] ?? null,
                'note' => $validated['note'] ?? null,
                'status' => 'pending',
            ]);

            // Notify Vehicle Admin
            $vehicleAdmin = $vehicle->vehicleAdmin;
            if ($vehicleAdmin) {
                $vehicleAdmin->notify(new BookingRequestCreatedNotification([
                    'vehicle_name' => $vehicle->name,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'passenger_count' => $validated['passenger_count'],
                ]));
            }

            return response()->json([
                'status' => true,
                'message' => 'Vehicle booking request submitted successfully.',
                'data' => $bookingRequest,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Vehicle Booking Request failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    public function indexForMainAdmin(Request $request)
    {
        try {
            $user = $request->user();
            
            $requests = VehicleBookingRequest::with(['vehicle:id,name,registration_no,type', 'vehicleAdmin:id,name,email,phone'])
                // Strictly scope to requests made by the authenticated main admin
                ->where('main_admin_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $requests
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch booking requests for main admin: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching requests.',
            ], 500);
        }
    }

    public function indexForVehicleAdmin(Request $request)
    {
        try {
            $user = $request->user();
            
            $requests = VehicleBookingRequest::with(['vehicle:id,name,registration_no,type', 'mainAdmin:id,name,email'])
                // Strictly scope to vehicles owned by the authenticated vehicle admin
                ->whereHas('vehicle', function($q) use ($user) {
                    $q->where('vehicle_admin_id', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $requests
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch booking requests for vehicle admin: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching requests.',
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'status' => 'required|in:accepted,rejected',
                'rejection_note' => 'required_if:status,rejected|nullable|string|max:1000'
            ]);

            // Find request and verify ownership via the associated vehicle strictly
            $bookingRequest = VehicleBookingRequest::where('id', $id)
                ->whereHas('vehicle', function($q) use ($user) {
                    $q->where('vehicle_admin_id', $user->id);
                })
                ->first();

            if (!$bookingRequest) {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking request not found or unauthorized.'
                ], 404);
            }

            if ($bookingRequest->status !== 'pending') {
                return response()->json([
                    'status' => false,
                    'message' => 'This request has already been processed.'
                ], 422);
            }

            if ($validated['status'] === 'accepted') {
                // Double-Check Rule
                $startDate = $bookingRequest->start_date;
                $endDate = $bookingRequest->end_date;

                $isBusy = VehicleBusySchedule::where('vehicle_id', $bookingRequest->vehicle_id)
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q2) use ($startDate, $endDate) {
                                $q2->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
                    })->exists();

                if ($isBusy) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Cannot accept. The vehicle has overlapping busy schedules for these dates.',
                    ], 409);
                }

                $bookingRequest->update(['status' => 'accepted']);

                // Create confirmed busy schedule
                VehicleBusySchedule::create([
                    'vehicle_id' => $bookingRequest->vehicle_id,
                    'vehicle_admin_id' => $user->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'reason' => 'Booking'
                ]);

                // Notify Main Admin
                $mainAdmin = $bookingRequest->mainAdmin;
                if ($mainAdmin) {
                    $mainAdmin->notify(new BookingRequestStatusUpdatedNotification([
                        'status' => 'accepted',
                        'vehicle_name' => $bookingRequest->vehicle->name,
                    ]));
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Booking request accepted successfully.'
                ], 200);
            } else {
                // Rejected
                $bookingRequest->update([
                    'status' => 'rejected',
                    'rejection_note' => $validated['rejection_note']
                ]);

                // Notify Main Admin
                $mainAdmin = $bookingRequest->mainAdmin;
                if ($mainAdmin) {
                    $mainAdmin->notify(new BookingRequestStatusUpdatedNotification([
                        'status' => 'rejected',
                        'vehicle_name' => $bookingRequest->vehicle->name,
                        'rejection_note' => $validated['rejection_note']
                    ]));
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Booking request rejected successfully.'
                ], 200);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update booking request status: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }
}
