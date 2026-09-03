<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Property;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function show($id)
    {
        try {
            $property = Property::with([
                'country',
                'state',
                'city',
            ])->find($id);

            if (!$property) {
                return response()->json([
                    'status' => false,
                    'message' => 'Property not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $property,
            ], 200);

        } catch (Throwable $e) {

            Log::error('Failed to fetch property details', [
                'property_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch property details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function options(Request $request)
    {
        try {
            $query = Property::query();

            if ($request->filled('hotel_admin_id')) {
                $query->where(
                    'hotel_admin_id',
                    $request->integer('hotel_admin_id')
                );
            }

            $properties = $query
                ->orderBy('name')
                ->get();

            $countryIds = $properties
                ->pluck('country_id')
                ->filter()
                ->unique()
                ->values();

            $cityIds = $properties
                ->pluck('city_id')
                ->filter()
                ->unique()
                ->values();

            $countries = Country::whereIn('id', $countryIds)
                ->select('id', 'name')
                ->get();

            $cities = City::whereIn('id', $cityIds)
                ->select('id', 'name')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $properties,
                'location_data' => [
                    'countries' => $countries,
                    'cities' => $cities,
                ],
            ], 200);

        } catch (Throwable $e) {

            Log::error('Failed to fetch property options', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch property options.',
            ], 500);
        }
    }

    // using property_id in roomType table
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

    // using property_id to assign amenities to a property
    public function propertiesForAmenities(Request $request)
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

            Log::error('Failed to search properties for amenities', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to search properties.',
            ], 500);
        }
    }

     // using room type ID to assign amenities to a room type
    public function roomTypesForAmenities(Request $request)
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
                'property.hotelAdmin:id,name,phone,email',
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

            Log::error('Failed to search room types for amenities', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to search room types.',
            ], 500);
        }
    }

    // using property_id to find room types for room configuration.
    public function propertiesForRoomConfiguration(Request $request)
    {
        try {

            $query = trim($request->query('query', ''));

            if (strlen($query) < 3) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please enter at least 3 characters to search.',
                ], 422);
            }

            $user = Auth::user();

            $properties = Property::with([
                'country:id,name',
                'city:id,name',
                'hotelAdmin:id,name,phone',
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

            });


            if ($user->role === 'hotel_admin') {

                $properties->where(
                    'hotel_admin_id',
                    $user->id
                );
            }


            $properties = $properties
                ->latest()
                ->limit(10)
                ->get();


            return response()->json([
                'status' => true,
                'data' => $properties,
            ], 200);

        } catch (Throwable $e) {

            Log::error('Failed to search properties for room configuration', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to search properties.',
            ], 500);
        }
    }

}