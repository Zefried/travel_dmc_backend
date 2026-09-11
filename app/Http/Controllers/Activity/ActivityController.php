<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ActivityController extends Controller
{

    protected function activityRules(): array
    {
        return [
            'country_id' =>
                'required|integer|exists:countries,id',

            'state_id' =>
                'required|integer|exists:states,id',

            'city_id' =>
                'required|integer|exists:cities,id',

            'name' =>
                'required|string|max:255',

            'category' =>
                'nullable|string|max:255',

            'description' =>
                'nullable|string',

            'duration' =>
                'required|numeric|min:0.1',

            'duration_unit' =>
                'required|in:minutes,hours,days',

            'base_price' =>
                'required|numeric|min:0',

            'status' =>
                'nullable|in:active,inactive',
        ];
    }


    protected function activityUpdateRules(): array
    {
        return [
            'country_id' =>
                'sometimes|integer|exists:countries,id',

            'state_id' =>
                'sometimes|integer|exists:states,id',

            'city_id' =>
                'sometimes|integer|exists:cities,id',

            'name' =>
                'sometimes|string|max:255',

            'category' =>
                'sometimes|nullable|string|max:255',

            'description' =>
                'sometimes|nullable|string',

            'duration' =>
                'sometimes|numeric|min:0.1',

            'duration_unit' =>
                'sometimes|in:minutes,hours,days',

            'base_price' =>
                'sometimes|numeric|min:0',

            'status' =>
                'sometimes|nullable|in:active,inactive',
        ];
    }


    public function store(Request $request)
    {
        try {

            $validated = $request->validate(
                $this->activityRules()
            );


            $activity = Activity::create(
                $validated
            );


            return response()->json([
                'status' => true,
                'message' => 'Activity created successfully.',
                'data' => $activity,
            ], 201);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (Throwable $e) {

            Log::error('Failed to create activity', [
                'error' => $e->getMessage(),
            ]);


            return response()->json([
                'status' => false,
                'message' => 'Failed to create activity.',
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {

            $activity = Activity::find($id);


            if (!$activity) {

                return response()->json([
                    'status' => false,
                    'message' => 'Activity not found.',
                ], 404);
            }


            $validated = $request->validate(
                $this->activityUpdateRules()
            );


            $activity->update(
                $validated
            );


            return response()->json([
                'status' => true,
                'message' => 'Activity updated successfully.',
                'data' => $activity->fresh(),
            ], 200);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (Throwable $e) {

            Log::error('Failed to update activity', [
                'activity_id' => $id,
                'error' => $e->getMessage(),
            ]);


            return response()->json([
                'status' => false,
                'message' => 'Failed to update activity.',
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {

            $activities = Activity::with([
                'country',
                'state',
                'city',
            ])
                ->latest()
                ->paginate(10);


            return response()->json([
                'status' => true,
                'data' => $activities,
            ], 200);


        } catch (Throwable $e) {

            Log::error('Failed to fetch activity list', [
                'error' => $e->getMessage(),
            ]);


            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch activity list.',
            ], 500);
        }
    }


    // activity transfer will be handled here
       public function listTransfers(Request $request)
    {
        try {
            $validated = $request->validate([
                'activity_id' => 'sometimes|integer|exists:activities,id',
                'page' => 'sometimes|integer|min:1',
            ]);

            $query = ActivityTransfer::with('activity')
                ->latest();

            if ($request->filled('activity_id')) {
                $query->where('activity_id', $validated['activity_id']);
            }

            $transfers = $query->paginate(10);

            return response()->json([
                'status' => true,
                'data' => $transfers,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to fetch activity transfers', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch activity transfers.',
            ], 500);
        }
    }

    public function storeTransfer(Request $request)
    {
        try {

            $validated = $request->validate([
                'activity_id' =>
                    'required|integer|exists:activities,id',

                'name' =>
                    'required|string|max:255',

                'transfer_type' =>
                    'required|in:shared,private',

                'transfer_duration' =>
                    'required|numeric|min:0.1',

                'transfer_duration_unit' =>
                    'required|in:minutes,hours',

                'transfer_price' =>
                    'required|numeric|min:0',

                'pickup_type' =>
                    'nullable|string|max:255',

                'pickup_description' =>
                    'nullable|string',

                'status' =>
                    'nullable|in:active,inactive',
            ]);


            $transfer = ActivityTransfer::create(
                $validated
            );


            return response()->json([
                'status' => true,
                'message' => 'Activity transfer created successfully.',
                'data' => $transfer,
            ], 201);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (Throwable $e) {

            Log::error('Failed to create activity transfer', [
                'error' => $e->getMessage(),
            ]);


            return response()->json([
                'status' => false,
                'message' => 'Failed to create activity transfer.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}