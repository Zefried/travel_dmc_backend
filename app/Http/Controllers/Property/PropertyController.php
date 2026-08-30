<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class PropertyController extends Controller
{
    protected function propertyRules(): array
    {
        return [
            'hotel_admin_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where(fn ($query) => $query->where('role', 'hotel_admin')),
            ],

            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'description' => 'nullable|string',

            'country_id' => 'required|integer|exists:countries,id',
            'state_id' => 'nullable|integer|exists:states,id',
            'city_id' => 'required|integer|exists:cities,id',

            'address' => 'nullable|string',
            'postal_code' => 'required|string|max:50',

            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

            'phone' => 'required|string|max:50',
            'alternative_phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',

            'status' => 'nullable|string|max:50',
        ];
    }

    protected function propertyUpdateRules(): array
    {
        return [
            'hotel_admin_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')
                    ->where(fn ($query) => $query->where('role', 'hotel_admin')),
            ],

            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:255',
            'star_rating' => 'sometimes|nullable|integer|min:1|max:5',
            'description' => 'sometimes|nullable|string',

            'country_id' => 'sometimes|integer|exists:countries,id',
            'state_id' => 'sometimes|nullable|integer|exists:states,id',
            'city_id' => 'sometimes|integer|exists:cities,id',

            'address' => 'sometimes|nullable|string',
            'postal_code' => 'sometimes|string|max:50',

            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',

            'phone' => 'sometimes|string|max:50',
            'alternative_phone' => 'sometimes|nullable|string|max:50',
            'email' => 'sometimes|nullable|email|max:255',
            'website' => 'sometimes|nullable|string|max:255',

            'status' => 'sometimes|nullable|in:active,inactive',
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->propertyRules());

            $existingProperty = Property::where(
                'phone',
                $validated['phone']
            )->first();

            if ($existingProperty) {
                return response()->json([
                    'status' => false,
                    'message' => 'A property with this phone number already exists.',
                ], 409);
            }

            $property = Property::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Property created successfully.',
                'data' => $property,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to create property', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create property.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $property = Property::find($id);

            if (!$property) {
                return response()->json([
                    'status' => false,
                    'message' => 'Property not found.',
                ], 404);
            }

            $validated = $request->validate(
                $this->propertyUpdateRules()
            );

            $property->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Property updated successfully.',
                'data' => $property->fresh(),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to update property', [
                'property_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update property.',
            ], 500);
        }
    }

    public function propertiesForRoomType(Request $request)
    {
        try {
            $query = trim($request->query('query', ''));

            if (strlen($query) < 3) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please enter at least 3 characters to search.',
                ], 422);
            }

            $properties = Property::with([
                'country:id,name',
                'city:id,name',
                'hotelAdmin:id,name,phone,email',
            ])
            ->where('status', 'active')
            ->where(function ($queryBuilder) use ($query) {

                $queryBuilder
                    ->where('name', 'like', $query . '%')
                    ->orWhereHas('hotelAdmin', function ($adminQuery) use ($query) {

                        $adminQuery
                            ->where('phone', 'like', $query . '%')
                            ->orWhere('email', 'like', $query . '%');

                    });

            })
            ->latest()
            ->limit(10)
            ->get();

            return response()->json([
                'status' => true,
                'data' => $properties,
            ], 200);

        } catch (Throwable $e) {
            Log::error('Failed to search properties for room type', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to search properties.',
            ], 500);
        }
    }

}