<?php

namespace App\Http\Controllers\RoomType;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class RoomTypeController extends Controller
{
    protected function roomTypeRules(): array
    {
        return [
            'property_id' => 'required|integer|exists:properties,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',

            'bedroom' => 'required|integer|min:1',

            'size' => 'nullable|numeric|min:0',
            'size_unit' => 'nullable|string|max:20',

            'max_adults' => 'required|integer|min:1',
            'max_children' => 'nullable|integer|min:0',
            'max_occupancy' => 'required|integer|min:1',

            'view' => 'nullable|string|max:255',

            'default_bed_type' => 'nullable|string|max:255',
            'default_bed_quantity' => 'nullable|integer|min:1',

            'description' => 'nullable|string',

            'status' => 'sometimes|nullable|in:active,inactive',
            'base_price' => 'sometimes|nullable|numeric|min:0',
        ];
    }

    protected function roomTypeUpdateRules(): array
    {
        return [
            'property_id' => 'sometimes|integer|exists:properties,id',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:255',

            'bedroom' => 'sometimes|integer|min:1',

            'size' => 'sometimes|nullable|numeric|min:0',
            'size_unit' => 'sometimes|nullable|string|max:20',

            'max_adults' => 'sometimes|integer|min:1',
            'max_children' => 'sometimes|nullable|integer|min:0',
            'max_occupancy' => 'sometimes|integer|min:1',

            'view' => 'sometimes|nullable|string|max:255',

            'default_bed_type' => 'sometimes|nullable|string|max:255',
            'default_bed_quantity' => 'sometimes|nullable|integer|min:1',

            'description' => 'sometimes|nullable|string',

            'base_price' => 'sometimes|nullable|numeric|min:0',
            'status' => 'sometimes|nullable|in:active,inactive',
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->roomTypeRules());

            $existingRoomType = RoomType::where('property_id', $validated['property_id'])
                ->where('name', $validated['name'])
                ->first();

            if ($existingRoomType) {
                return response()->json([
                    'status' => false,
                    'message' => 'A room type with this name already exists for this property.',
                ], 409);
            }

            $roomType = RoomType::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Room type created successfully.',
                'data' => $roomType,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Failed to create room type', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create room type.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $roomType = RoomType::find($id);

            if (!$roomType) {
                return response()->json([
                    'status' => false,
                    'message' => 'Room type not found.',
                ], 404);
            }

            $validated = $request->validate($this->roomTypeUpdateRules());

            $propertyId = $validated['property_id'] ?? $roomType->property_id;
            $name = $validated['name'] ?? $roomType->name;

            $existingRoomType = RoomType::where('property_id', $propertyId)
                ->where('name', $name)
                ->where('id', '!=', $roomType->id)
                ->first();

            if ($existingRoomType) {
                return response()->json([
                    'status' => false,
                    'message' => 'A room type with this name already exists for this property.',
                ], 409);
            }

            $roomType->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Room type updated successfully.',
                'data' => $roomType->fresh(),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Failed to update room type', [
                'room_type_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update room type.',
            ], 500);
        }
    }
}
