<?php

namespace App\Http\Controllers\Amenity;

use App\Http\Controllers\Controller;
use App\Models\PropertyAmenity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AmenityController extends Controller
{
    protected function amenityRules(): array
    {
        return [
            'category' => 'required|string|max:100',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    protected function amenityUpdateRules(): array
    {
        return [
            'category' => 'sometimes|string|max:100',
            'name' => 'sometimes|string|max:150',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|nullable|in:active,inactive',
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->amenityRules());

            $existingAmenity = PropertyAmenity::where('category', $validated['category'])
                ->where('name', $validated['name'])
                ->first();

            if ($existingAmenity) {
                return response()->json([
                    'status' => false,
                    'message' => 'This amenity already exists in this category.',
                ], 409);
            }

            $amenity = PropertyAmenity::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Amenity created successfully.',
                'data' => $amenity,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'error' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to create amenity', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create amenity.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $amenity = PropertyAmenity::find($id);

            if (!$amenity) {
                return response()->json([
                    'status' => false,
                    'message' => 'Amenity not found.',
                ], 404);
            }

            $validated = $request->validate($this->amenityUpdateRules());

            $category = $validated['category'] ?? $amenity->category;
            $name = $validated['name'] ?? $amenity->name;

            $existingAmenity = PropertyAmenity::where('category', $category)
                ->where('name', $name)
                ->where('id', '!=', $amenity->id)
                ->first();

            if ($existingAmenity) {
                return response()->json([
                    'status' => false,
                    'message' => 'This amenity already exists in this category.',
                ], 409);
            }

            $amenity->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Amenity updated successfully.',
                'data' => $amenity->fresh(),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to update amenity', [
                'amenity_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update amenity.',
            ], 500);
        }
    }

    public function index()
    {
        try {
            $amenities = PropertyAmenity::latest()->get();

            return response()->json([
                'status' => true,
                'data' => $amenities,
            ], 200);

        } catch (Throwable $e) {
            Log::error('Failed to fetch amenities', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch amenities.',
            ], 500);
        }
    }
}