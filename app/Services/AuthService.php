<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(array $data): array
    {
        try {

            if (empty($data['email']) && empty($data['phone'])) {
                return [
                    'status' => false,
                    'message' => 'Email or phone is required',
                    'code' => 422
                ];
            }

            $validator = Validator::make($data, [
                'email' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return [
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'code' => 422
                ];
            }

            if (!empty($data['email'])) {
                $user = User::where('email', $data['email'])->first();
            } else {
                $user = User::where('phone', $data['phone'])->first();
            }

            if (!$user || !Hash::check($data['password'], $user->password)) {
                return [
                    'status' => false,
                    'message' => 'Invalid credentials',
                    'code' => 401
                ];
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return [
                'status' => true,
                'data' => [
                    'user' => $user,
                    'token' => $token
                ],
                'code' => 200
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    public function logout($user): array
    {
        try {
            $user->currentAccessToken()->delete();

            return [
                'status' => true,
                'message' => 'Logged out successfully',
                'code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }


    public function register(array $data): array
    {
        try {

            if (empty($data['email']) && empty($data['phone'])) {
                return [
                    'status' => false,
                    'message' => 'Email or phone is required',
                    'code' => 422
                ];
            }

            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255|unique:users,email',
                'phone' => 'nullable|string|max:20|unique:users,phone',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return [
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'code' => 422
                ];
            }

            $validated = $validator->validated();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => 'user',
            ]);

            return [
                'status' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                ],
                'code' => 201
            ];
            } catch (\Exception $e) {
                return [
                    'status' => false,
                    'message' => $e->getMessage(),
                    'code' => 500
                ];
            }
    }


    public function createAdminUser(array $data): array
    {
        try {

            if (empty($data['email']) && empty($data['phone'])) {
                return [
                    'status' => false,
                    'message' => 'Email or phone is required',
                    'code' => 422
                ];
            }

            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255|unique:users,email',
                'phone' => 'nullable|string|max:20|unique:users,phone',
                'password' => 'required|string|min:8',
                'role' => 'required|in:hotel_admin,vehicle_admin,sub_admin',
            ]);

            if ($validator->fails()) {
                return [
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'code' => 422
                ];
            }

            $validated = $validator->validated();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            return [
                'status' => true,
                'message' => 'User created successfully',
                'data' => [
                    'user' => $user,
                ],
                'code' => 201
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }
}
