<?php

namespace App\Http\Controllers\BookingController;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class BookingController extends Controller
{
    protected function bookingRules(): array
    {
        return [
            'booking_reference' => 'required|string|max:255|unique:bookings,booking_reference',

            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',

            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',

            'status' => 'nullable|in:pending,confirmed,cancelled,completed',

            'total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }

    protected function bookingUpdateRules($id): array
    {
        return [
            'booking_reference' => 'sometimes|string|max:255|unique:bookings,booking_reference,' . $id,

            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date|after:check_in',

            'adults' => 'sometimes|integer|min:1',
            'children' => 'sometimes|nullable|integer|min:0',

            'status' => 'sometimes|nullable|in:pending,confirmed,cancelled,completed',

            'total_amount' => 'sometimes|nullable|numeric|min:0',
            'notes' => 'sometimes|nullable|string',
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->bookingRules());

            $booking = Booking::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Booking created successfully.',
                'data' => $booking,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to create booking', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create booking.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $booking = Booking::find($id);

            if (!$booking) {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking not found.',
                ], 404);
            }

            $validated = $request->validate(
                $this->bookingUpdateRules($id)
            );

            $booking->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Booking updated successfully.',
                'data' => $booking->fresh(),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to update booking', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update booking.',
            ], 500);
        }
    }
}