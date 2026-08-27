<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery\Matcher\AnyArgs;

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

   
}