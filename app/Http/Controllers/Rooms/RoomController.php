<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class RoomController extends Controller
{
    protected function roomRules(): array
    {
        return [
            'room_type_id' => 'required|integer|exists:room_types,id',
            'room_no' => 'required|string|max:100',
            'status' => 'nullable|in:active,inactive,maintenance',
        ];
    }

    protected function roomUpdateRules(): array
    {
        return [
            'room_type_id' => 'sometimes|integer|exists:room_types,id',
            'room_no' => 'sometimes|string|max:100',
            'status' => 'sometimes|nullable|in:active,inactive,maintenance',
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->roomRules());

            $existingRoom = Room::where('room_type_id', $validated['room_type_id'])
                ->where('room_no', $validated['room_no'])
                ->first();

            if ($existingRoom) {
                return response()->json([
                    'status' => false,
                    'message' => 'This room number already exists for this room type.',
                ], 409);
            }

            $room = Room::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Room created successfully.',
                'data' => $room,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Failed to create room', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create room.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $room = Room::find($id);

            if (!$room) {
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found.',
                ], 404);
            }

            $validated = $request->validate($this->roomUpdateRules());

            $roomTypeId = $validated['room_type_id'] ?? $room->room_type_id;
            $roomNo = $validated['room_no'] ?? $room->room_no;

            $existingRoom = Room::where('room_type_id', $roomTypeId)
                ->where('room_no', $roomNo)
                ->where('id', '!=', $room->id)
                ->first();

            if ($existingRoom) {
                return response()->json([
                    'status' => false,
                    'message' => 'This room number already exists for this room type.',
                ], 409);
            }

            $room->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Room updated successfully.',
                'data' => $room->fresh(),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Failed to update room', [
                'room_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update room.',
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $query = Room::query();

            if ($request->filled('room_type_id')) {
                $query->where(
                    'room_type_id',
                    $request->integer('room_type_id')
                );
            }

            $rooms = $query
                ->latest()
                ->paginate(2);

            return response()->json([
                'status' => true,
                'data' => $rooms,
            ], 200);
        } catch (Throwable $e) {

            Log::error('Failed to fetch room list', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch room list.',
            ], 500);
        }
    }

    public function roomTypesForRooms(Request $request)
    {
        try {
            $query = trim($request->query('query', ''));

            if (strlen($query) < 3) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please enter at least 3 characters to search.',
                ], 422);
            }

            $roomTypes = RoomType::with([
                'property:id,name,country_id,city_id',
                'property.country:id,name',
                'property.city:id,name',
            ])
                ->where(function ($queryBuilder) use ($query) {

                    $queryBuilder
                        ->where('name', 'like', $query . '%')
                        ->orWhereHas('property', function ($propertyQuery) use ($query) {

                            $propertyQuery->where(
                                'name',
                                'like',
                                $query . '%'
                            );
                        });
                })
                ->latest()
                ->limit(10)
                ->get();

            return response()->json([
                'status' => true,
                'data' => $roomTypes,
            ], 200);
        } catch (Throwable $e) {

            Log::error('Failed to search room types for rooms', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to search room types.',
            ], 500);
        }
    }
}
