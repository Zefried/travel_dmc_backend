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
            'property_id' => 'required|integer|exists:properties,id',

            'property_amenity_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'property_amenity_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:property_amenities,id',
            ],
        ];
    }

    protected function propertyAmenityConfigUpdateRules(): array
    {
        return [
            'property_id' => 'sometimes|integer|exists:properties,id',

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

            $propertyId = $validated['property_id'];
            $amenityIds = $validated['property_amenity_ids'];


            $existingAmenityIds = PropertyAmenityConfig::where(
                'property_id',
                $propertyId
            )
                ->whereIn(
                    'property_amenity_id',
                    $amenityIds
                )
                ->pluck('property_amenity_id')
                ->toArray();


            if (!empty($existingAmenityIds)) {

                return response()->json([
                    'status' => false,
                    'message' => 'One or more amenities are already assigned to this property.',
                    'existing_amenity_ids' => $existingAmenityIds,
                ], 409);
            }


            $configs = DB::transaction(function () use (
                $propertyId,
                $amenityIds
            ) {

                $data = [];

                foreach ($amenityIds as $amenityId) {

                    $data[] = [
                        'property_id' => $propertyId,
                        'property_amenity_id' => $amenityId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }


                PropertyAmenityConfig::insert($data);


                return PropertyAmenityConfig::where(
                    'property_id',
                    $propertyId
                )
                    ->whereIn(
                        'property_amenity_id',
                        $amenityIds
                    )
                    ->get();
            });


            return response()->json([
                'status' => true,
                'message' => 'Property amenities assigned successfully.',
                'data' => $configs,
            ], 201);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (Throwable $e) {

            Log::error(
                'Failed to assign property amenities',
                [
                    'error' => $e->getMessage(),
                ]
            );


            return response()->json([
                'status' => false,
                'message' => 'Failed to assign property amenities.',
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
                    'message' => 'Property amenity configuration not found.',
                ], 404);
            }


            $validated = $request->validate(
                $this->propertyAmenityConfigUpdateRules()
            );


            $propertyId =
                $validated['property_id']
                ?? $config->property_id;


            $propertyAmenityId =
                $validated['property_amenity_id']
                ?? $config->property_amenity_id;


            $existingConfig = PropertyAmenityConfig::where(
                'property_id',
                $propertyId
            )
                ->where(
                    'property_amenity_id',
                    $propertyAmenityId
                )
                ->where('id', '!=', $config->id)
                ->first();


            if ($existingConfig) {

                return response()->json([
                    'status' => false,
                    'message' => 'This amenity is already assigned to this property.',
                ], 409);
            }


            $config->update($validated);


            return response()->json([
                'status' => true,
                'message' => 'Property amenity updated successfully.',
                'data' => $config->fresh(),
            ], 200);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (Throwable $e) {

            Log::error(
                'Failed to update property amenity',
                [
                    'property_amenity_config_id' => $id,
                    'error' => $e->getMessage(),
                ]
            );


            return response()->json([
                'status' => false,
                'message' => 'Failed to update property amenity.',
            ], 500);
        }
    }

    public function existingPropertyAmenities($id)
    {
        try {

            $amenities = PropertyAmenityConfig::where(
                'property_id',
                $id
            )
                ->with('propertyAmenity')
                ->get()
                ->pluck('propertyAmenity');

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
}