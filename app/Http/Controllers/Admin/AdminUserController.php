<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function store(Request $request)
    {
        $result = $this->authService->createAdminUser($request->all());

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ], $result['code']);
    }

    public function update(Request $request, $id)
    {
  
        $result = $this->authService->updateAdminUser($id, $request->all());

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ], $result['code']);
    }

    public function searchHotelAdmins(Request $request)
    {
        $query = $request->query('query');

        if (!$query || strlen($query) < 3) {
            return response()->json([
                'status' => false,
                'message' => 'Please enter at least 3 characters to search.',
            ], 422);
        }

        $users = User::query()
            ->where('role', 'hotel_admin')
            ->where(function ($q) use ($query) {
                $q->where('email', 'like', $query . '%')
                ->orWhere('phone', 'like', $query . '%');
            })
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'role',
            ])
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $users,
        ], 200);
    }

   
}