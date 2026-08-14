<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function update()
    {
    }

    public function destroy()
    {
    }
}