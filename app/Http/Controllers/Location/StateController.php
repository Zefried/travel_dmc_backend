<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class StateController extends Controller
{
    protected function stateRules(): array
    {
        return [
            'country_id' => 'required|integer|exists:countries,id',
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    protected function stateUpdateRules(): array
    {
        return [
            'country_id' => 'sometimes|integer|exists:countries,id',
            'name' => 'sometimes|string|max:150',
            'code' => 'sometimes|nullable|string|max:20',
            'status' => 'sometimes|nullable|in:active,inactive',
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->stateRules());

            $existingState = State::where('country_id', $validated['country_id'])
                ->where('name', $validated['name'])
                ->first();

            if ($existingState) {
                return response()->json([
                    'status' => false,
                    'message' => 'A state with this name already exists for this country.',
                ], 409);
            }

            $state = State::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'State created successfully.',
                'data' => $state,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to create state', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create state.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $state = State::find($id);

            if (!$state) {
                return response()->json([
                    'status' => false,
                    'message' => 'State not found.',
                ], 404);
            }

            $validated = $request->validate($this->stateUpdateRules());

            $countryId = $validated['country_id'] ?? $state->country_id;
            $name = $validated['name'] ?? $state->name;

            $existingState = State::where('country_id', $countryId)
                ->where('name', $name)
                ->where('id', '!=', $state->id)
                ->first();

            if ($existingState) {
                return response()->json([
                    'status' => false,
                    'message' => 'A state with this name already exists for this country.',
                ], 409);
            }

            $state->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'State updated successfully.',
                'data' => $state->fresh(),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to update state', [
                'state_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update state.',
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $query = State::query();

            if ($request->filled('country_id')) {
                $query->where(
                    'country_id',
                    $request->integer('country_id')
                );
            }

            if ($request->filled('search')) {
                $query->where(
                    'name',
                    'like',
                    '%' . $request->input('search') . '%'
                );
            }

            $states = $query
                ->with('country')
                ->latest()
                ->paginate(2);

            return response()->json([
                'status' => true,
                'data' => $states,
            ], 200);

        } catch (Throwable $e) {
            Log::error('Failed to fetch states', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch states.',
            ], 500);
        }
    }
}