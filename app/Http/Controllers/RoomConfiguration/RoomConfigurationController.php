<?php

namespace App\Http\Controllers\RoomConfiguration;

use App\Http\Controllers\Controller;
use App\Models\RoomConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class RoomConfigurationController extends Controller
{
   protected function roomConfigurationRules(): array
    {
        return [
            'room_type_id' => 'required|integer|exists:room_types,id',
            'type' => 'required|string|max:50',
            'name' => 'required|string|max:150',
            'meal_code' => 'nullable|in:RO,HB,FB,AI',
            'description' => 'nullable|string',
            'extra_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:50',
        ];
    }

    protected function roomConfigurationUpdateRules(): array
    {
        return [
            'room_type_id' => 'sometimes|integer|exists:room_types,id',
            'type' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:150',
            'description' => 'sometimes|nullable|string',
            'meal_code' => 'sometimes|nullable|in:RO,HB,FB,AI',
            'extra_price' => 'sometimes|nullable|numeric|min:0',
            'status' => 'sometimes|nullable|string|max:50',
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate(
                $this->roomConfigurationRules()
            );


            $existingConfiguration = RoomConfiguration::where(
                'room_type_id',
                $validated['room_type_id']
            )
                ->where('type', $validated['type'])
                ->where('name', $validated['name'])
                ->first();

            if ($existingConfiguration) {
                return response()->json([
                    'status' => false,
                    'message' => 'This room configuration already exists.',
                ], 409);
            }

            $configuration = RoomConfiguration::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Room configuration created successfully.',
                'data' => $configuration,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to create room configuration', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create room configuration.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $configuration = RoomConfiguration::find($id);

            if (!$configuration) {
                return response()->json([
                    'status' => false,
                    'message' => 'Room configuration not found.',
                ], 404);
            }

            $validated = $request->validate(
                $this->roomConfigurationUpdateRules()
            );

            $roomTypeId = $validated['room_type_id'] ?? $configuration->room_type_id;
            $type = $validated['type'] ?? $configuration->type;
            $name = $validated['name'] ?? $configuration->name;

            $existingConfiguration = RoomConfiguration::where(
                'room_type_id',
                $roomTypeId
            )
                ->where('type', $type)
                ->where('name', $name)
                ->where('id', '!=', $configuration->id)
                ->first();

            if ($existingConfiguration) {
                return response()->json([
                    'status' => false,
                    'message' => 'This room configuration already exists.',
                ], 409);
            }

            $configuration->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Room configuration updated successfully.',
                'data' => $configuration->fresh(),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to update room configuration', [
                'room_configuration_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update room configuration.',
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $query = RoomConfiguration::query();

            if ($request->filled('room_type_id')) {
                $query->where(
                    'room_type_id',
                    $request->integer('room_type_id')
                );
            }

            if ($request->filled('type')) {
                $query->where(
                    'type',
                    $request->input('type')
                );
            }

            $configurations = $query
                ->latest()
                ->get();

            return response()->json([
                'status' => true,
                'data' => $configurations,
            ], 200);

        } catch (Throwable $e) {

            Log::error('Failed to fetch room configurations', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch room configurations.',
            ], 500);
        }
    }

}
