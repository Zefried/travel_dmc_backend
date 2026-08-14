<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    
    private AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function login(Request $request)
    {
        $result = $this->auth->login($request->all());

        return response()->json(
            $result,
            $result['status'] ? 200 : 401
        );
    }

    public function register(Request $request)
    {

        $result = $this->auth->register($request->all());

        return response()->json($result);
    }

    public function logout(Request $request)
    {
        $result = $this->auth->logout($request->user());

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
        ], $result['code']);
    }
}




