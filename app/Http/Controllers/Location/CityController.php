<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CityController extends Controller
{
    protected function cityRules(): array
    {
        return [
            'state_id' => 'required|integer|exists:states,id',
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    protected function cityUpdateRules(): array
    {
        return [
            'state_id' => 'sometimes|integer|exists:states,id',
            'name' => 'sometimes|string|max:150',
            'code' => 'sometimes|nullable|string|max:20',
            'status' => 'sometimes|nullable|in:active,inactive',
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->cityRules());

            $existingCity = City::where('state_id', $validated['state_id'])
                ->where('name', $validated['name'])
                ->first();

            if ($existingCity) {
                return response()->json([
                    'status' => false,
                    'message' => 'A city with this name already exists for this state.',
                ], 409);
            }

            $city = City::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'City created successfully.',
                'data' => $city,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to create city', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create city.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $city = City::find($id);

            if (!$city) {
                return response()->json([
                    'status' => false,
                    'message' => 'City not found.',
                ], 404);
            }

            $validated = $request->validate($this->cityUpdateRules());

            $stateId = $validated['state_id'] ?? $city->state_id;
            $name = $validated['name'] ?? $city->name;

            $existingCity = City::where('state_id', $stateId)
                ->where('name', $name)
                ->where('id', '!=', $city->id)
                ->first();

            if ($existingCity) {
                return response()->json([
                    'status' => false,
                    'message' => 'A city with this name already exists for this state.',
                ], 409);
            }

            $city->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'City updated successfully.',
                'data' => $city->fresh(),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to update city', [
                'city_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update city.',
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $query = City::query();

            if ($request->filled('state_id')) {
                $query->where(
                    'state_id',
                    $request->integer('state_id')
                );
            }

            $cities = $query->latest()->paginate(15);

            return response()->json([
                'status' => true,
                'data' => $cities,
            ], 200);

        } catch (Throwable $e) {
            Log::error('Failed to fetch cities', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch cities.',
            ], 500);
        }
    }
}