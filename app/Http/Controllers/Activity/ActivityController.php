<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity;
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
    
}