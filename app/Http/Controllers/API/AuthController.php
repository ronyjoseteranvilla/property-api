<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateUserRequest;
use App\Http\Requests\API\UserLoginRequest;
use App\Models\User;
use App\Services\API\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function createUser(CreateUserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user_token = $this->authService->createUser($data['name'], $data['email'], $data['password']);  

            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'token' => $user_token
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }

    }

    public function login(UserLoginRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            $user_token = $this->authService->login($data['email'], $data['password']);

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => $user_token
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
