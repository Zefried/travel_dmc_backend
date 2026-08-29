<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CountryController extends Controller
{
    protected function countryRules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:10',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    protected function countryUpdateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:150',
            'code' => 'sometimes|string|max:10',
            'status' => 'sometimes|nullable|in:active,inactive',
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->countryRules());

            $existingCountry = Country::where('name', $validated['name'])
                ->orWhere('code', $validated['code'])
                ->first();

            if ($existingCountry) {
                return response()->json([
                    'status' => false,
                    'message' => 'A country with this name or code already exists.',
                ], 409);
            }

            $country = Country::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Country created successfully.',
                'data' => $country,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to create country', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create country.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $country = Country::find($id);

            if (!$country) {
                return response()->json([
                    'status' => false,
                    'message' => 'Country not found.',
                ], 404);
            }

            $validated = $request->validate($this->countryUpdateRules());

            $name = $validated['name'] ?? $country->name;
            $code = $validated['code'] ?? $country->code;

            $existingCountry = Country::where(function ($query) use ($name, $code) {
                    $query->where('name', $name)
                        ->orWhere('code', $code);
                })
                ->where('id', '!=', $country->id)
                ->first();

            if ($existingCountry) {
                return response()->json([
                    'status' => false,
                    'message' => 'A country with this name or code already exists.',
                ], 409);
            }

            $country->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Country updated successfully.',
                'data' => $country->fresh(),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to update country', [
                'country_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update country.',
            ], 500);
        }
    }

    public function index()
    {
        try {
            $countries = Country::latest()->paginate(15);

            return response()->json([
                'status' => true,
                'data' => $countries,
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to fetch countries', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch countries.',
            ], 500);
        }
    }
}