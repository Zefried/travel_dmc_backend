<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomBusySchedule;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class RoomBusyScheduleController extends Controller
{
    // ─────────────────────────────────────────────
    // DRILL-DOWN HELPERS (wizard step data)
    // ─────────────────────────────────────────────

    /**
     * Step 1: List all properties owned by the authenticated hotel admin.
     * GET /hotel/mark-busy/my-properties
     */
    public function myProperties(Request $request)
    {
        try {
            if ($request->user()->role !== 'hotel_admin') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Only Hotel Admins can access this.',
                ], 403);
            }

            $properties = Property::where('hotel_admin_id', $request->user()->id)
                ->select('id', 'name', 'type', 'city_id', 'status')
                ->with('city:id,name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $properties,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch hotel admin properties: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    /**
     * Step 2: List room types under a property. Verifies property ownership.
     * GET /hotel/mark-busy/properties/{id}/room-types
     */
    public function roomTypesForProperty(Request $request, $propertyId)
    {
        try {
            if ($request->user()->role !== 'hotel_admin') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Only Hotel Admins can access this.',
                ], 403);
            }

            $adminId = $request->user()->id;

            $property = Property::where('id', $propertyId)
                ->where('hotel_admin_id', $adminId)
                ->first();

            if (!$property) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Property not found or you do not own it.',
                ], 404);
            }

            $roomTypes = RoomType::where('property_id', $property->id)
                ->select('id', 'name', 'type', 'base_price', 'status')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $roomTypes,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch room types for property: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    /**
     * Step 3: List rooms under a room type. Verifies ownership chain.
     * GET /hotel/mark-busy/room-types/{id}/rooms
     */
    public function roomsForRoomType(Request $request, $roomTypeId)
    {
        try {
            if ($request->user()->role !== 'hotel_admin') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Only Hotel Admins can access this.',
                ], 403);
            }

            $adminId = $request->user()->id;

            // Verify ownership chain: roomType → property → hotel_admin_id
            $roomType = RoomType::with('property:id,hotel_admin_id')
                ->find($roomTypeId);

            if (!$roomType || $roomType->property->hotel_admin_id !== $adminId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Room type not found or you do not own it.',
                ], 404);
            }

            $rooms = Room::where('room_type_id', $roomType->id)
                ->select('id', 'room_no', 'status')
                ->orderBy('room_no')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $rooms,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch rooms for room type: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────

    /**
     * List all room busy schedules for the authenticated hotel admin.
     * GET /hotel/room-busy-schedules
     */
    public function index(Request $request)
    {
        try {
            if ($request->user()->role !== 'hotel_admin') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Only Hotel Admins can view room busy schedules.',
                ], 403);
            }

            $adminId = $request->user()->id;

            $schedules = RoomBusySchedule::with([
                    'room:id,room_no,room_type_id',
                    'room.roomType:id,name,property_id',
                    'room.roomType.property:id,name',
                ])
                ->where('hotel_admin_id', $adminId)
                ->orderBy('start_date', 'asc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => true,
                'data'   => $schedules,
            ]);
        } catch (Throwable $e) {
            Log::error('List room busy schedules failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    /**
     * Mark a room as busy for a date range.
     * POST /hotel/room-busy-schedules
     */
    public function store(Request $request)
    {
        try {
            if ($request->user()->role !== 'hotel_admin') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Only Hotel Admins can mark rooms as busy.',
                ], 403);
            }

            $validated = $request->validate([
                'room_id'    => 'required|integer|exists:rooms,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'reason'     => 'required|string|max:255',
                'note'       => 'nullable|string|max:1000',
            ]);

            $adminId   = $request->user()->id;
            $roomId    = $validated['room_id'];
            $startDate = $validated['start_date'];
            $endDate   = $validated['end_date'];
            $reason    = $validated['reason'];
            $note      = $validated['note'] ?? null;

            // 1. Verify ownership chain: room → roomType → property → hotel_admin_id
            $room = Room::with('roomType.property:id,hotel_admin_id')->find($roomId);

            if (!$room || $room->roomType->property->hotel_admin_id !== $adminId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You do not own this room.',
                ], 403);
            }

            // 2. "Other" reason requires a note
            if ($reason === 'Other' && empty(trim($note ?? ''))) {
                return response()->json([
                    'status'  => false,
                    'message' => 'A note is required when reason is Other.',
                ], 422);
            }

            // 3. Inclusive overlap check:
            //    Overlap exists if existing.start_date <= new.end_date
            //                  AND existing.end_date >= new.start_date
            //    This means Jan 5–10 and Jan 10–15 share Jan 10 → conflict.
            $overlap = RoomBusySchedule::where('room_id', $roomId)
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->exists();

            if ($overlap) {
                return response()->json([
                    'status'  => false,
                    'message' => 'The selected dates overlap with an existing busy schedule for this room.',
                ], 409);
            }

            // 4. Create
            $schedule = RoomBusySchedule::create([
                'room_id'       => $roomId,
                'hotel_admin_id' => $adminId,
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'reason'        => $reason,
                'note'          => $note,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Room successfully marked as busy.',
                'data'    => $schedule,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Mark room busy failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    /**
     * Edit an existing room busy schedule (dates, reason, note only — room is locked).
     * PATCH /hotel/room-busy-schedules/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'hotel_admin') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Only Hotel Admins can edit room busy schedules.',
                ], 403);
            }

            $adminId = $request->user()->id;

            // Find schedule and verify ownership (no chain join needed — hotel_admin_id is denormalized)
            $schedule = RoomBusySchedule::where('hotel_admin_id', $adminId)->find($id);

            if (!$schedule) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Busy schedule not found.',
                ], 404);
            }

            $validated = $request->validate([
                // room_id intentionally excluded — room cannot be changed on edit
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'reason'     => 'required|string|max:255',
                'note'       => 'nullable|string|max:1000',
            ]);

            $startDate = $validated['start_date'];
            $endDate   = $validated['end_date'];
            $reason    = $validated['reason'];
            $note      = $validated['note'] ?? null;

            // "Other" reason requires note
            if ($reason === 'Other' && empty(trim($note ?? ''))) {
                return response()->json([
                    'status'  => false,
                    'message' => 'A note is required when reason is Other.',
                ], 422);
            }

            // Inclusive overlap check, excluding current record
            $overlap = RoomBusySchedule::where('room_id', $schedule->room_id)
                ->where('id', '!=', $schedule->id)
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->exists();

            if ($overlap) {
                return response()->json([
                    'status'  => false,
                    'message' => 'The selected dates overlap with another busy schedule for this room.',
                ], 409);
            }

            $schedule->update([
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'reason'     => $reason,
                'note'       => $note,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Busy schedule updated successfully.',
                'data'    => $schedule->load([
                    'room:id,room_no,room_type_id',
                    'room.roomType:id,name,property_id',
                    'room.roomType.property:id,name',
                ]),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Update room busy schedule failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    /**
     * Delete a room busy schedule.
     * DELETE /hotel/room-busy-schedules/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'hotel_admin') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Only Hotel Admins can delete room busy schedules.',
                ], 403);
            }

            $schedule = RoomBusySchedule::where('hotel_admin_id', $request->user()->id)->find($id);

            if (!$schedule) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Busy schedule not found.',
                ], 404);
            }

            $schedule->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Busy schedule deleted successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('Delete room busy schedule failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }
}
