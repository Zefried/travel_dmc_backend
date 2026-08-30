<?php

namespace App\Http\Controllers\PropAmenityConfig;

use App\Http\Controllers\Controller;
use App\Models\PropertyAmenityConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PropertyAmenityConfigController extends Controller
{
    protected function propertyAmenityConfigRules(): array
    {
        return [
            'target_type' => 'required|in:property,room_type',

            'property_id' => 'nullable|integer|exists:properties,id',

            'room_type_id' => 'nullable|integer|exists:room_types,id',

            'property_amenity_ids' => 'required|array|min:1',

            'property_amenity_ids.*' =>
                'required|integer|distinct|exists:property_amenities,id',
        ];
    }

    protected function propertyAmenityConfigUpdateRules(): array
    {
        return [
            'target_type' => 'required|in:property,room_type',

            'property_id' => 'nullable|integer|exists:properties,id',

            'room_type_id' => 'nullable|integer|exists:room_types,id',

            'property_amenity_id' => [
                'sometimes',
                'integer',
                'exists:property_amenities,id',
            ],
        ];
    }


    public function store(Request $request)
    {
        try {

            $validated = $request->validate(
                $this->propertyAmenityConfigRules()
            );

            $targetType = $validated['target_type'];

            $targetColumn = $targetType === 'property'
                ? 'property_id'
                : 'room_type_id';

            $targetId = $validated[$targetColumn] ?? null;

            if (!$targetId) {
                return response()->json([
                    'status' => false,
                    'message' => "{$targetColumn} is required.",
                ], 422);
            }

            $amenityIds = $validated['property_amenity_ids'];


            DB::transaction(function () use (
                $targetColumn,
                $targetId,
                $targetType,
                $amenityIds
            ) {

                $query = PropertyAmenityConfig::where(
                    $targetColumn,
                    $targetId
                );


                $existingAmenityIds = $query
                    ->pluck('property_amenity_id')
                    ->toArray();


                $query->whereNotIn(
                    'property_amenity_id',
                    $amenityIds
                )->delete();


                $newAmenityIds = array_diff(
                    $amenityIds,
                    $existingAmenityIds
                );


                foreach ($newAmenityIds as $amenityId) {

                    PropertyAmenityConfig::create([
                        'property_id' =>
                            $targetType === 'property'
                                ? $targetId
                                : null,

                        'room_type_id' =>
                            $targetType === 'room_type'
                                ? $targetId
                                : null,

                        'property_amenity_id' => $amenityId,
                    ]);
                }
            });


            return response()->json([
                'status' => true,
                'message' => 'Amenities saved successfully.',
            ], 200);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (Throwable $e) {

            Log::error('Failed to save amenities', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to save amenities.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {

            $config = PropertyAmenityConfig::find($id);

            if (!$config) {
                return response()->json([
                    'status' => false,
                    'message' => 'Amenity configuration not found.',
                ], 404);
            }


            $validated = $request->validate(
                $this->propertyAmenityConfigUpdateRules()
            );


            $targetType = $validated['target_type'];

            if ($targetType === 'property') {

                $targetId = $validated['property_id'] ?? null;

                if (!$targetId) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Property ID is required.',
                    ], 422);
                }

                $propertyId = $targetId;
                $roomTypeId = null;

            } else {

                $targetId = $validated['room_type_id'] ?? null;

                if (!$targetId) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Room Type ID is required.',
                    ], 422);
                }

                $propertyId = null;
                $roomTypeId = $targetId;
            }


            $propertyAmenityId =
                $validated['property_amenity_id']
                ?? $config->property_amenity_id;


            $exists = PropertyAmenityConfig::where(
                'property_amenity_id',
                $propertyAmenityId
            )
                ->where('id', '!=', $config->id)
                ->where(function ($query) use (
                    $propertyId,
                    $roomTypeId
                ) {

                    if ($propertyId) {
                        $query->where('property_id', $propertyId);
                    } else {
                        $query->where('room_type_id', $roomTypeId);
                    }

                })
                ->exists();


            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'This amenity is already assigned to this target.',
                ], 409);
            }


            $config->update([
                'property_id' => $propertyId,
                'room_type_id' => $roomTypeId,
                'property_amenity_id' => $propertyAmenityId,
            ]);


            return response()->json([
                'status' => true,
                'message' => 'Amenity configuration updated successfully.',
                'data' => $config->fresh(),
            ], 200);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (Throwable $e) {

            Log::error('Failed to update amenity configuration', [
                'property_amenity_config_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update amenity configuration.',
            ], 500);
        }
    }

    public function existingPropertyAmenities($id)
    {
        try {

            $configs = PropertyAmenityConfig::where(
                'property_id',
                $id
            )
                ->with('propertyAmenity')
                ->get();

            $amenities = $configs->map(function ($config) {

                return [
                    'config_id' => $config->id,
                    'id' => $config->propertyAmenity->id,
                    'category' => $config->propertyAmenity->category,
                    'name' => $config->propertyAmenity->name,
                    'description' => $config->propertyAmenity->description,
                    'status' => $config->propertyAmenity->status,
                ];

            })->values();


            return response()->json([
                'status' => true,
                'data' => $amenities,
            ], 200);

        } catch (Throwable $e) {

            Log::error('Failed to fetch existing property amenities', [
                'property_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch property amenities.',
            ], 500);
        }
    }

    public function existingRoomTypeAmenities($id)
    {
        try {

            $configs = PropertyAmenityConfig::where(
                'room_type_id',
                $id
            )
                ->with('propertyAmenity')
                ->get();

            $amenities = $configs->map(function ($config) {

                return [
                    'config_id' => $config->id,
                    'id' => $config->propertyAmenity->id,
                    'category' => $config->propertyAmenity->category,
                    'name' => $config->propertyAmenity->name,
                    'description' => $config->propertyAmenity->description,
                    'status' => $config->propertyAmenity->status,
                ];

            })->values();


            return response()->json([
                'status' => true,
                'data' => $amenities,
            ], 200);

        } catch (Throwable $e) {

            Log::error('Failed to fetch existing room type amenities', [
                'room_type_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch room type amenities.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {

            $config = PropertyAmenityConfig::find($id);

            if (!$config) {
                return response()->json([
                    'status' => false,
                    'message' => 'Amenity assignment not found.',
                ], 404);
            }

            $config->delete();

            return response()->json([
                'status' => true,
                'message' => 'Amenity removed successfully.',
            ], 200);

        } catch (Throwable $e) {

            Log::error('Failed to remove amenity assignment', [
                'amenity_config_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to remove amenity.',
            ], 500);
        }
    }
}